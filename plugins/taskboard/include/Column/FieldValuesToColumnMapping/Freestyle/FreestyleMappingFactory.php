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
use Tuleap\Taskboard\Column\FieldValuesToColumnMapping\EmptyMappedValues;
use Tuleap\Taskboard\Column\FieldValuesToColumnMapping\MappedValues;
use Tuleap\Taskboard\Column\FieldValuesToColumnMapping\MappedValuesInterface;
use Tuleap\Taskboard\Tracker\TaskboardTracker;

class FreestyleMappingFactory
{
    public function __construct(private FreestyleMappingDao $dao)
    {
    }

    public function doesFreestyleMappingExist(TaskboardTracker $taskboard_tracker): bool
    {
        return $this->dao->doesFreestyleMappingExist($taskboard_tracker);
    }

    public function getValuesMappedToColumn(
        TaskboardTracker $taskboard_tracker,
        Cardwall_Column $column,
    ): MappedValuesInterface {
        $rows = $this->dao->searchMappedFieldValuesForColumn($taskboard_tracker, $column);
        if (empty($rows)) {
            return new EmptyMappedValues();
        }
        $field_value_ids = [];
        foreach ($rows as $row) {
            $field_value_ids[] = (int) $row['value_id'];
        }
        return new MappedValues($field_value_ids);
    }
}
