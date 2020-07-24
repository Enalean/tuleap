<?php
/**
 * Copyright (c) Enalean, 2011-present. All Rights Reserved.
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

use PHPUnit\Framework\TestCase;

//phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
class ColorHelperTest extends TestCase
{

    private $colorSet = [
        '#FFFFFF' => [255, 255, 255],
        '#000000' => [0, 0, 0],
        '#FF0000' => [255, 0, 0],
        '#00FF00' => [0, 255, 0],
        '#0000FF' => [0, 0, 255],
        '#38CDAF' => [56, 205, 175],
        '#E025DC' => [224, 37, 220]
    ];

    public function testRGBToHexa(): void
    {
        foreach ($this->colorSet as $hexa => $rgb) {
            $this->assertEquals($hexa, ColorHelper::RGBToHexa($rgb[0], $rgb[1], $rgb[2]));
        }
    }

    public function testHexaToRGB(): void
    {
        foreach ($this->colorSet as $hexa => $rgb) {
            $this->assertEquals([$rgb[0], $rgb[1], $rgb[2]], ColorHelper::HexaToRGB($hexa));
        }
    }
}
