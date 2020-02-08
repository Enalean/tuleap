<?php
/**
 * Copyright (c) Enalean, 2017 - 2018. All Rights Reserved.
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

namespace Tuleap\AgileDashboard\REST\v1\Kanban;

use AgileDashboard_KanbanItemManager;
use Cardwall_Semantic_CardFields;
use EventManager;
use PFUser;
use Tracker_Artifact;
use Tuleap\AgileDashboard\Kanban\ColumnIdentifier;
use Tuleap\Cardwall\BackgroundColor\BackgroundColorBuilder;
use UserManager;

class ItemRepresentationBuilder
{
    /** @var AgileDashboard_KanbanItemManager */
    private $kanban_item_manager;
    /** @var TimeInfoFactory */
    private $time_info_factory;
    /** @var UserManager */
    private $user_manager;
    /** @var EventManager */
    private $event_manager;
    /**
     * @var BackgroundColorBuilder
     */
    private $background_color_builder;

    public function __construct(
        AgileDashboard_KanbanItemManager $kanban_item_manager,
        TimeInfoFactory $time_info_factory,
        UserManager $user_manager,
        EventManager $event_manager,
        BackgroundColorBuilder $background_color_builder
    ) {
        $this->kanban_item_manager      = $kanban_item_manager;
        $this->time_info_factory        = $time_info_factory;
        $this->user_manager             = $user_manager;
        $this->event_manager            = $event_manager;
        $this->background_color_builder = $background_color_builder;
    }

    /**
     * @return KanbanItemRepresentation
     */
    public function buildItemRepresentation(Tracker_Artifact $artifact)
    {
        $item_in_backlog = $this->kanban_item_manager->isKanbanItemInBacklog($artifact);
        $in_column       = ($item_in_backlog) ? ColumnIdentifier::BACKLOG_COLUMN : null;

        if (! $in_column) {
            $item_in_archive = $this->kanban_item_manager->isKanbanItemInArchive($artifact);
            $in_column       = ($item_in_archive) ? ColumnIdentifier::ARCHIVE_COLUMN : null;
        }

        if (! $in_column) {
            $in_column = $this->kanban_item_manager->getColumnIdOfKanbanItem($artifact);
        }

        $item_representation = $this->buildItem(
            new ColumnIdentifier($in_column),
            $artifact,
            $this->time_info_factory->getTimeInfo($artifact)
        );

        return $item_representation;
    }

    /**
     * @return KanbanItemRepresentation
     */
    public function buildItemRepresentationInColumn(ColumnIdentifier $column_identifier, Tracker_Artifact $artifact)
    {
        $time_info = $column_identifier->isBacklog() ? [] : $this->time_info_factory->getTimeInfo($artifact);

        return $this->buildItem($column_identifier, $artifact, $time_info);
    }

    /**
     * @param $time_info
     * @return KanbanItemRepresentation
     */
    private function buildItem(ColumnIdentifier $column_identifier, Tracker_Artifact $artifact, array $time_info)
    {
        $current_user         = $this->user_manager->getCurrentUser();
        $card_fields_semantic = $this->getCardFieldsSemantic($artifact);
        $card_fields          = $this->getCardFields($card_fields_semantic, $artifact, $current_user);
        $background_color     = $this->background_color_builder->build(
            $card_fields_semantic,
            $artifact,
            $current_user
        );

        $item_representation = new KanbanItemRepresentation();
        $item_representation->build(
            $artifact,
            $time_info,
            $column_identifier->getColumnId(),
            $card_fields,
            $background_color
        );
        return $item_representation;
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
     * @return Cardwall_Semantic_CardFields|null
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
