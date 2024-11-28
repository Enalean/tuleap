<?php
/*
 * Copyright (c) Enalean, 2023-Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Notifications;

use ProjectUGroup;
use Tuleap\DB\DataAccessObject;
use Tuleap\Project\Duplication\DuplicationType;
use Tuleap\Project\Duplication\DuplicationUserGroupMapping;

final class UgroupsToNotifyDuplicationDao extends DataAccessObject
{
    public function duplicate(int $source_notification_id, int $target_notification_id, DuplicationUserGroupMapping $duplication_user_group_mapping): void
    {
        $ugroup_id_statement = $this->getUGroupIdStatement($duplication_user_group_mapping);

        $sql = <<<EOSQL
        INSERT INTO tracker_global_notification_ugroups(notification_id, ugroup_id)
        SELECT ?, $ugroup_id_statement
        FROM tracker_global_notification_ugroups
        WHERE notification_id = ?
        EOSQL;
        $this->getDB()->run($sql, $target_notification_id, $source_notification_id);
    }

    private function getUGroupIdStatement(DuplicationUserGroupMapping $duplication_user_group_mapping): string
    {
        if ($duplication_user_group_mapping->duplication_type === DuplicationType::DUPLICATE_SAME_PROJECT) {
            return 'ugroup_id';
        }

        $when_then  = $this->getWhenThen(ProjectUGroup::PROJECT_MEMBERS, ProjectUGroup::PROJECT_MEMBERS);
        $when_then .= $this->getWhenThen(ProjectUGroup::PROJECT_ADMIN, ProjectUGroup::PROJECT_ADMIN);
        foreach ($duplication_user_group_mapping->ugroup_mapping as $template_ugroup => $new_ugroup) {
            $when_then .= $this->getWhenThen($template_ugroup, $new_ugroup);
        }
        return $this->getCase('ugroup_id', $when_then);
    }

    private function getWhenThen(int $when, int $then): string
    {
        return ' WHEN ' . $when . ' THEN ' . $then . ' ';
    }

    private function getCase(string $field, string $when_then): string
    {
        return ' CASE ' . $field . ' ' . $when_then . ' END ';
    }
}
