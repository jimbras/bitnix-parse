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
    Bitnix\Parse\ParseFailure,
    Bitnix\Parse\Parser,
    Bitnix\Parse\Token;

/**
 * @version 0.1.0
 */
final class GrammarBuilder {

    /**
     * @var array
     */
    private array $prefixes = [];

    /**
     * @var array
     */
    private array $infixes = [];

    /**
     * @param string $type
     * @param PrefixParser $parser
     * @return self
     */
    public function prefix(string $type, PrefixParser $parser) : GrammarBuilder {
        $this->prefixes[$type] = $parser;
        return $this;
    }

    /**
     * @param string $type
     * @param InfixParser $parser
     * @return self
     */
    public function infix(string $type, InfixParser $parser) : GrammarBuilder {
        $this->infixes[$type] = $parser;
        return $this;
    }

    /**
     * @param string $type
     * @param MixfixParser $parser
     * @return self
     */
    public function mixfix(string $type, MixfixParser $parser) : GrammarBuilder {
        return $this
            ->prefix($type, $parser)
            ->infix($type, $parser);
    }

    /**
     * @return Grammar
     */
    public function build() : Grammar {

        $grammar = new class($this->prefixes, $this->infixes) implements Grammar {

            private $prefixes = null;
            private $infixes = null;

            public function __construct(array $prefixes, array $infixes) {
                $this->prefixes = $prefixes;
                $this->infixes = $infixes;
            }

            public function precedence(Token $token) : int {
                $type = $token->type();
                if (isset($this->infixes[$type])) {
                    return $this->infixes[$type]->precedence();
                }
                return 0;
            }

            public function prefix(Parser $parser, Token $token) : Expression {
                $type = $token->type();
                if (!isset($this->prefixes[$type])) {
                    $parser->error(\sprintf(
                        'Failed to parse %s token', $type
                    ));
                }
                return $this->prefixes[$type]->parsePrefix($parser, $token);
            }

            public function infix(Parser $parser, Expression $left, Token $token) : Expression {
                $type = $token->type();
                if (!isset($this->infixes[$type])) {
                    $parser->error(\sprintf(
                        'Failed to parse %s token after expression %s',
                            $type,
                            \get_class($left)
                    ));
                }
                return $this->infixes[$type]->parseInfix($parser, $left, $token);
            }

            public function __toString() : string {
                return static::CLASS;
            }
        };

        $this->prefixes = [];
        $this->infixes = [];

        return $grammar;
    }

    /**
     * @return string
     */
    public function __toString() : string {
        return self::CLASS;
    }
}
