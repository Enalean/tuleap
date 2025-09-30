<?php
/**
 * Copyright (c) Enalean, 2022 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Workflow\Trigger;

use Tuleap\DB\DataAccessObject;

class TriggersDao extends DataAccessObject
{
    public function isTrackerImplicatedInTriggers(int $tracker_id): bool
    {
        return $this->isTrackerTargetOfTriggers($tracker_id) || $this->isTrackerSourceOfTriggers($tracker_id);
    }

    private function isTrackerTargetOfTriggers(int $tracker_id): bool
    {
        $sql = ' SELECT COUNT(*)
                FROM tracker_workflow_trigger_rule_trg_field_static_value
                INNER JOIN tracker_field_list_bind_static_value ON tracker_workflow_trigger_rule_trg_field_static_value.value_id = tracker_field_list_bind_static_value.id
                INNER JOIN tracker_field ON tracker_field.id = tracker_field_list_bind_static_value.field_id
                INNER JOIN tracker ON tracker.id = tracker_field.tracker_id
                WHERE tracker_field.tracker_id = ? AND tracker.deletion_date IS NULL';

        return $this->getDB()->single($sql, [$tracker_id]) > 0;
    }

    private function isTrackerSourceOfTriggers(int $tracker_id): bool
    {
        $sql = 'SELECT COUNT(*)
                FROM tracker_workflow_trigger_rule_static_value
                INNER JOIN tracker_field_list_bind_static_value ON tracker_workflow_trigger_rule_static_value.value_id = tracker_field_list_bind_static_value.id
                INNER JOIN tracker_field ON tracker_field.id = tracker_field_list_bind_static_value.field_id
                INNER JOIN tracker ON tracker.id = tracker_field.tracker_id
                WHERE tracker_field.tracker_id = ? AND tracker.deletion_date IS NULL';

        return $this->getDB()->single($sql, [$tracker_id]) > 0;
    }
}
