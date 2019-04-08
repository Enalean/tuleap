<?php
/**
 * Copyright (c) Enalean, 2014. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

class Tracker_ColorPresenterCollection implements Iterator {

    /** @var array */
    private $colors = array();

    /** @var Tracker */
    private $tracker;

    /** @var array() */
    private $existing_colors = array(
       'inca-silver',
       'chrome-silver',
       'firemist-silver',
       'red-wine',
       'fiesta-red',
       'coral-pink',
       'teddy-brown',
       'clockwork-orange',
       'graffiti-yellow',
       'army-green',
       'neon-green',
       'acid-green',
       'sherwood-green',
       'ocean-turquoise',
       'surf-green',
       'deep-blue',
       'lake-placid-blue',
       'daphne-blue',
       'plum-crazy',
       'ultra-violet',
       'lilac-purple',
       'panther-pink',
       'peggy-pink',
       'flamingo-pink',
    );

    public function __construct(Tracker $tracker) {
        $this->tracker = $tracker;

        foreach ($this->existing_colors as $color) {
             $this->colors[] = array(
                 'color'    => $color,
                 'selected' => $color === $this->tracker->getColor()
             );
        }
    }

    public function current() {
        return current($this->colors);
    }

    public function key() {
        return key($this->colors);
    }

    public function next() {
        return next($this->colors);
    }

    public function rewind() {
        reset($this->colors);
    }

    public function valid() {
        return current($this->colors) !== false;
    }
}
