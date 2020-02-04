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

use InvalidArgumentException,
    PHPUnit\Framework\TestCase;

/**
 * @version 0.1.0
 */
class PositionTest extends TestCase {

    public function testUnkownPositionMarker() {
        $pos = new Position();
        $this->assertEquals('', $pos->marker());
        $this->assertEquals('  ', $pos->marker(2));
    }

    public function testKnownPositionLocation() {
        $pos = new Position('Hello', 1, 1);
        $this->assertEquals('line 1, column 2', $pos->location());
        $this->assertEquals('  line 1, column 2', $pos->location(2));

        $this->assertEquals('[1:2]', $pos->location(0, '[%d:%d]'));
        $this->assertEquals('  [1:2]', $pos->location(2, '[%d:%d]'));
    }

    public function testUnkownPositionLocation() {
        $pos = new Position();
        $this->assertEquals('unknown position', $pos->location());
        $this->assertEquals('  unknown position', $pos->location(2));

        $this->assertEquals('???', $pos->location(0, '[%d:%d]', '???'));
        $this->assertEquals('  ???', $pos->location(2, '[%d:%d]', '???'));
    }

    public function testKnownPosition() {
        $pos = new Position('Hello', 1, 1);

        $this->assertTrue($pos->known());
        $this->assertEquals('Hello', $pos->buffer());
        $this->assertEquals(1, $pos->line());
        $this->assertEquals(2, $pos->column());
    }

    public function testUnkownPosition() {
        $pos = new Position();

        $this->assertFalse($pos->known());
        $this->assertNull($pos->buffer());
        $this->assertEquals(-1, $pos->line());
        $this->assertEquals(-1, $pos->column());
    }

    public function testKownPositionMarker() {
        $pos = new Position('Hello', 1, 1);

        $exp = 'Hello' . PHP_EOL
             . ' ^';
        $this->assertEquals($exp, $pos->marker());

        $exp = '  Hello' . PHP_EOL
             . '   ^';
        $this->assertEquals($exp, $pos->marker(2));
    }

    public function testMultibytePosition() {
        $buffer = "Say: ご飯が熱い。 Gohan ga atsui. (The rice is hot.)\n";
        $marker = "       ^";
        $offset = \strpos($buffer, '飯');

        $pos = new Position($buffer, 1, $offset);
        $this->assertEquals($buffer, $pos->buffer());
        $this->assertEquals(1, $pos->line());
        $this->assertEquals(7, $pos->column());
        $this->assertEquals('line 1, column 7', $pos->location());
        $this->assertEquals($buffer . $marker, $pos->marker());

        $buffer = "Say: ご飯が熱い。 Gohan ga atsui. (The rice is hot.)\n";
      $marker = "                  ^"; // weird font spacing!!!
        $offset = \strpos($buffer, 'G');

        $pos = new Position($buffer, 1, $offset);
        $this->assertEquals($buffer, $pos->buffer());
        $this->assertEquals(1, $pos->line());
        $this->assertEquals(13, $pos->column());
        $this->assertEquals('line 1, column 13', $pos->location());
        $this->assertEquals($buffer . $marker, $pos->marker());
    }

    public function testInvalidOffset() {
        $this->expectException(InvalidArgumentException::CLASS);
        new Position("foo\n", 1, 4);
    }

    public function testInvalidLine() {
        $this->expectException(InvalidArgumentException::CLASS);
        new Position("foo\n", 0);
    }

    public function testInvalidBuffer() {
        $this->expectException(InvalidArgumentException::CLASS);
        new Position("foo\nbar");
    }

    public function testToString() {
        $this->assertIsString((string) new Position());
    }
}
