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

use League\CommonMark\Block\Element\AbstractBlock;
use League\CommonMark\Block\Element\FencedCode;
use League\CommonMark\Block\Renderer\BlockRendererInterface;
use League\CommonMark\Block\Renderer\FencedCodeRenderer;
use League\CommonMark\ElementRendererInterface;
use League\CommonMark\HtmlElement;
use Tuleap\Markdown\CodeBlockFeaturesInterface;

final class EnhancedCodeBlockRenderer implements BlockRendererInterface
{
    /**
     * @var FencedCodeRenderer
     */
    private $fenced_code_renderer;
    /**
     * @var CodeBlockFeaturesInterface
     */
    private $code_block_features;

    public function __construct(CodeBlockFeaturesInterface $code_block_features, FencedCodeRenderer $fenced_code_renderer)
    {
        $this->fenced_code_renderer = $fenced_code_renderer;
        $this->code_block_features  = $code_block_features;
    }

    /**
     * @return HtmlElement|string|null
     */
    public function render(AbstractBlock $block, ElementRendererInterface $htmlRenderer, bool $inTightList = false)
    {
        if (! ($block instanceof FencedCode)) {
            throw new \InvalidArgumentException('Incompatible block type: ' . \get_class($block));
        }

        $code_block = $this->fenced_code_renderer->render($block, $htmlRenderer, $inTightList);

        $infoWords = $block->getInfoWords();
        if (\count($infoWords) !== 0 && \strlen($infoWords[0]) !== 0) {
            if ($infoWords[0] === 'mermaid') {
                $this->code_block_features->needsMermaid();

                return new HtmlElement('tlp-mermaid-diagram', [], $code_block);
            }

            $this->code_block_features->needsSyntaxHighlight();

            return new HtmlElement('tlp-syntax-highlighting', [], $code_block);
        }


        return $code_block;
    }
}
