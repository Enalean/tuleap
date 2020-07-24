<?php
/**
 * Copyright (c) Enalean, 2017. All Rights Reserved.
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

namespace Tuleap\Templating\Mustache;

use Mustache_Parser;
use Mustache_Tokenizer;
use Tuleap\Language\Gettext\POTEntryCollection;

class GettextExtractor
{
    /**
     * @var Mustache_Parser
     */
    private $parser;
    /**
     * @var Mustache_Tokenizer
     */
    private $tokenizer;
    /**
     * @var GettextCollector
     */
    private $collector;

    private static $GETTEXT_NODE_NAMES = [
        GettextHelper::GETTEXT,
        GettextHelper::NGETTEXT,
        GettextHelper::DGETTEXT,
        GettextHelper::DNGETTEXT
    ];

    public function __construct(
        Mustache_Parser $parser,
        Mustache_Tokenizer $tokenizer,
        GettextCollector $collector
    ) {
        $this->parser    = $parser;
        $this->tokenizer = $tokenizer;
        $this->collector = $collector;
    }

    public function extract($template, POTEntryCollection $collection)
    {
        $tokens = $this->tokenizer->scan($template);
        $tree   = $this->parser->parse($tokens);

        $this->walk($template, $tree, $collection);
    }

    private function walk($template, array $tree, POTEntryCollection $collection)
    {
        foreach ($tree as $node) {
            if (! is_array($node)) {
                continue;
            }

            if ($this->isAGettextSection($node)) {
                $this->collectEntry($template, $node, $collection);
            } elseif ($this->isASectionOrInvertedSection($node)) {
                $this->walk($template, $node[Mustache_Tokenizer::NODES], $collection);
            }
        }
    }

    private function isAGettextSection($node)
    {
        return $this->isASection($node) && $this->isGettext($node);
    }

    private function isASection($node)
    {
        return $node[Mustache_Tokenizer::TYPE] === Mustache_Tokenizer::T_SECTION;
    }

    private function isASectionOrInvertedSection($node)
    {
        return in_array(
            $node[Mustache_Tokenizer::TYPE],
            [Mustache_Tokenizer::T_SECTION, Mustache_Tokenizer::T_INVERTED],
            true
        );
    }

    private function isGettext($node)
    {
        return in_array($node[Mustache_Tokenizer::NAME], self::$GETTEXT_NODE_NAMES, true);
    }

    private function collectEntry($template, $node, POTEntryCollection $collection)
    {
        $content = $this->extractSectionContent($node, $template);

        $this->collector->collectEntry($node[Mustache_Tokenizer::NAME], $content, $collection);
    }

    private function extractSectionContent($node, $template)
    {
        $start = $node[Mustache_Tokenizer::INDEX];
        $end   = $node[Mustache_Tokenizer::END];

        return substr($template, $start, $end - $start);
    }
}
