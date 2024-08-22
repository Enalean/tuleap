<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

namespace Tuleap\Taskboard\Column\FieldValuesToColumnMapping;

use Cardwall_Column;
use Tracker;
use Tuleap\Taskboard\Column\FieldValuesToColumnMapping\Freestyle\FreestyleMappedFieldValuesRetriever;
use Tuleap\Taskboard\Tracker\TaskboardTracker;

class MappedValuesRetriever
{
    public function __construct(
        private FreestyleMappedFieldValuesRetriever $freestyle_retriever,
        private \Cardwall_FieldProviders_SemanticStatusFieldRetriever $status_retriever,
    ) {
    }

    public function getValuesMappedToColumn(
        TaskboardTracker $taskboard_tracker,
        Cardwall_Column $column,
    ): MappedValuesInterface {
        return $this->freestyle_retriever->getValuesMappedToColumn($taskboard_tracker, $column)->match(
            static fn($freestyle_mapped_values) => $freestyle_mapped_values,
            fn() => $this->matchStatusValuesByDuckTyping($taskboard_tracker->getTracker(), $column)
        );
    }

    private function matchStatusValuesByDuckTyping(Tracker $tracker, Cardwall_Column $column): MappedValuesInterface
    {
        $status_field = $this->status_retriever->getField($tracker);
        if (! $status_field) {
            return new EmptyMappedValues();
        }
        foreach ($status_field->getVisibleValuesPlusNoneIfAny() as $value) {
            if ($column->getLabel() === $value->getLabel()) {
                return new MappedValues([(int) $value->getId()]);
            }
        }
        return new EmptyMappedValues();
    }
}
