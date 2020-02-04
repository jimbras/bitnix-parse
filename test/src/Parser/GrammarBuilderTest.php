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
    Bitnix\Parse\ParseFailure,
    Bitnix\Parse\Parser,
    Bitnix\Parse\Position,
    Bitnix\Parse\Token,
    PHPUnit\Framework\TestCase;

/**
 * ...
 *
 * @version 0.1.0
 */
class GrammarBuilderTest extends TestCase {

    private $builder = null;

    public function setUp() : void {
        $this->builder = new GrammarBuilder();
    }

    public function testPrecedence() {
        $parser = $this->createMock(Parser::CLASS);
        $left = $this->createMock(Expression::CLASS);
        $infix = $this->createMock(InfixParser::CLASS);
        $infix->expects($this->once())
            ->method('precedence')
            ->will($this->returnValue(123));

        $foo = new Token('T_FOO', 'foo');
        $bar = new Token('T_BAR', 'bar');

        $grammar = $this->builder
            ->infix('T_FOO', $infix)
            ->build();

        $this->assertEquals(0, $grammar->precedence($bar));
        $this->assertEquals(123, $grammar->precedence($foo));
    }

    public function testMixfixParser() {
        $parser = $this->createMock(Parser::CLASS);
        $mixfix = $this->createMock(MixfixParser::CLASS);
        $left = $this->createMock(Expression::CLASS);
        $pexpr = $this->createMock(Expression::CLASS);
        $iexpr = $this->createMock(Expression::CLASS);
        $token = new Token('T_FOO', 'foo');

        $mixfix->expects($this->once())
            ->method('parsePrefix')
            ->with($parser, $token)
            ->will($this->returnValue($pexpr));

        $mixfix->expects($this->once())
            ->method('parseInfix')
            ->with($parser, $left, $token)
            ->will($this->returnValue($iexpr));

        $grammar = $this->builder
            ->mixfix('T_FOO', $mixfix)
            ->build();

        $this->assertSame($pexpr, $grammar->prefix($parser, $token));
        $this->assertSame($iexpr, $grammar->infix($parser, $left, $token));
    }

    public function testInfixParser() {
        $parser = $this->createMock(Parser::CLASS);
        $infix = $this->createMock(InfixParser::CLASS);
        $left = $this->createMock(Expression::CLASS);
        $result = $this->createMock(Expression::CLASS);
        $token = new Token('T_FOO', 'foo');

        $infix->expects($this->once())
            ->method('parseInfix')
            ->with($parser, $left, $token)
            ->will($this->returnValue($result));

        $grammar = $this->builder
            ->infix('T_FOO', $infix)
            ->build();

        $this->assertSame($result, $grammar->infix($parser, $left, $token));
    }

    public function testPrefixParser() {
        $parser = $this->createMock(Parser::CLASS);
        $prefix = $this->createMock(PrefixParser::CLASS);
        $expression = $this->createMock(Expression::CLASS);
        $token = new Token('T_FOO', 'foo');

        $prefix->expects($this->once())
            ->method('parsePrefix')
            ->with($parser, $token)
            ->will($this->returnValue($expression));

        $grammar = $this->builder
            ->prefix('T_FOO', $prefix)
            ->build();

        $this->assertSame($expression, $grammar->prefix($parser, $token));
    }

    public function testInfixParserError() {
        $this->expectException(ParseFailure::CLASS);
        $parser = $this->createMock(Parser::CLASS);
        $left = $this->createMock(Expression::CLASS);
        $parser->expects($this->once())
            ->method('error')
            ->will($this->throwException(new ParseFailure('kaput')));
        $token = new Token('T_FOO', 'foo');
        $grammar = $this->builder->build();
        $grammar->infix($parser, $left, $token);
    }

    public function testPrefixParserError() {
        $this->expectException(ParseFailure::CLASS);
        $parser = $this->createMock(Parser::CLASS);
        $parser->expects($this->once())
            ->method('error')
            ->will($this->throwException(new ParseFailure('kaput')));
        $token = new Token('T_FOO', 'foo');
        $grammar = $this->builder->build();
        $grammar->prefix($parser, $token);
    }

    public function testBuildReturnsGrammar() {
        $g1 = $this->builder->build();
        $g2 = $this->builder->build();
        $this->assertInstanceOf(Grammar::CLASS, $g1);
        $this->assertInstanceOf(Grammar::CLASS, $g2);
        $this->assertNotSame($g1, $g2);
    }

    public function testGrammarToString() {
        $this->assertIsString((string) $this->builder->build());
    }

    public function testToString() {
        $this->assertIsString((string) $this->builder);
    }
}
