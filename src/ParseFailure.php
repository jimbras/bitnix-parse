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

use RuntimeException;

/**
 * @version 0.1.0
 */
class ParseFailure extends RuntimeException {

    /**
     * @var Position
     */
    private Position $position;

    /**
     * @param string $message
     * @param null|Position $position
     */
    public function __construct(string $message, Position $position = null) {
        parent::__construct($message);
        $this->position = $position ?: new Position();
    }

    /**
     * @return Position
     */
    public function getPosition() : Position {
        return $this->position;
    }

    /**
     * @return string
     */
    public function __toString() : string {
        return \trim(
            $this->getMessage() . ' at ' . $this->position->location()
                . \PHP_EOL
                . $this->position->marker(4)
        );
    }
}
