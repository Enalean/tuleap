<?php
/**
 * Copyright (c) Enalean, 2015. All Rights Reserved.
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

use Tuleap\REST\JsonCast;
use Tracker_Artifact;
use UserManager;
use EventManager;

class KanbanItemRepresentation {

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

    public function build(
        Tracker_Artifact $artifact,
        $timeinfo,
        $in_column
    ) {
        $this->id          = JsonCast::toInt($artifact->getId());
        $this->item_name   = $artifact->getTracker()->getItemName();
        $this->label       = $artifact->getTitle();
        $this->color       = $artifact->getTracker()->getNormalizedColor();
        $this->card_fields = $this->getArtifactCardFields($artifact);
        $this->timeinfo    = $timeinfo;
        $this->in_column   = $in_column;
    }

    private function getArtifactCardFields(Tracker_Artifact $artifact) {
        $current_user         = UserManager::instance()->getCurrentUser();
        $card_fields_semantic = $this->getCardFieldsSemantic($artifact);
        $card_fields          = array();

        foreach($card_fields_semantic->getFields() as $field) {
            if ($field->userCanRead($current_user)) {
                $card_fields[] = $field->getFullRESTValue($current_user, $artifact->getLastChangeset());
            }
        }

        return $card_fields;
    }

    private function getCardFieldsSemantic(Tracker_Artifact $artifact) {
        $card_fields_semantic = null;

        EventManager::instance()->processEvent(
            AGILEDASHBOARD_EVENT_GET_CARD_FIELDS,
            array(
                'tracker'              => $artifact->getTracker(),
                'card_fields_semantic' => &$card_fields_semantic
            )
        );

        return $card_fields_semantic;
    }
}
