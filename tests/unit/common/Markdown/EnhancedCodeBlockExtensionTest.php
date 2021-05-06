<?php
/**
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\Markdown;

use League\CommonMark\Block\Element\FencedCode;
use League\CommonMark\Block\Renderer\BlockRendererInterface;
use League\CommonMark\Environment;
use Tuleap\Markdown\BlockRenderer\EnhancedCodeBlockRenderer;

final class EnhancedCodeBlockExtensionTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testAddsTheEnhancedCodeBlockRendererToTheEnvironmentAndOverridesDefaultCodeBlockRenderer(): void
    {
        $environment = Environment::createCommonMarkEnvironment();

        $environment->addExtension(new EnhancedCodeBlockExtension(new CodeBlockFeatures()));

        $block_renderers = $environment->getBlockRenderersForClass(FencedCode::class);

        self::assertInstanceOf(EnhancedCodeBlockRenderer::class, self::getFirstElement($block_renderers));
    }

    /**
     * @param iterable<BlockRendererInterface> $block_renderers
     */
    private static function getFirstElement(iterable $block_renderers): ?BlockRendererInterface
    {
        foreach ($block_renderers as $block_renderer) {
            return $block_renderer;
        }

        return null;
    }
}
