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

use InvalidArgumentException,
    LogicException,
    RuntimeException,
    PHPUnit\Framework\TestCase,
    Bitnix\Parse\Token;

/**
 * @version 0.1.0
 */
final class TokenSetTest extends TestCase {

    public function testTokenReturnsToken() {
        $called = false;
        $shifter = $this->createMock(Shifter::CLASS);
        $tokens = new TokenSet(['T_FOO' => 'foo']);

        $tokens->on('T_FOO', function($s, $v) use ($shifter, &$called) {
            $this->assertSame($shifter, $s);
            $this->assertEquals('foo', $v);
            $called = true;
        });

        $token = $tokens->token($shifter, 'foo is bar', 0);
        $this->assertInstanceOf(Token::CLASS, $token);
        $this->assertEquals('T_FOO', $token->type());
        $this->assertEquals('foo', $token->lexeme());
        $this->assertTrue($called);
    }

    public function testTokenReturnsNull() {
        $tokens = new TokenSet(['T_FOO' => 'foo']);
        $tokens->on('T_FOO', function() {
            $this->fail('Unexpected handler call');
        });
        $shifter = $this->createMock(Shifter::CLASS);
        $this->assertNull($tokens->token($shifter, 'This is foo', 0));
    }

    public function testInvalidListener() {
        $this->expectException(LogicException::CLASS);
        $tokens = new TokenSet(['T_FOO' => 'foo', 'T_BAR' => 'bar']);
        $tokens->on('T_ZOID', function() {});
    }

    public function testInvalidMatch() {
        $this->expectException(RuntimeException::CLASS);
        $tokens = new TokenSet(['T_FOO' => 'foo|fuu', 'T_BAR' => 'bar']);
        $shifter = $this->createMock(Shifter::CLASS);
        $tokens->token($shifter, 'foo', 0);
    }

    public function testInvalidPattern() {
        $this->expectException(InvalidArgumentException::CLASS);
        new TokenSet(['T_FOO' => '(']);
    }

    public function testEmptySetIsNotAllowed() {
        $this->expectException(InvalidArgumentException::CLASS);
        new TokenSet([]);
    }

    public function testToString() {
        $tokens = new TokenSet(['T_FOO' => 'foo']);
        $this->assertIsString((string) $tokens);
    }

}
