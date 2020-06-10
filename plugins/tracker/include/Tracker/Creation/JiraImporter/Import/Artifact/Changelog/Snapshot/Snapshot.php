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
 *
 */

declare(strict_types=1);

namespace Tuleap\Tracker\Creation\JiraImporter\Import\Artifact\Changelog\Snapshot;

class Snapshot
{
    /**
     * @var FieldSnapshot[]
     */
    private $field_snapshots;

    /**
     * @return FieldSnapshot[]
     */
    public function getAllFieldsSnapshot(): array
    {
        return $this->field_snapshots;
    }

    public function addFieldSnapshot(FieldSnapshot $state): void
    {
        $field_key = $state->getFieldMapping()->getJiraFieldId();

        $this->field_snapshots[$field_key] = $state;
    }

    public function removeFieldSnapshot(string $field_state_key): void
    {
        if (isset($this->field_snapshots[$field_state_key])) {
            unset($this->field_snapshots[$field_state_key]);
        }
    }

    public function isFieldInSnapshot(string $field_state_key): bool
    {
        return array_key_exists($field_state_key, $this->field_snapshots);
    }

    public function getFieldInSnapshot(string $field_state_key): ?FieldSnapshot
    {
        if ($this->isFieldInSnapshot($field_state_key)) {
            return $this->field_snapshots[$field_state_key];
        }

        return null;
    }

    public static function duplicateExistingSnapshot(Snapshot $snapshot): self
    {
        $new_snapshot = new self();
        foreach ($snapshot->getAllFieldsSnapshot() as $field_snapshot) {
            $new_snapshot->addFieldSnapshot($field_snapshot);
        }

        return $new_snapshot;
    }
}
