<?php
/**
 * Copyright (c) Enalean, 2017 - Present. All Rights Reserved.
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

namespace Tuleap\BotMattermost\SenderServices\MarkdownEngine;

use Tuleap\Templating\TemplateCache;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class MarkdownMustacheRendererTest extends TestCase
{
    public function testItVerifiesThatMarkdownEngineConvertSpecialCharactersIntoHtmlEntities(): void
    {
        $template_cache = $this->createMock(TemplateCache::class);
        $template_cache
            ->expects(self::once())
            ->method('getPath');

        $renderer = new MarkdownMustacheRenderer(
            $template_cache,
            __DIR__ . '/templates'
        );

        $text   = '![my](text)|{~}\*+-._$>`';
        $result = $renderer->renderToString(
            'simple-text',
            ['text' => $text]
        );

        self::assertEquals(
            $result,
            '&#33;&#91;my&#93;&#40;text&#41;&#124;&#123;&#126;&#125;&#92;&#42;&#43;&#45;&#46;&#95;&#36;&#62;&#96;'
        );
    }
}
