<?php
/**
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
 *
 *  Tuleap is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  Tuleap is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace Tuleap\Project\REST\v1;

/**
 * @psalm-immutable
 * @psalm-import-type ValidProjectBackgroundName from \Tuleap\Project\ProjectBackground\ProjectBackgroundName
 */
final class HeaderBackgroundRepresentation
{
    /**
     * @var string {@required true} {@choice aerial-water,asphalt-rock,beach-daytime,blue-rain,blue-sand,brown-alpaca,brown-desert,brown-grass,brown-textile,brush-daytime,green-grass,green-leaf,green-trees,led-light,ocean-waves,octopus-black,orange-tulip,purple-building,purple-droplet,purple-textile,snow-mountain,tree-water,white-sheep,wooden-surface}
     * @psalm-var ValidProjectBackgroundName
     */
    public $identifier;

    private function __construct()
    {
    }
}
