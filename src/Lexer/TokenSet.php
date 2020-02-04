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
    LogicException,
    RuntimeException,
    Bitnix\Parse\Token;

/**
 * @version 0.1.0
 */
final class TokenSet implements State {

    /**
     * @var string
     */
    private string $regex;

    /**
     * @var array
     */
    private array $handlers = [];

    /**
     * @param array $patterns
     * @throws \InvalidArgumentException
     */
    public function __construct(array $patterns) {
        if (empty($patterns)) {
            throw new InvalidArgumentException('No token patterns to compile');
        }

        $fn = function() {};

        $marked = \array_map(function($matcher, $type) use($fn) {
            $this->handlers[$type] = $fn;
            return \str_replace('~', '\\~', $matcher) . '(*MARK:' . $type . ')';
        }, $patterns, \array_keys($patterns));

        $regex = '~(' . \implode(')|(', \array_values($marked)) . ')~Au';

        if (false === (@\preg_match($regex, ''))) {
            throw new InvalidArgumentException(\sprintf(
                'Invalid token set (%s) compiled pattern',
                \implode(', ', \array_keys($patterns))
            ));
        }

        $this->regex = $regex;
    }

    /**
     * @param string $type
     * @param callable $handler
     * @return self
     * @throws LogicException
     */
    public function on(string $type, callable $handler) : TokenSet {
        if (!isset($this->handlers[$type])) {
            throw new LogicException(sprintf(
                'Unknown token type %s in set (%s)',
                $type, \implode(', ', \array_keys($this->handlers))
            ));
        }
        $this->handlers[$type] = $handler;
        return $this;
    }

    /**
     * @param Shifter $shifter
     * @param string $buffer
     * @param int $offset
     * @return null|Token
     * @throws RuntimeException
     */
    public function token(Shifter $shifter, string $buffer, int $offset) : ?Token {
        if (!\preg_match($this->regex, $buffer, $matches, 0, $offset)) {
            return null;
        }

        if (!isset($matches['MARK'])) {
            throw new RuntimeException(\sprintf(
                'Unable to determine token type from token set (%s)',
                \implode(', ', \array_keys($this->handlers))
            ));
        }

        $type = $matches['MARK'];
        $value = $matches[0];

        $this->handlers[$type]($shifter, $value);

        return new Token($type, $value);
    }

    /**
     * @return string
     */
    public function __toString() : string {
        return self::CLASS;
    }
}
