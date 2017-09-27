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

namespace Tuleap\Glyph;

class GlyphFinderTest extends \TuleapTestCase
{
    private $tmp_tuleap_dir;

    public function setUp()
    {
        parent::setUp();
        \ForgeConfig::store();
        $this->tmp_tuleap_dir = $this->getTmpDir();
        \ForgeConfig::set('codendi_dir', $this->tmp_tuleap_dir);
    }

    public function tearDown()
    {
        \ForgeConfig::restore();
        parent::tearDown();
    }

    public function itThrowsAnExceptionWhenTheGlyphCanNotBeFound()
    {
        $glyph_finder = new GlyphFinder(mock('EventManager'));

        $this->expectException('Tuleap\\Glyph\\GlyphNotFoundException');

        $glyph_finder->get('does-not-exist');
    }

    public function itFindsAGlyphInCore()
    {
        mkdir($this->tmp_tuleap_dir . '/src/glyphs/', 0777, true);
        file_put_contents($this->tmp_tuleap_dir . '/src/glyphs/test.svg', 'Glyph in core');

        $event_manager = mock('EventManager');
        $event_manager->expectNever('processEvent');

        $glyph_finder = new GlyphFinder($event_manager);
        $glyph = $glyph_finder->get('test');

        $this->assertEqual($glyph->getInlineString(), 'Glyph in core');
    }
}
