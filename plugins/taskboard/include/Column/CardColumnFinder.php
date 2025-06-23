<?php
/**
 * Copyright (c) Enalean, 2024-Present. All Rights Reserved.
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

namespace Tuleap\Taskboard\Column;

use Tuleap\Cardwall\OnTop\Config\ColumnFactory;
use Tuleap\Option\Option;
use Tuleap\Taskboard\Column\FieldValuesToColumnMapping\ArtifactMappedFieldValueRetriever;
use Tuleap\Taskboard\Column\FieldValuesToColumnMapping\MappedValuesRetriever;
use Tuleap\Taskboard\Tracker\TaskboardTracker;
use Tuleap\Tracker\Artifact\Artifact;

final readonly class CardColumnFinder
{
    public function __construct(
        private ArtifactMappedFieldValueRetriever $card_value_retriever,
        private ColumnFactory $column_factory,
        private MappedValuesRetriever $mapped_values_retriever,
    ) {
    }

    /** @return Option<\Cardwall_Column> */
    public function findColumnOfCard(
        \Tuleap\Tracker\Tracker $milestone_tracker,
        Artifact $card_artifact,
        \PFUser $user,
    ): Option {
        return $this->card_value_retriever->getFirstValueAtLastChangeset($milestone_tracker, $card_artifact, $user)
            ->andThen(
                function (\Tracker_FormElement_Field_List_BindValue $card_mapped_field_value) use (
                    $card_artifact,
                    $milestone_tracker
                ) {
                    $card_tracker      = $card_artifact->getTracker();
                    $taskboard_tracker = new TaskboardTracker($milestone_tracker, $card_tracker);
                    $columns           = $this->column_factory->getDashboardColumns($milestone_tracker);

                    $found = Option::nothing(\Cardwall_Column::class);
                    foreach ($columns as $column) {
                        $found = $found->orElse(
                            fn() => $this->doesColumnAcceptCardMappedFieldValue($taskboard_tracker, $column, $card_mapped_field_value)
                        );
                    }
                    return $found;
                }
            );
    }

    /** @return Option<\Cardwall_Column> */
    private function doesColumnAcceptCardMappedFieldValue(
        TaskboardTracker $taskboard_tracker,
        \Cardwall_Column $column,
        \Tracker_FormElement_Field_List_BindValue $card_mapped_field_value,
    ): Option {
        return $this->mapped_values_retriever->getValuesMappedToColumn($taskboard_tracker, $column)
            ->andThen(
                function ($mapped_values) use ($card_mapped_field_value, $column) {
                    if ($mapped_values->contains($card_mapped_field_value->getId())) {
                        return Option::fromValue($column);
                    }
                    return Option::nothing(\Cardwall_Column::class);
                }
            );
    }
}
