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

use Bitnix\Parse\Lexer,
    Bitnix\Parse\ParseFailure,
    Bitnix\Parse\Position,
    Bitnix\Parse\Token,
    Bitnix\Parse\Tokenizer;

/**
 * @version 0.1.0
 */
final class Scanner implements Lexer {

    /**
     * @var Tokenizer;
     */
    private Tokenizer $tokenizer;

    /**
     * @var array
     */
    private array $tokens = [];

    /**
     * @var int
     */
    private int $buffer = 0;

    /**
     * @param Tokenizer $tokenizer
     */
    public function __construct(Tokenizer $tokenizer) {
        $this->tokenizer = $tokenizer;
    }

    /**
     * ...
     */
    public function __destruct() {
        unset($this->tokenizer);
    }

    /**
     * @return bool
     */
    public function valid() : bool {
        if ($this->buffer) {
            return $this->tokens[0][0];
        }
        return $this->tokenizer->valid();
    }

    /**
     * @return Token
     * @throws ParseFailure
     */
    public function next() : Token {
        if ($this->buffer) {
            --$this->buffer;
            return \array_shift($this->tokens)[2];
        }
        return $this->tokenizer->next();
    }

    /**
     * @return Position
     */
    public function position() : Position {
        if ($this->buffer) {
            return $this->tokens[0][1];
        }
        return $this->tokenizer->position();
    }

    /**
     * @param int $dist
     * @return Token
     * @throws ParseFailure
     */
    public function peek(int $dist = 0) : Token {
        $dist = \max(0, $dist);

        while ($dist >= $this->buffer) {
            ++$this->buffer;
            $this->tokens[] = [
                $this->tokenizer->valid(),
                $this->tokenizer->position(),
                $this->tokenizer->next()
            ];
        }

        return $this->tokens[$dist][2];
    }

    /**
     * @param string $message
     * @throws ParseFailure
     */
    public function error(string $message) : void {
        $this->tokenizer->error($message);
    }

    /**
     * @param string ...$types
     * @return bool
     */
    public function match(string ...$types) : bool {
        $token = $this->peek()->type();
        foreach ($types as $type) {
            if ($type === $token) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param string ...$types
     * @return null|Token
     * @throws ParseFailure
     */
    public function consume(string ...$types) : ?Token {
        return $this->match(...$types) ? $this->next() : null;
    }

    /**
     * @param string $type
     * @param null|string $message
     * @return Token
     * @throws ParseFailure
     */
    public function demand(string $type, string $message = null) : Token {

        if (null !== ($token = $this->consume($type))) {
            return $token;
        }

        if (null === $message) {
            $message = 'Expecting %1$s, but got %2$s';
        }

        throw $this->error(\sprintf(
            $message,
                $type,
                $this->peek()->type()
        ));
    }

    /**
     * @return string
     */
    public function __toString() : string {
        return static::CLASS;
    }

}
