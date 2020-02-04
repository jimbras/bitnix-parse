<?php declare(strict_types=1);

/**
 *  This program is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU Affero General Public License as
 *  published by the Free Software Foundation, either version 3 of the
 *  License, or (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU Affero General Public License for more details.
 *
 *  You should have received a copy of the GNU Affero General Public License
 *  along with this program. If not, see <https://www.gnu.org/licenses/agpl-3.0.txt>.
 */

namespace Bitnix\Parse\Parser;

use Bitnix\Parse\Expression,
    Bitnix\Parse\Lexer,
    Bitnix\Parse\ParseFailure,
    Bitnix\Parse\Parser,
    Bitnix\Parse\Position,
    Bitnix\Parse\Token,
    Bitnix\Parse\Tokenizer,
    Bitnix\Parse\Lexer\TokenSet,
    Bitnix\Parse\Lexer\TokenStream,
    Bitnix\Parse\Lexer\Scanner,
    PHPUnit\Framework\TestCase;

class ValueExpression implements Expression {
    private int $value = 0;
    public function __construct(int $value) {
        $this->value = $value;
    }
    public function value() : int {
        return $this->value;
    }
}

class ValueParser implements PrefixParser {
    public function parsePrefix(Parser $parser, Token $token) : Expression {
        return new ValueExpression((int) $token->lexeme());
    }
}

class OperationParser implements InfixParser {
    private int $precedence;
    private string $operation;

    public function __construct(string $operation, int $precedence) {
        $this->precedence = $precedence;
        $this->operation = $operation;
    }

    public function precedence() : int {
        return $this->precedence;
    }

    public function parseInfix(Parser $parser, Expression $left, Token $token) : Expression {
        $right = $parser->expression($this->precedence);
        $l = $left->value();
        $r = (int) $right->value();

        switch ($this->operation) {
            case '+':
                $value = $l + $r;
                break;
            default:
                // *
                $value = $l * $r;
                break;
        }
        return new ValueExpression($value);
    }
}

/**
 * ...
 *
 * @version 0.1.0
 */
class PrattParserTest extends TestCase {

    public function testParserDelegatesValidToLexer() {
        $lexer = $this->createMock(Lexer::CLASS);
        $lexer
            ->expects($this->once())
            ->method('valid')
            ->will($this->returnValue(true));

        $parser = new PrattParser($lexer, $this->createMock(Grammar::CLASS));
        $this->assertTrue($parser->valid());
    }

    public function testParserDelegatesNextToLexer() {
        $token = new Token('T_FOO', 'bar');
        $lexer = $this->createMock(Lexer::CLASS);
        $lexer
            ->expects($this->once())
            ->method('next')
            ->will($this->returnValue($token));

        $parser = new PrattParser($lexer, $this->createMock(Grammar::CLASS));
        $this->assertSame($token, $parser->next());
    }

    public function testParserDelegatesPositionToLexer() {
        $position = new Position();
        $lexer = $this->createMock(Lexer::CLASS);
        $lexer
            ->expects($this->once())
            ->method('position')
            ->will($this->returnValue($position));

        $parser = new PrattParser($lexer, $this->createMock(Grammar::CLASS));
        $this->assertSame($position, $parser->position());
    }

    public function testParserDelegatesErrorToLexer() {
        $this->expectException(ParseFailure::CLASS);
        $lexer = $this->createMock(Lexer::CLASS);
        $lexer
            ->expects($this->once())
            ->method('error')
            ->with('kaput')
            ->will($this->throwException(new ParseFailure('kaput')));

        $parser = new PrattParser($lexer, $this->createMock(Grammar::CLASS));
        $parser->error('kaput');
    }

    public function testParserDelegatesPeekToLexer() {
        $token = new Token('T_FOO', 'bar');
        $lexer = $this->createMock(Lexer::CLASS);
        $lexer
            ->expects($this->once())
            ->method('peek')
            ->with(5)
            ->will($this->returnValue($token));

        $parser = new PrattParser($lexer, $this->createMock(Grammar::CLASS));
        $this->assertSame($token, $parser->peek(5));
    }

    public function testParserDelegatesMatchToLexer() {
        $lexer = $this->createMock(Lexer::CLASS);
        $lexer
            ->expects($this->once())
            ->method('match')
            ->with('T_FOO')
            ->will($this->returnValue(true));

        $parser = new PrattParser($lexer, $this->createMock(Grammar::CLASS));
        $this->assertTrue($parser->match('T_FOO'));
    }

    public function testParserDelegatesConsumeToLexer() {
        $token = new Token('T_FOO', 'bar');
        $lexer = $this->createMock(Lexer::CLASS);
        $lexer
            ->expects($this->once())
            ->method('consume')
            ->with('T_FOO')
            ->will($this->returnValue($token));

        $parser = new PrattParser($lexer, $this->createMock(Grammar::CLASS));
        $this->assertSame($token, $parser->consume('T_FOO'));
    }

    public function testParserDelegatesDemandToLexer() {
        $token = new Token('T_FOO', 'bar');
        $lexer = $this->createMock(Lexer::CLASS);
        $lexer
            ->expects($this->once())
            ->method('demand')
            ->with('T_FOO', 'kaput')
            ->will($this->returnValue($token));

        $parser = new PrattParser($lexer, $this->createMock(Grammar::CLASS));
        $this->assertSame($token, $parser->demand('T_FOO', 'kaput'));
    }

    public function testExpression() {

        $state = new TokenSet([
            'T_INT' => '\d+',
            'T_ADD' => '\\+',
            'T_MUL' => '\\*'
        ]);

        $grammar = (new GrammarBuilder())
            ->prefix('T_INT', new ValueParser())
            ->infix('T_ADD', new OperationParser('+', 10))
            ->infix('T_MUL', new OperationParser('*', 20))
            ->build();

        $lexer = new Scanner(new TokenStream($state, '1*2+3+4'));
        $parser = new PrattParser($lexer, $grammar);
        $this->assertSame(9, $parser->expression()->value());

        $lexer = new Scanner(new TokenStream($state, '1+2*3+4'));
        $parser = new PrattParser($lexer, $grammar);
        $this->assertSame(11, $parser->expression()->value());

        $lexer = new Scanner(new TokenStream($state, '1+2+3*4'));
        $parser = new PrattParser($lexer, $grammar);
        $this->assertSame(15, $parser->expression()->value());
    }

    public function testToString() {
        $this->assertIsString(
            (string) new PrattParser(
                $this->createMock(Lexer::CLASS),
                $this->createMock(Grammar::CLASS)
            )
        );
    }

}
