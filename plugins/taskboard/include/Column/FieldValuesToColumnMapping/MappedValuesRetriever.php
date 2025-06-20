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
use Tuleap\Option\Option;
use Tuleap\Taskboard\Column\FieldValuesToColumnMapping\Freestyle\FreestyleMappedFieldValuesRetriever;
use Tuleap\Taskboard\Tracker\TaskboardTracker;
use Tuleap\Tracker\Tracker;

final readonly class MappedValuesRetriever
{
    public function __construct(
        private FreestyleMappedFieldValuesRetriever $freestyle_retriever,
        private \Cardwall_FieldProviders_SemanticStatusFieldRetriever $status_retriever,
    ) {
    }

    /** @return Option<MappedValues | EmptyMappedValues> | Option<MappedValues> */
    public function getValuesMappedToColumn(
        TaskboardTracker $taskboard_tracker,
        Cardwall_Column $column,
    ): Option {
        return $this->freestyle_retriever->getValuesMappedToColumn($taskboard_tracker, $column)
            ->orElse(
                fn() => $this->matchStatusValuesByDuckTyping($taskboard_tracker->getTracker(), $column)
            );
    }

    /** @return Option<MappedValues> */
    private function matchStatusValuesByDuckTyping(Tracker $tracker, Cardwall_Column $column): Option
    {
        return Option::fromNullable($this->status_retriever->getField($tracker))
            ->andThen(static function ($status_field) use ($column) {
                foreach ($status_field->getVisibleValuesPlusNoneIfAny() as $value) {
                    if ($column->getLabel() === $value->getLabel()) {
                        return Option::fromValue(new MappedValues([(int) $value->getId()]));
                    }
                }
                return Option::nothing(MappedValues::class);
            });
    }
}
