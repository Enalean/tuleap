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

namespace Tuleap\ScaledAgile\Program\Backlog\ProgramIncrement\Source\Fields;

final class SynchronizedFieldDataFromProgramAndTeamTrackersCollection
{
    /**
     * @var array<int, true>
     */
    private $synchronized_fields_ids = [];
    /**
     * @var FieldData[]
     */
    private $synchronized_fields = [];

    /**
     * @psalm-readonly
     */
    public function canUserSubmitAndUpdateAllFields(\PFUser $user): bool
    {
        foreach ($this->synchronized_fields as $synchronized_field) {
            if (! $synchronized_field->userCanSubmit($user)) {
                return false;
            }
            if (! $synchronized_field->userCanUpdate($user)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @psalm-readonly
     */
    public function isFieldSynchronized(\Tracker_FormElement_Field $field): bool
    {
        return isset($this->synchronized_fields_ids[(int) $field->getId()]);
    }

    /**
     * @return int[]
     * @psalm-readonly
     */
    public function getSynchronizedFieldIDs(): array
    {
        return array_keys($this->synchronized_fields_ids);
    }

    public function add(SynchronizedFieldDataFromProgramAndTeamTrackers $synchronized_field_data): void
    {
        $this->synchronized_fields     = array_merge(
            $this->synchronized_fields,
            $synchronized_field_data->getSynchronizedFieldsData()->getAllFields()
        );
        $this->synchronized_fields_ids = $this->synchronized_fields_ids + $synchronized_field_data->getSynchronizedFieldDataIds();
    }
}
