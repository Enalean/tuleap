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

declare(strict_types=1);

namespace Tuleap\Glyph;

use EventManager;
use org\bovigo\vfs\vfsStream;
use Tuleap\ForgeConfigSandbox;

class GlyphFinderTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use ForgeConfigSandbox;

    public function testItThrowsAnExceptionWhenTheGlyphCanNotBeFound(): void
    {
        $event_manager = $this->createMock(EventManager::class);
        $event_manager->method('processEvent');
        $glyph_finder = new GlyphFinder($event_manager);

        self::expectException(GlyphNotFoundException::class);

        $glyph_finder->get('does-not-exist');
    }

    public function testItFindsAGlyphInCore(): void
    {
        $event_manager = $this->createMock(EventManager::class);
        $event_manager->expects(self::never())->method('processEvent');

        $glyph_finder = new GlyphFinder($event_manager);
        $glyph        = $glyph_finder->get('scrum');

        self::assertStringStartsWith('<svg', $glyph->getInlineString());
    }

    public function testItFindsGlyphsInCustomImagesDirectory(): void
    {
        $images_path = vfsStream::setup('/')->url();
        \ForgeConfig::set('sys_data_dir', $images_path);
        mkdir($images_path . '/images');
        file_put_contents($images_path . '/images/organization_logo.svg', '<svg></svg>');

        $event_manager = $this->createMock(EventManager::class);
        $event_manager->expects(self::never())->method('processEvent');

        $glyph_finder = new GlyphFinder($event_manager);
        $glyph        = $glyph_finder->get('organization_logo');

        self::assertStringStartsWith('<svg', $glyph->getInlineString());
    }
}
