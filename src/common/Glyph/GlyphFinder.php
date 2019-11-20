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

use Tuleap\URI\URIModifier;

class GlyphFinder
{
    /**
     * @var \EventManager
     */
    private $event_manager;

    public function __construct(\EventManager $event_manager)
    {
        $this->event_manager = $event_manager;
    }

    /**
     * @return Glyph
     * @throws \Tuleap\Glyph\GlyphNotFoundException
     */
    public function get($name)
    {
        static $symbol_cache = array();

        if (isset($symbol_cache[$name])) {
            return $symbol_cache[$name];
        }

        $glyph = $this->getCoreGlyph($name);
        if ($glyph === null) {
            $glyph_locations_collector = new GlyphLocationsCollector();
            $this->event_manager->processEvent($glyph_locations_collector);
            $glyph_location = $glyph_locations_collector->getLocation($name);
            if ($glyph_location !== null) {
                $glyph = $this->readGlyph($glyph_location, $name);
            }
        }

        if ($glyph === null) {
            throw new GlyphNotFoundException();
        }

        $symbol_cache[$name] = $glyph;
        return $glyph;
    }

    /**
     * @return null|Glyph
     */
    private function getCoreGlyph($name)
    {
        $core_glyphs_location = new GlyphLocation(__DIR__ . '/../../glyphs');
        return $this->readGlyph($core_glyphs_location, $name);
    }

    /**
     * @return null|Glyph
     */
    private function readGlyph(GlyphLocation $location, $name)
    {
        $svg_file_path     = $location->getPath() . $name . '.svg';
        $svg_purified_path = URIModifier::removeDotSegments(
            URIModifier::removeEmptySegments($svg_file_path)
        );
        if ($svg_file_path !== $svg_purified_path || ! is_file($svg_purified_path)) {
            return null;
        }
        $svg_content = file_get_contents($svg_purified_path);
        if ($svg_content !== false) {
            return new Glyph($svg_content);
        }

        return null;
    }
}
