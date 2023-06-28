<?php
/**
 * Copyright (c) Enalean, 2011-Present. All Rights Reserved.
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
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

namespace Tuleap\Tracker\FormElement\Field\PermissionsOnArtifact;

use Tracker_Artifact_Changeset_ValueDao;
use Tuleap\DB\DataAccessObject;
use Tuleap\Tracker\FormElement\Field\DeleteFieldValue;
use Tuleap\Tracker\FormElement\Field\SearchFieldValue;

class PermissionsOnArtifactFieldValueDao extends DataAccessObject implements SearchFieldValue, DeleteFieldValue
{
    public function __construct(private readonly Tracker_Artifact_Changeset_ValueDao $changeset_value_dao)
    {
        parent::__construct();
    }

    public function searchById($changeset_value_id): ?array
    {
        $sql = "SELECT changeset_value_id, use_perm, ugroup.ugroup_id, ugroup.name AS ugroup_name
                FROM tracker_changeset_value_permissionsonartifact
                JOIN ugroup ON (ugroup.ugroup_id = tracker_changeset_value_permissionsonartifact.ugroup_id)
                WHERE changeset_value_id = ? ";
        return $this->getDB()->run($sql, $changeset_value_id);
    }

    public function create(int $changeset_value_id, bool $use_perm, array|string $value_ids)
    {
        $values = [];
        if (! is_array($value_ids)) {
            $value_ids = [$value_ids];
        }
        foreach ($value_ids as $v) {
            $values[] = ["changeset_value_id" => $changeset_value_id, "use_perm" => $use_perm, "ugroup_id" => $v];
        }
        if ($values) {
            return $this->getDB()->insertMany("tracker_changeset_value_permissionsonartifact", $values) !== null;
        }
        return true;
    }

    public function createNoneValue(int $tracker_id, int $field_id): bool
    {
        $changeset_value_ids = $this->changeset_value_dao->createFromLastChangesetByTrackerId($tracker_id, $field_id);
        if (empty($changeset_value_ids)) {
            return false;
        }

        $values = [];
        foreach ($changeset_value_ids as $v) {
            $values[] = ["changeset_value_id" => $v, "use_perm" => 1, "ugroup_id" => 1];
        }
        return $this->getDB()->insertMany("tracker_changeset_value_permissionsonartifact", $values) !== null;
    }

    public function keep(int $from, int $to): bool
    {
        $sql = "INSERT INTO tracker_changeset_value_permissionsonartifact (changeset_value_id, use_perm, ugroup_id)
                SELECT ?, use_perm, ugroup_id
                FROM tracker_changeset_value_permissionsonartifact
                WHERE changeset_value_id = ?";
        return $this->getDB()->run($sql, $to, $from) !== null;
    }

    public function delete(int $changeset_value_id): void
    {
        $this->getDB()->delete('tracker_changeset_value_permissionsonartifact', ['changeset_value_id' => $changeset_value_id]);
    }
}
