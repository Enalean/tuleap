<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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

namespace Tuleap\ScaledAgile\Adapter\Program;

use Tuleap\ScaledAgile\Program\Backlog\ProgramIncrement\Source\Changeset\Values\ChangesetValueNotFoundException;
use Tuleap\ScaledAgile\Program\Backlog\ProgramIncrement\Source\Changeset\Values\StartDateValue;
use Tuleap\ScaledAgile\Program\Backlog\ProgramIncrement\Source\Fields\FieldData;
use Tuleap\ScaledAgile\Program\Backlog\ProgramIncrement\Source\ReplicationData;

final class StartDateValueAdapter
{
    /**
     * @throws ChangesetValueNotFoundException
     */
    public function build(FieldData $field_start_date_data, ReplicationData $replication_data): StartDateValue
    {
        $start_date_value = $replication_data->getFullChangeset()->getValue($field_start_date_data->getFullField());

        if (! $start_date_value) {
            throw new ChangesetValueNotFoundException(
                (int) $replication_data->getFullChangeset()->getId(),
                (int) $field_start_date_data->getId(),
                "timeframe start date"
            );
        }
        assert($start_date_value instanceof \Tracker_Artifact_ChangesetValue_Date);
        return new StartDateValue($start_date_value->getValue());
    }
}
