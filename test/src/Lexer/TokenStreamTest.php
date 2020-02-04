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
    RuntimeException,
    Bitnix\Parse\ParseFailure,
    Bitnix\Parse\Token,
    PHPUnit\Framework\TestCase;

/**
 * ...
 *
 * @version 0.1.0
 */
class TokenStreamTest extends TestCase {

    private State $state;

    public function setUp() : void {
        $this->state = $this->createMock(State::CLASS);
    }

    public function testStack() {
        $this->state
            ->expects($this->once())
            ->method('token')
            ->will($this->returnValue(new Token('T_BAR', 'bar')));

        $stream = new TokenStream($this->state, 'foo bar', [TokenStream::STACK_SIZE => 2]);
        $state = $this->createMock(State::CLASS);
        $state
            ->expects($this->once())
            ->method('token')
            ->will($this->returnValue(new Token('T_FOO', 'foo')));

        try {
            $stream->pop();
            $this->fail('Pop should have failed');
        } catch (RuntimeException $x) {}

        $stream->push($state); // ok

        try {
            $stream->push($state);
            $this->fail('Push should have failed');
        } catch (RuntimeException $x) {}

        // use state in stack
        $this->assertEquals('T_FOO', $stream->next()->type());

        $stream->pop(); // ok

        // use main state
        $this->assertEquals('T_BAR', $stream->next()->type());
    }

    public function testSkip() {
        $this->state
            ->expects($this->once())
            ->method('token')
            ->will($this->returnCallback(function($shifter) {
                $shifter->skip();
                return new Token('T_FOO', 'foo');
            }));

        $stream = new TokenStream($this->state, 'foo', [TokenStream::EOS_TOKEN => 'T_END']);
        $token = $stream->next();
        $this->assertEquals('T_END', $token->type());
    }

    public function testNextParseFailureOnEmptyTokenValue() {
        $this->expectException(ParseFailure::CLASS);
        $this->state
            ->expects($this->once())
            ->method('token')
            ->will($this->returnValue(new Token('T_KAPUT')));

        $stream = new TokenStream($this->state, 'foo');
        $stream->next();
    }

    public function testNextParseFailureOnNull() {
        $this->expectException(ParseFailure::CLASS);
        $this->state
            ->expects($this->once())
            ->method('token')
            ->will($this->returnValue(null));

        $stream = new TokenStream($this->state, 'foo');
        $stream->next();
    }

    public function testNext() {
        $stream = null;
        $tokens = [
            [0, new Token('T_FOO', 'foo')],
            [3, new Token('T_SPACE', ' ')],
            [4, new Token('T_BAR', 'bar')],
            [7, new Token('T_EOL', PHP_EOL)]
        ];

        $this->state
            ->expects($this->exactly(4))
            ->method('token')
            ->will($this->returnCallback(function($shifter, $buffer, $offset) use (&$tokens, &$stream) {
                $this->assertSame($stream, $shifter);
                $this->assertEquals('foo bar' . PHP_EOL, $buffer);
                $info = \array_shift($tokens);
                $this->assertEquals($info[0], $offset);
                return $info[1];
            }));

        $input = 'foo bar' . PHP_EOL;
        $stream = new TokenStream($this->state, $input);
        $this->assertTrue($stream->valid());
        $pos = $stream->position();
        $this->assertEquals($input, $pos->buffer());
        $this->assertEquals(1, $pos->line());
        $this->assertEquals(1, $pos->column());

        // 1
        $token = $stream->next();
        $this->assertEquals('T_FOO', $token->type());
        $pos = $stream->position();
        $this->assertEquals($input, $pos->buffer());
        $this->assertEquals(1, $pos->line());
        $this->assertEquals(4, $pos->column());
        $this->assertTrue($stream->valid());

        // 2
        $token = $stream->next();
        $this->assertEquals('T_SPACE', $token->type());
        $pos = $stream->position();
        $this->assertEquals($input, $pos->buffer());
        $this->assertEquals(1, $pos->line());
        $this->assertEquals(5, $pos->column());
        $this->assertTrue($stream->valid());

        // 3
        $token = $stream->next();
        $this->assertEquals('T_BAR', $token->type());
        $pos = $stream->position();
        $this->assertEquals($input, $pos->buffer());
        $this->assertEquals(1, $pos->line());
        $this->assertEquals(8, $pos->column());
        $this->assertTrue($stream->valid());

        // 4
        $token = $stream->next();
        $this->assertEquals('T_EOL', $token->type());
        $pos = $stream->position();
        $this->assertEquals($input, $pos->buffer());
        $this->assertEquals(1, $pos->line());
        $this->assertEquals(8, $pos->column());
        $this->assertTrue($stream->valid());

        // should not call state to fetch token
        $token = $stream->next();
        $this->assertEquals('T_EOS', $token->type());
        $pos = $stream->position();
        $this->assertEquals($input, $pos->buffer());
        $this->assertEquals(1, $pos->line());
        $this->assertEquals(8, $pos->column());
        $this->assertFalse($stream->valid());

        // should not call state to fetch token
        $token = $stream->next();
        $this->assertEquals('T_EOS', $token->type());
        $pos = $stream->position();
        $this->assertEquals($input, $pos->buffer());
        $this->assertEquals(1, $pos->line());
        $this->assertEquals(8, $pos->column());
        $this->assertFalse($stream->valid());
    }

    public function testReadError() {
        $this->expectException(RuntimeException::CLASS);
        $fp = \fopen(__DIR__ . '/_stream/error.txt', 'wb');
        try {
            $stream = new TokenStream($this->state, $fp);
        } finally {
            @\fclose($fp);
        }
    }

    public function testStreamFromResource() {
        $fp = \fopen('php://memory', 'wb+');
        \fwrite($fp, 'foo');
        \rewind($fp);

        try {
            $stream = new TokenStream($this->state, $fp);
            $this->assertTrue($stream->valid());
            $this->assertTrue(\is_resource($fp));
        } finally {
            @\fclose($fp);
        }

        $fp = \fopen('php://memory', 'wb+');

        try {
            $stream = new TokenStream($this->state, $fp);
            $this->assertFalse($stream->valid());
            $this->assertFalse(\is_resource($fp));
        } finally {
            @\fclose($fp);
        }
    }

    public function testStreamFromString() {
        $stream = new TokenStream($this->state, 'foo');
        $this->assertTrue($stream->valid());

        $stream = new TokenStream($this->state, '');
        $this->assertFalse($stream->valid());
    }

    public function testInvalidInputStream() {
        $this->expectException(InvalidArgumentException::CLASS);
        new TokenStream($this->state, $this);
    }

    /** @dataProvider badEosToken */
    public function testInvalidEOSToken($type) {
        $this->expectException(InvalidArgumentException::CLASS);
        new TokenStream($this->state, 'foo', [
            TokenStream::EOS_TOKEN => $type
        ]);
    }

    public function badEosToken() : array {
        return [
            [null],
            [$this],
            [1],
            [1.2],
            [[]]
        ];
    }

    /** @dataProvider badStackSize */
    public function testInvalidStackSize($size) {
        $this->expectException(InvalidArgumentException::CLASS);
        new TokenStream($this->state, 'foo', [
            TokenStream::STACK_SIZE => $size
        ]);
    }

    public function badStackSize() : array {
        return [
            ['1'],
            [0],
            [-1]
        ];
    }

    public function testToString() {
        $this->assertIsString((string) new TokenStream($this->state, 'foo'));
    }
}
