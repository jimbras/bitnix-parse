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
    Bitnix\Parse\Lexer\Scanner;

/**
 * @version 0.1.0
 */
final class PrattParser implements Parser {

    /**
     * @var Grammar
     */
    private Grammar $grammar;

    /**
     * @var Lexer
     */
    private Lexer $lexer;

    /**
     * @param Lexer $lexer
     * @param Grammar $grammar
     */
    public function __construct(Lexer $lexer, Grammar $grammar) {
        $this->lexer = $lexer;
        $this->grammar = $grammar;
    }

    /**
     * @return bool
     */
    public function valid() : bool {
        return $this->lexer->valid();
    }

    /**
     * @return Token
     * @throws ParseFailure
     */
    public function next() : Token {
        return $this->lexer->next();
    }

    /**
     * @return Position
     */
    public function position() : Position {
        return $this->lexer->position();
    }

    /**
     * @param string $message
     * @throws ParseFailure
     */
    public function error(string $message) : void {
        $this->lexer->error($message);
    }

    /**
     * @param int $dist
     * @return Token
     * @throws ParseFailure
     */
    public function peek(int $dist = 0) : Token {
        return $this->lexer->peek($dist);
    }

    /**
     * @param string ...$types
     * @return bool
     */
    public function match(string ...$types) : bool {
        return $this->lexer->match(...$types);
    }

    /**
     * @param string ...$types
     * @return null|Token
     * @throws ParseFailure
     */
    public function consume(string ...$types) : ?Token {
        return $this->lexer->consume(...$types);
    }

    /**
     * @param string $type
     * @param null|string $message
     * @return Token
     * @throws ParseFailure
     */
    public function demand(string $type, string $message = null) : Token {
        return $this->lexer->demand($type, $message);
    }

    /**
     * @param int $precedence
     * @return Expression
     * @throws ParseFailure
     */
    public function expression(int $precedence = 0) : Expression {
        $left = $this->grammar->prefix($this, $this->lexer->next());
        while ($precedence < $this->grammar->precedence($this->lexer->peek())) {
            $left = $this->grammar->infix($this, $left, $this->lexer->next());
        }
        return $left;
    }

    /**
     * @return string
     */
    public function __toString() : string {
        return self::CLASS;
    }
}
