<?php
/**
 * Copyright (c) Enalean, 2017 - Present. All Rights Reserved.
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

namespace Tuleap\Kanban\REST\v1;

use Tuleap\Kanban\KanbanItemManager;
use Cardwall_Semantic_CardFields;
use EventManager;
use PFUser;
use Tuleap\Kanban\ColumnIdentifier;
use Tuleap\AgileDashboard\REST\v1\BacklogItemRepresentationFactory;
use Tuleap\Cardwall\BackgroundColor\BackgroundColor;
use Tuleap\Cardwall\BackgroundColor\BackgroundColorBuilder;
use Tuleap\Tracker\Artifact\Artifact;
use UserManager;

final class ItemRepresentationBuilder
{
    public function __construct(
        private readonly KanbanItemManager $kanban_item_manager,
        private readonly TimeInfoFactory $time_info_factory,
        private readonly UserManager $user_manager,
        private readonly EventManager $event_manager,
        private readonly BackgroundColorBuilder $background_color_builder,
    ) {
    }

    public function buildItemRepresentation(Artifact $artifact): KanbanItemRepresentation
    {
        $item_in_backlog = $this->kanban_item_manager->isKanbanItemInBacklog($artifact);
        $in_column       = ($item_in_backlog) ? ColumnIdentifier::BACKLOG_COLUMN : 0;

        if (! $in_column) {
            $item_in_archive = $this->kanban_item_manager->isKanbanItemInArchive($artifact);
            $in_column       = ($item_in_archive) ? ColumnIdentifier::ARCHIVE_COLUMN : 0;
        }

        if (! $in_column) {
            $in_column = $this->kanban_item_manager->getColumnIdOfKanbanItem($artifact) ?: 0;
        }

        $item_representation = $this->buildItem(
            new ColumnIdentifier($in_column),
            $artifact,
            $this->time_info_factory->getTimeInfo($artifact)
        );

        return $item_representation;
    }

    public function buildItemRepresentationInColumn(
        ColumnIdentifier $column_identifier,
        Artifact $artifact,
    ): KanbanItemRepresentation {
        $time_info = $column_identifier->isBacklog() ? [] : $this->time_info_factory->getTimeInfo($artifact);

        return $this->buildItem($column_identifier, $artifact, $time_info);
    }

    private function buildItem(
        ColumnIdentifier $column_identifier,
        Artifact $artifact,
        array $time_info,
    ): KanbanItemRepresentation {
        $current_user         = $this->user_manager->getCurrentUser();
        $card_fields_semantic = $this->getCardFieldsSemantic($artifact);

        $card_fields      = [];
        $background_color = new BackgroundColor('');
        if ($card_fields_semantic) {
            $card_fields      = $this->getCardFields($card_fields_semantic, $artifact, $current_user);
            $background_color = $this->background_color_builder->build(
                $card_fields_semantic,
                $artifact,
                $current_user
            );
        }

        return KanbanItemRepresentation::build(
            $artifact,
            $time_info,
            $column_identifier->getColumnId(),
            $card_fields,
            $background_color
        );
    }

    private function getCardFields(
        Cardwall_Semantic_CardFields $card_fields_semantic,
        Artifact $artifact,
        PFUser $current_user,
    ): array {
        $card_fields = [];

        foreach ($card_fields_semantic->getFields() as $field) {
            if (! $field->userCanRead($current_user)) {
                continue;
            }

            $changeset = $artifact->getLastChangeset();
            if (! $changeset) {
                continue;
            }

            $value = $field->getFullRESTValue($current_user, $changeset);
            if ($value) {
                $card_fields[] = $value;
            }
        }

        return $card_fields;
    }

    private function getCardFieldsSemantic(Artifact $artifact): ?Cardwall_Semantic_CardFields
    {
        $card_fields_semantic = null;

        $this->event_manager->processEvent(
            BacklogItemRepresentationFactory::AGILEDASHBOARD_EVENT_GET_CARD_FIELDS,
            [
                'tracker'              => $artifact->getTracker(),
                'card_fields_semantic' => &$card_fields_semantic,
            ]
        );

        return $card_fields_semantic;
    }
}
