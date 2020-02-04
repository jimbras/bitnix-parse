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

use InvalidArgumentException;

/**
 * @version 0.1.0
 */
final class Position {

    /**
     * @var null|string
     */
    private ?string $buffer = null;

    /**
     * @var int
     */
    private int $line = -1;

    /**
     * @var int
     */
    private int $offset = -1;

    /**
     * @var int
     */
    private int $column = -1;

    /**
     * @var int
     */
    private int $marker = -1;

    /**
     * @var string
     */
    private string $eol = '';

    /**
     * @param null|string $buffer
     * @param int $line
     * @param int $offset
     * @throws InvalidArgumentException
     */
    public function __construct(string $buffer = null, int $line = -1, int $offset = -1) {
        if ($buffer !== null) {

            // multiline not allowed
            $lines = \preg_split('~\R~u', $buffer, -1, \PREG_SPLIT_NO_EMPTY);
            if (isset($lines[1])) {
                throw new InvalidArgumentException('Multiline position buffer not allowed');
            }

            // line must be >= 1
            if (1 > $line) {
                throw new InvalidArgumentException('Invalid position line number');
            }

            // offset must exist in buffer
            if (!isset($buffer[$offset])) {
                throw new InvalidArgumentException('Invalid position buffer offset');
            }

            // add newline if needed
            if (!\preg_match('~\R$~u', $buffer)) {
                $this->eol = \PHP_EOL;
            }

            $this->buffer = $buffer;
            $this->line = $line;
            $this->offset = $offset;

            $part = \substr($buffer, 0, $offset);
            $this->column = \mb_strlen($part, 'UTF-8') + 1;
            $this->marker = \mb_strwidth($part, 'UTF-8');
        }
    }

    /**
     * @return bool
     */
    public function known() : bool {
        return null !== $this->buffer;
    }

    /**
     * @return null|string
     */
    public function buffer() : ?string {
        return $this->buffer;
    }

    /**
     * @return int
     */
    public function line() : int {
        return $this->line;
    }

    /**
     * @return int
     */
    public function column() : int {
        return $this->column;
    }

    /**
     * @param int $indent
     * @return string
     */
    private function prefix(int $indent) : string {
        return \str_repeat(' ', \max(0, $indent));
    }

    /**
     * @param int $indent
     * @param string $known
     * @param string $unknown
     * @return string
     */
    public function location(
        int $indent = 0,
        string $known = 'line %d, column %d',
        string $unknown = 'unknown position') : string {

        $prefix = $this->prefix($indent);

        if ($this->known()) {
            return $prefix . \sprintf($known, $this->line, $this->column);
        }

        return $prefix . $unknown;
    }

    /**
     * @param int $indent
     * @param string $pad
     * @param string $marker
     * @return string
     */
    public function marker(int $indent = 0, string $pad = ' ', string $marker = '^') : string {
        $prefix = $this->prefix($indent);

        if ($this->known()) {
            $pad = $pad[0] ?? ' ';
            $marker = $marker[0] ?? '^';
            return $prefix
                . $this->buffer
                . $this->eol
                . $prefix
                . \str_repeat($pad, $this->marker)
                . $marker;
        }

        return $prefix;
    }

    /**
     * @return string
     */
    public function __toString() : string {
        return \trim($this->location() . \PHP_EOL . $this->marker());
    }
}
