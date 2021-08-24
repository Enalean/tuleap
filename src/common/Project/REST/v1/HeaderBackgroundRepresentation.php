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

use Tuleap\Project\ProjectBackground\ProjectBackgroundColorName;
use Tuleap\Project\ProjectBackground\ProjectBackgroundName;

/**
 * @psalm-immutable
 * @psalm-import-type ValidProjectBackgroundName from \Tuleap\Project\ProjectBackground\ProjectBackgroundName
 * @psalm-import-type ValidProjectBackgroundColorName from \Tuleap\Project\ProjectBackground\ProjectBackgroundColorName
 */
final class HeaderBackgroundRepresentation
{
    /**
     * @var string | null {@required false} {@choice aerial-water,asphalt-rock,beach-daytime,blue-rain,blue-sand,brown-alpaca,brown-desert,brown-grass,brown-textile,brush-daytime,green-grass,green-leaf,green-trees,led-light,ocean-waves,octopus-black,orange-tulip,purple-building,purple-droplet,purple-textile,snow-mountain,tree-water,white-sheep,wooden-surface}
     * @psalm-var ValidProjectBackgroundName|null
     */
    public $identifier;

    /**
     * @var string | null {@required false} {@choice inca-silver,chrome-silver,firemist-silver,red-wine,fiesta-red,coral-pink,teddy-brown,clockwork-orange,graffiti-yellow,army-green,neon-green,acid-green,sherwood-green,ocean-turquoise,surf-green,deep-blue,lake-placid-blue,daphne-blue,plum-crazy,ultra-violet,lilac-purple,panther-pink,peggy-pink}
     * @psalm-var ValidProjectBackgroundColorName|null
     */
    public $color;

    /**
     * @psalm-param ValidProjectBackgroundName|null $identifier
     * @psalm-param ValidProjectBackgroundColorName|null $color
     */
    private function __construct(?string $identifier, ?string $color)
    {
        $this->identifier = $identifier;
        $this->color      = $color;
    }

    public static function fromBackgroundName(ProjectBackgroundName $background_name): self
    {
        return new self(
            $background_name->getIdentifier(),
            null
        );
    }

    public static function fromColorName(ProjectBackgroundColorName $color_name): self
    {
        return new self(
            null,
            $color_name->getColorName()
        );
    }
}
