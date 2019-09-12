<?php
/**
 * Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
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

namespace Tuleap\Taskboard\REST\v1;

use Cardwall_Semantic_CardFields;
use PFUser;
use Tracker_Artifact;
use Tuleap\Cardwall\BackgroundColor\BackgroundColorBuilder;

class CardRepresentationBuilder
{
    /**
     * @var BackgroundColorBuilder
     */
    private $background_color_builder;

    public function __construct(BackgroundColorBuilder $background_color_builder)
    {
        $this->background_color_builder = $background_color_builder;
    }

    public function build(Tracker_Artifact $artifact, PFUser $user, int $rank): CardRepresentation
    {
        $card_fields_semantic = Cardwall_Semantic_CardFields::load($artifact->getTracker());
        $background_color     = $this->background_color_builder->build($card_fields_semantic, $artifact, $user);

        $representation = new CardRepresentation();
        $representation->build($artifact, $background_color, $rank);

        return $representation;
    }
}
