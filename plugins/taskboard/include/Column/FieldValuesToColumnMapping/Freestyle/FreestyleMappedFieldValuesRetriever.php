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

namespace Tuleap\Taskboard\Column\FieldValuesToColumnMapping\Freestyle;

use Cardwall_Column;
use Tuleap\Option\Option;
use Tuleap\Taskboard\Column\FieldValuesToColumnMapping\EmptyMappedValues;
use Tuleap\Taskboard\Column\FieldValuesToColumnMapping\MappedValues;
use Tuleap\Taskboard\Tracker\TaskboardTracker;

final readonly class FreestyleMappedFieldValuesRetriever
{
    public function __construct(
        private VerifyMappingExists $verify_mapping_exists,
        private SearchMappedFieldValuesForColumn $search_values,
    ) {
    }

    /**
     * Returns Nothing when there is no freestyle mapping for the given trackers
     * @return Option<EmptyMappedValues>|Option<MappedValues>
     */
    public function getValuesMappedToColumn(
        TaskboardTracker $taskboard_tracker,
        Cardwall_Column $column,
    ): Option {
        if (! $this->verify_mapping_exists->doesFreestyleMappingExist($taskboard_tracker)) {
            return Option::nothing(MappedValues::class);
        }
        $bind_value_ids = $this->search_values->searchMappedFieldValuesForColumn($taskboard_tracker, $column);
        if ($bind_value_ids === []) {
            return Option::fromValue(new EmptyMappedValues());
        }
        return Option::fromValue(new MappedValues($bind_value_ids));
    }
}
