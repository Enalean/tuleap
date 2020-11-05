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
use Tuleap\ScaledAgile\Program\Backlog\ProgramIncrement\Source\Changeset\Values\DescriptionValue;
use Tuleap\ScaledAgile\Program\Backlog\ProgramIncrement\Source\Fields\FieldData;
use Tuleap\ScaledAgile\Program\Backlog\ProgramIncrement\Source\ReplicationData;

final class DescriptionValueAdapter
{
    /**
     * @throws ChangesetValueNotFoundException
     */
    public function build(FieldData $field_description_data, ReplicationData $replication_data): DescriptionValue
    {
        $description_value = $replication_data->getFullChangeset()->getValue($field_description_data->getFullField());
        if (! $description_value) {
            throw new ChangesetValueNotFoundException(
                (int) $replication_data->getFullChangeset()->getId(),
                (int) $field_description_data->getId(),
                "description"
            );
        }
        assert($description_value instanceof \Tracker_Artifact_ChangesetValue_Text);
        return new DescriptionValue($description_value->getValue(), $description_value->getFormat());
    }
}
