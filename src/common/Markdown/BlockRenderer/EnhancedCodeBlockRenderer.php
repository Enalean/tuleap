<?php
/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\Markdown\BlockRenderer;

use League\CommonMark\Extension\CommonMark\Node\Block\FencedCode;
use League\CommonMark\Extension\CommonMark\Renderer\Block\FencedCodeRenderer;
use League\CommonMark\Node\Node;
use League\CommonMark\Renderer\ChildNodeRendererInterface;
use League\CommonMark\Renderer\NodeRendererInterface;
use League\CommonMark\Util\HtmlElement;
use Tuleap\Markdown\CodeBlockFeaturesInterface;

final class EnhancedCodeBlockRenderer implements NodeRendererInterface
{
    private FencedCodeRenderer $fenced_code_renderer;
    private CodeBlockFeaturesInterface $code_block_features;

    public function __construct(CodeBlockFeaturesInterface $code_block_features, FencedCodeRenderer $fenced_code_renderer)
    {
        $this->fenced_code_renderer = $fenced_code_renderer;
        $this->code_block_features  = $code_block_features;
    }

    #[\Override]
    public function render(Node $node, ChildNodeRendererInterface $childRenderer)
    {
        if (! ($node instanceof FencedCode)) {
            throw new \InvalidArgumentException('Incompatible block type: ' . $node::class);
        }

        $code_block = $this->fenced_code_renderer->render($node, $childRenderer);

        $infoWords = $node->getInfoWords();
        if (\count($infoWords) !== 0 && \strlen($infoWords[0]) !== 0) {
            if ($infoWords[0] === 'mermaid') {
                $this->code_block_features->needsMermaid();

                return new HtmlElement('tlp-mermaid-diagram', [], (string) $code_block);
            }

            $this->code_block_features->needsSyntaxHighlight();

            return new HtmlElement('tlp-syntax-highlighting', [], (string) $code_block);
        }


        return $code_block;
    }
}
