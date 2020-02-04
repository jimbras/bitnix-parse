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

/**
 * @version 0.1.0
 */
interface Lexer extends Tokenizer {

    /**
     * @param int $dist
     * @return Token
     * @throws ParseFailure
     */
    public function peek(int $dist = 0) : Token;

    /**
     * @param string ...$types
     * @return bool
     */
    public function match(string ...$types) : bool;

    /**
     * @param string ...$types
     * @return null|Token
     * @throws ParseFailure
     */
    public function consume(string ...$types) : ?Token;

    /**
     * @param string $type
     * @param null|string $message
     * @return Token
     * @throws ParseFailure
     */
    public function demand(string $type, string $message = null) : Token;

}
