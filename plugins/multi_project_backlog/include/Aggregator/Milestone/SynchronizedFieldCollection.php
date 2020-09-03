<?php
/**
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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

namespace Tuleap\MultiProjectBacklog\Aggregator\Milestone;

final class SynchronizedFieldCollection
{
    /**
     * @var \Tracker_FormElement_Field[]
     * @psalm-readonly
     */
    private $synchronized_fields;
    /**
     * @var array<int,true>
     * @psalm-readonly
     */
    private $synchronized_field_ids;

    /**
     * @param \Tracker_FormElement_Field[] $synchronized_fields
     */
    public function __construct(array $synchronized_fields)
    {
        $this->synchronized_fields    = $synchronized_fields;
        $this->synchronized_field_ids = self::getSynchronizedFieldIDsAsKeys($synchronized_fields);
    }

    /**
     * @param \Tracker_FormElement_Field[] $synchronized_fields
     * @return array<int,true>
     */
    private static function getSynchronizedFieldIDsAsKeys(array $synchronized_fields): array
    {
        $ids = [];
        foreach ($synchronized_fields as $synchronized_field) {
            $ids[(int) $synchronized_field->getId()] = true;
        }
        return $ids;
    }

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

    public function isFieldSynchronized(\Tracker_FormElement_Field $field): bool
    {
        return isset($this->synchronized_field_ids[(int) $field->getId()]);
    }

    /**
     * @return int[]
     */
    public function getSynchronizedFieldIDs(): array
    {
        return array_keys($this->synchronized_field_ids);
    }
}
