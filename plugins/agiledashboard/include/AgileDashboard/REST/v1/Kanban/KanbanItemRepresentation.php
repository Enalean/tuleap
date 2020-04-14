<?php
/**
 * Copyright (c) Enalean, 2015 - Present. All Rights Reserved.
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

namespace Tuleap\AgileDashboard\REST\v1\Kanban;

use Tuleap\Cardwall\BackgroundColor\BackgroundColor;
use Tuleap\REST\JsonCast;
use Tracker_Artifact;

class KanbanItemRepresentation
{

    /**
     * @var Int
     */
    public $id;

    /**
     * @var String
     */
    public $item_name;

    /**
     * @var String
     */
    public $label;

    /**
     * @var String
     */
    public $color;

    /*
     * @var array
     */
    public $card_fields;

    /*
     * @var array
     */
    public $timeinfo;

    /**
     * @var mixed string || int
     */
    public $in_column;

    /**
     * @var string
     */
    public $background_color_name;

    public function build(
        Tracker_Artifact $artifact,
        $timeinfo,
        $in_column,
        array $card_fields,
        BackgroundColor $background_color
    ) {
        $this->id                    = JsonCast::toInt($artifact->getId());
        $this->item_name             = $artifact->getTracker()->getItemName();
        $this->label                 = $artifact->getTitle() ?? '';
        $this->color                 = $artifact->getTracker()->getColor()->getName();
        $this->timeinfo              = $timeinfo;
        $this->in_column             = $in_column;
        $this->card_fields           = $card_fields;
        $this->background_color_name = $background_color->getBackgroundColorName();
    }
}
