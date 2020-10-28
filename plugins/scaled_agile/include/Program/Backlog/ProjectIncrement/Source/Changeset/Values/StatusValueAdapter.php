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

namespace Tuleap\ScaledAgile\Program\Backlog\ProjectIncrement\Source\Changeset\Values;

use Tuleap\ScaledAgile\Program\Backlog\ProjectIncrement\Source\Fields\FieldData;

class StatusValueAdapter
{
    public function build(FieldData $field_status_data, \Tracker_Artifact_Changeset $source_changeset): StatusValueData
    {
        $status_value = $source_changeset->getValue($field_status_data->getField());
        if (! $status_value) {
            throw new ChangesetValueNotFoundException(
                (int) $source_changeset->getId(),
                (int) $field_status_data->getId(),
                "status"
            );
        }
        assert($status_value instanceof \Tracker_Artifact_ChangesetValue_List);

        return new StatusValueData($status_value->getListValues());
    }
}
