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

use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\CommonMark\Node\Block\FencedCode;
use League\CommonMark\Renderer\NodeRendererInterface;
use Tuleap\Markdown\BlockRenderer\EnhancedCodeBlockRenderer;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class EnhancedCodeBlockExtensionTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testAddsTheEnhancedCodeBlockRendererToTheEnvironmentAndOverridesDefaultCodeBlockRenderer(): void
    {
        $environment = new Environment();
        $environment->addExtension(new CommonMarkCoreExtension());

        $environment->addExtension(new EnhancedCodeBlockExtension(new CodeBlockFeatures()));

        $block_renderers = $environment->getRenderersForClass(FencedCode::class);

        self::assertInstanceOf(EnhancedCodeBlockRenderer::class, self::getFirstElement($block_renderers));
    }

    /**
     * @param iterable<NodeRendererInterface> $block_renderers
     */
    private static function getFirstElement(iterable $block_renderers): ?NodeRendererInterface
    {
        foreach ($block_renderers as $block_renderer) {
            return $block_renderer;
        }

        return null;
    }
}
