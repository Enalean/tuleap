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
use ForgeConfig;
use Mockery;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;

class GlyphFinderTest extends TestCase
{
    use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    /**
     * @var string
     */
    private $tmp_tuleap_dir;

    protected function setUp(): void
    {
        parent::setUp();
        ForgeConfig::store();
        $this->tmp_tuleap_dir = vfsStream::setup()->url();
        ForgeConfig::set('tuleap_dir', $this->tmp_tuleap_dir);
    }

    protected function tearDown(): void
    {
        ForgeConfig::restore();
        parent::tearDown();
    }

    public function testItThrowsAnExceptionWhenTheGlyphCanNotBeFound(): void
    {
        $glyph_finder = new GlyphFinder(Mockery::spy(EventManager::class));

        $this->expectException(GlyphNotFoundException::class);

        $glyph_finder->get('does-not-exist');
    }

    public function testItFindsAGlyphInCore(): void
    {
        mkdir($this->tmp_tuleap_dir . '/src/glyphs/', 0777, true);
        file_put_contents($this->tmp_tuleap_dir . '/src/glyphs/test.svg', 'Glyph in core');

        $event_manager = Mockery::mock(EventManager::class);
        $event_manager->shouldNotReceive('processEvent');

        $glyph_finder = new GlyphFinder($event_manager);
        $glyph        = $glyph_finder->get('test');

        $this->assertEquals($glyph->getInlineString(), 'Glyph in core');
    }
}
