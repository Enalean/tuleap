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

class GlyphLocationsCollectorTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testItFindsDeclaredLocation(): void
    {
        $glyph_location_collector = new GlyphLocationsCollector();
        $glyph_location           = $this->createMock(GlyphLocation::class);
        $glyph_location_collector->addLocation('tuleap-git', $glyph_location);
        $glyph_location_collector->addLocation('tuleap-tracker', $glyph_location);

        $found_glyph_location = $glyph_location_collector->getLocation('tuleap-tracker-test');
        self::assertSame($glyph_location, $found_glyph_location);
    }

    public function testItReturnsNullWhenLocationIsNotFound(): void
    {
        $glyph_location_collector = new GlyphLocationsCollector();
        self::assertEquals(null, $glyph_location_collector->getLocation('do-not-exist'));
    }
}
