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

namespace Bitnix\Parse;

use PHPUnit\Framework\TestCase;

/**
 * @version 0.1.0
 */
class TokenTest extends TestCase {

    public function testFullConstructor() {
        $token = new Token('T_FOO', 'foo');
        $this->assertEquals('T_FOO', $token->type());
        $this->assertEquals('foo', $token->lexeme());
    }

    public function testDefaultConstructor() {
        $token = new Token('T_FOO');
        $this->assertEquals('T_FOO', $token->type());
        $this->assertEquals('', $token->lexeme());
    }

    public function testToString() {
        $this->assertIsString((string) new Token('T_FOO'));
    }

}
