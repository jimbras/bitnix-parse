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
final class Token {

    /**
     * @var string
     */
    private string $type;

    /**
     * @var string
     */
    private ?string $lexeme = null;

    /**
     * @param string $type
     * @param string $lexeme
     */
    public function __construct(string $type, string $lexeme = '') {
        $this->type = $type;
        $this->lexeme = $lexeme;
    }

    /**
     * @return string
     */
    public function type() : string {
        return $this->type;
    }

    /**
     * @return null|string
     */
    public function lexeme() : ?string {
        return $this->lexeme;
    }

    /**
     * @return string
     */
    public function __toString() : string {
        return \sprintf('%s (%s)', $this->type, $this->lexeme);
    }
}
