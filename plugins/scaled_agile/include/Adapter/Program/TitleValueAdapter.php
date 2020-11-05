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
use Tuleap\ScaledAgile\Program\Backlog\ProgramIncrement\Source\Changeset\Values\TitleValue;
use Tuleap\ScaledAgile\Program\Backlog\ProgramIncrement\Source\Changeset\Values\UnsupportedTitleFieldException;
use Tuleap\ScaledAgile\Program\Backlog\ProgramIncrement\Source\Fields\FieldData;
use Tuleap\ScaledAgile\Program\Backlog\ProgramIncrement\Source\ReplicationData;

final class TitleValueAdapter
{
    /**
     * @throws UnsupportedTitleFieldException
     * @throws ChangesetValueNotFoundException
     */
    public function build(FieldData $field_title_data, ReplicationData $replication_data): TitleValue
    {
        $title_value = $replication_data->getFullChangeset()->getValue($field_title_data->getFullField());
        if (! $title_value) {
            throw new ChangesetValueNotFoundException(
                (int) $replication_data->getFullChangeset()->getId(),
                (int) $field_title_data->getId(),
                "title"
            );
        }
        if (! ($title_value instanceof \Tracker_Artifact_ChangesetValue_String)) {
            throw new UnsupportedTitleFieldException((int) $field_title_data->getId());
        }

        return new TitleValue($title_value->getValue());
    }
}
