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
    Throwable,
    Bitnix\Parse\ParseFailure,
    Bitnix\Parse\Position,
    Bitnix\Parse\Token,
    Bitnix\Parse\Tokenizer;

/**
 * @version 0.1.0
 */
final class TokenStream implements Tokenizer, Shifter {

    public const EOS_TOKEN  = 'eos_token';
    public const STACK_SIZE = 'stack_size';

    public const DEFAULT_OPTIONS = [
        self::EOS_TOKEN  => 'T_EOS',
        self::STACK_SIZE => 5
    ];

    /**
     * @var State
     */
    private State $state;

    /**
     * @var resource
     */
    private $stream;

    /**
     * @var bool
     */
    private bool $valid = true;

    /**
     * @var int
     */
    private int $limit = -1;

    /**
     * @var string
     */
    private string $eos;

    /**
     * @var bool
     */
    private bool $skip = false;

    /**
     * @var int
     */
    private int $size = 1;

    /**
     * @var array
     */
    private array $stack = [];

    /**
     * @var null|string
     */
    private ?string $buffer = null;

    /**
     * @var int
     */
    private int $line = 0;

    /**
     * @var int
     */
    private int $offset = -1;

    /**
     * @param State $main
     * @param string|resource $input
     * @param array $options
     * @throws InvalidArgumentException
     * @throws RuntimeException
     */
    public function __construct(State $main, $input, array $options = []) {
        $this->state = $main;
        $this->options($options);
        $this->stream($input);
        $this->read();
    }

    /**
     * ...
     */
    public function __destruct() {
        $this->release();
    }

    /**
     * ...
     */
    private function release() : void {
        if (\is_resource($this->stream)) {
            \fclose($this->stream);
            $this->stream = null;
        }
        $this->valid = false;
    }

    /**
     * @param array $options
     * @throws InvalidArgumentException
     */
    private function options(array $options) : void {
        $options += self::DEFAULT_OPTIONS;

        $limit = $options[self::STACK_SIZE];
        if (!\is_int($limit) || $limit < 1) {
            throw new InvalidArgumentException('Invalid stack size');
        }

        $eos = $options[self::EOS_TOKEN];
        if (!\is_string($eos)) {
            throw new InvalidArgumentException('Invalid end of stream token type');
        }

        $this->limit = $limit;
        $this->eos = $eos;
    }

    /**
     * @param mixed $input
     * @throws InvalidArgumentException
     */
    private function stream($input) : void {

        if (\is_string($input)) {
            $this->stream = \fopen('php://memory', 'wb+');
            \fwrite($this->stream, $input);
            \rewind($this->stream);
            return;
        }

        if (\is_resource($input) && 'stream' === \get_resource_type($input)) {
            $this->stream = $input;
            return;
        }

        throw new InvalidArgumentException(\sprintf(
            'Invalid token stream: string or stream required, got %s',
            \is_object($input) ? \get_class($input) : \gettype($input)
        ));
    }

    /**
     * @return bool
     * @throws RuntimeException
     */
    private function read() : bool {
        $line = @\fgets($this->stream);

        if (false !== $line) {

            $this->buffer = $line;
            ++$this->line;
            $this->offset = 0;

            return true;
        }

        $eos = \feof($this->stream);
        $this->release();

        if (!$eos) {
            throw new RuntimeException('Unable to read from input stream');
        }

        return false;

    }

    /**
     * ...
     */
    public function skip() : void {
        $this->skip = true;
    }

    /**
     * @throws RuntimeException
     */
    public function pop() : void {
        if ($this->size === 1) {
            throw new RuntimeException('Cannot pop a tokenizer state from an empty stack');
        }
        --$this->size;
        $this->state = \array_pop($this->stack);
    }

    /**
     * @param State $state
     * @throws RuntimeException
     */
    public function push(State $state) : void {
        if ($this->size === $this->limit) {
            throw new RuntimeException('Cannot push a tokenizer state into a full stack');
        }
        ++$this->size;
        $this->stack[] = $this->state;
        $this->state = $state;
    }

    /**
     * @return bool
     */
    public function valid() : bool {
        return $this->valid;
    }

    /**
     * @return Token
     * @throws ParseFailure
     */
    public function next() : Token {

        if (!$this->valid) {
            return new Token($this->eos);
        }

        do {

            $this->skip = false;

            if (!isset($this->buffer[$this->offset]) && !$this->read()) {
                return new Token($this->eos);
            }

            $token = $this->state->token($this, $this->buffer, $this->offset);

            if (!$token || 0 === ($bytes = \strlen($token->lexeme()))) {
                $this->error('Unexpected token');
            }

            // may need fixing later on...
            $this->offset += $bytes;

        } while ($this->skip);

        return $token;
    }

    /**
     * @return Position
     */
    public function position() : Position {
        $offset = $this->offset;
        if (null !== $this->buffer) {
            // fix offset if needed...
            while (!isset($this->buffer[$offset])) {
                --$offset;
            }
        }
        return new Position($this->buffer, $this->line, $offset);
    }

    /**
     * @param string $message
     * @throws ParseFailure
     */
    public function error(string $message) : void {
        throw new ParseFailure($message, $this->position());
    }

    /**
     * @return string
     */
    public function __toString() : string {
        return self::CLASS;
    }
}
