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

use Tuleap\Event\Dispatchable;

class GlyphLocationsCollector implements Dispatchable
{
    public const NAME = 'collect_glyph_locations';

    private $glyph_locations = [];

    public function addLocation($namespace_name, GlyphLocation $glyph_location)
    {
        $this->glyph_locations[$namespace_name] = $glyph_location;
    }

    /**
     * @return GlyphLocation|null
     */
    public function getLocation($glyph_name)
    {
        foreach ($this->glyph_locations as $namespace_name => $glyph_location) {
            if (strpos($glyph_name, $namespace_name) === 0) {
                return $glyph_location;
            }
        }
        return null;
    }
}
