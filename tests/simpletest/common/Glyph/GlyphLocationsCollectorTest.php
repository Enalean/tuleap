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

class GlyphLocationsCollectorTest extends \TuleapTestCase
{
    public function itFindsDeclaredLocation()
    {
        $glyph_location_collector = new GlyphLocationsCollector();
        $glyph_location_collector->addLocation('tuleap-git', mock('Tuleap\\Glyph\\GlyphLocation'));
        $glyph_location = mock('Tuleap\\Glyph\\GlyphLocation');
        $glyph_location_collector->addLocation('tuleap-tracker', $glyph_location);

        $found_glyph_location = $glyph_location_collector->getLocation('tuleap-tracker-test');
        $this->assertIdentical($glyph_location, $found_glyph_location);
    }

    public function itReturnsNullWhenLocationIsNotFound()
    {
        $glyph_location_collector = new GlyphLocationsCollector();
        $this->assertEqual(null, $glyph_location_collector->getLocation('do-not-exist'));
    }
}
