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

namespace Bitnix\Parse\Lexer;

use Bitnix\Parse\ParseFailure,
    Bitnix\Parse\Token,
    PHPUnit\Framework\TestCase;

/**
 * ...
 *
 * @version 0.1.0
 */
class ScannerTest extends TestCase {

    private ?Scanner $lexer;

    public function setUp() : void {
        $state = new TokenSet([
            'T_WS'  => '\s+',
            'T_INT' => '\d+',
            'T_STR' => '[a-zA-z]+'
        ]);
        $state->on('T_WS', fn($shifter) => $shifter->skip());
        $this->lexer = new Scanner(new TokenStream($state, '1 2 3 four'));
    }

    public function tearDown() : void {
        $this->lexer = null;
    }

    public function testDemandError() {
        $this->expectException(ParseFailure::CLASS);
        $this->lexer->demand('T_EOS');
    }

    public function testDemand() {
        $this->assertEquals('1', $this->lexer->demand('T_INT')->lexeme());
        // peek after end of stream
        $this->assertEquals('T_EOS', $this->lexer->peek(10)->type());
        $this->assertEquals('2', $this->lexer->demand('T_INT')->lexeme());
        $this->assertEquals('3', $this->lexer->demand('T_INT')->lexeme());
        $this->assertEquals('four', $this->lexer->demand('T_STR')->lexeme());
        $this->assertEquals('', $this->lexer->demand('T_EOS')->lexeme());
    }

    public function testConsume() {
        $this->assertNull($this->lexer->consume('T_EOS'));
        $this->assertEquals('1', $this->lexer->consume('T_INT')->lexeme());
        $this->assertEquals('2', $this->lexer->consume('T_STR', 'T_INT')->lexeme());

        // peek after end of stream
        $this->assertEquals('T_EOS', $this->lexer->peek(10)->type());
        $this->assertNull($this->lexer->consume('T_STR'));
        $this->assertEquals('3', $this->lexer->consume('T_INT')->lexeme());
        $this->assertEquals('four', $this->lexer->consume('T_INT', 'T_STR')->lexeme());
    }

    public function testMatch() {
        $this->assertFalse($this->lexer->match('T_EOS'));
        $this->assertTrue($this->lexer->match('T_INT'));

        // peek after end of stream
        $this->assertEquals('T_EOS', $this->lexer->peek(10)->type());
        $this->assertFalse($this->lexer->match('T_EOS'));
        $this->assertTrue($this->lexer->match('T_INT'));
    }

    public function testNextWithPeek() {
        $tokens = [];

        $expected = [
            new Token('T_INT', '1'),
            new Token('T_INT', '2'),
            new Token('T_INT', '3'),
            new Token('T_STR', 'four'),
            new Token('T_EOS', '')
        ];

        $columns = [1, 2, 4, 6, 10];

        $this->assertEquals($expected[0], $this->lexer->peek(0));
        $this->assertEquals($expected[1], $this->lexer->peek(1));
        $this->assertEquals($expected[2], $this->lexer->peek(2));
        $this->assertEquals($expected[3], $this->lexer->peek(3));
        $this->assertEquals($expected[4], $this->lexer->peek(4));

        // peek after end of stream
        $this->assertEquals($expected[4], $this->lexer->peek(10));

        while ($this->lexer->valid()) {
            $this->assertEquals(\array_shift($columns), $this->lexer->position()->column());
            //echo $this->lexer->position(), PHP_EOL;
            $tokens[] = $this->lexer->next();
        }

        $this->assertEquals($expected, $tokens);
    }

    public function testNext() {
        $tokens = [];

        $expected = [
            new Token('T_INT', '1'),
            new Token('T_INT', '2'),
            new Token('T_INT', '3'),
            new Token('T_STR', 'four'),
            new Token('T_EOS', '')
        ];

        $columns = [1, 2, 4, 6, 10];

        while ($this->lexer->valid()) {
            $this->assertEquals(\array_shift($columns), $this->lexer->position()->column());
            $tokens[] = $this->lexer->next();
        }

        $this->assertEquals($expected, $tokens);
    }

    public function testToString() {
        $this->assertIsString((string) $this->lexer);
    }
}
