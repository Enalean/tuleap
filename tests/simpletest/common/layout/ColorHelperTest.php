<?php
/**
 * Copyright (c) Enalean, 2011. All Rights Reserved.
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

class ColorHelperTest extends TuleapTestCase
{

    private $colorSet = array(
        '#FFFFFF' => array(255, 255, 255),
        '#000000' => array(0, 0, 0),
        '#FF0000' => array(255, 0, 0),
        '#00FF00' => array(0, 255, 0),
        '#0000FF' => array(0, 0, 255),
        '#38CDAF' => array(56, 205, 175),
        '#E025DC' => array(224, 37, 220)
    );

    function testRGBToHexa()
    {
        foreach ($this->colorSet as $hexa => $rgb) {
            $this->assertEqual($hexa, ColorHelper::RGBToHexa($rgb[0], $rgb[1], $rgb[2]));
        }
    }

    function testHexaToRGB()
    {
        foreach ($this->colorSet as $hexa => $rgb) {
            $this->assertEqual(array($rgb[0], $rgb[1], $rgb[2]), ColorHelper::HexaToRGB($hexa));
        }
    }
}
