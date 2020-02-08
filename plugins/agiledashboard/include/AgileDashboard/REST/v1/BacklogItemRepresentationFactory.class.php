<?php
/**
 * Copyright (c) Enalean, 2014 - 2018. All Rights Reserved.
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

namespace Tuleap\AgileDashboard\REST\v1;

use AgileDashboard_Milestone_Backlog_IBacklogItem;
use Cardwall_Semantic_CardFields;
use EventManager;
use PFUser;
use Tracker_Artifact;
use Tuleap\Cardwall\BackgroundColor\BackgroundColorBuilder;
use UserManager;

class BacklogItemRepresentationFactory
{
    /** @var BackgroundColorBuilder */
    private $background_color_builder;

    /** @var UserManager */
    private $user_manager;

    /** @var EventManager */
    private $event_manager;

    public function __construct(
        BackgroundColorBuilder $background_color_builder,
        UserManager $user_manager,
        EventManager $event_manager
    ) {
        $this->background_color_builder = $background_color_builder;
        $this->user_manager             = $user_manager;
        $this->event_manager            = $event_manager;
    }

    public function createBacklogItemRepresentation(AgileDashboard_Milestone_Backlog_IBacklogItem $backlog_item)
    {
        $artifact             = $backlog_item->getArtifact();
        $current_user         = $this->user_manager->getCurrentUser();
        $card_fields_semantic = $this->getCardFieldsSemantic($artifact);
        $card_fields          = $this->getCardFields($card_fields_semantic, $artifact, $current_user);
        $background_color     = $this->background_color_builder->build(
            $card_fields_semantic,
            $artifact,
            $current_user
        );

        $backlog_item_representation = new BacklogItemRepresentation();
        $backlog_item_representation->build(
            $backlog_item,
            $card_fields,
            $background_color
        );

        return $backlog_item_representation;
    }

    /**
     * @return array
     */
    private function getCardFields(
        Cardwall_Semantic_CardFields $card_fields_semantic,
        Tracker_Artifact $artifact,
        PFUser $current_user
    ) {
        $card_fields = [];

        foreach ($card_fields_semantic->getFields() as $field) {
            if ($field->userCanRead($current_user)) {
                $value = $field->getFullRESTValue($current_user, $artifact->getLastChangeset());

                if ($value) {
                    $card_fields[] = $value;
                }
            }
        }

        return $card_fields;
    }

    /**
     * @return Cardwall_Semantic_CardFields
     */
    private function getCardFieldsSemantic(Tracker_Artifact $artifact)
    {
        $card_fields_semantic = null;

        $this->event_manager->processEvent(
            AGILEDASHBOARD_EVENT_GET_CARD_FIELDS,
            array(
                'tracker'              => $artifact->getTracker(),
                'card_fields_semantic' => &$card_fields_semantic
            )
        );

        return $card_fields_semantic;
    }
}
