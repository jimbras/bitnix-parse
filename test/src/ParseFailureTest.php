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
class ParseFailureTest extends TestCase {

    public function testCustomConstructor() {
        $pos = new Position();
        $x = new ParseFailure('Kaput', $pos);
        $this->assertEquals('Kaput', $x->getMessage());
        $this->assertSame($pos, $x->getPosition());
    }

    public function testDefaultConstructor() {
        $x = new ParseFailure('Kaput');
        $this->assertEquals('Kaput', $x->getMessage());
        $this->assertFalse($x->getPosition()->known());
    }

    public function testToString() {
        $x = new ParseFailure('Kaput');
        $this->assertEquals('Kaput at unknown position', (string) $x);

        $exp = 'Kaput at line 1, column 2' . PHP_EOL
             . '    the buffer' . PHP_EOL
             . '     ^';
        $x = new ParseFailure('Kaput', new Position('the buffer', 1, 1));
        $this->assertEquals($exp, (string) $x);
    }

}
