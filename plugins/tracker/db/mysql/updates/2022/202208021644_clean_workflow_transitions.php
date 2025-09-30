<?php
/**
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotPascalCase
final class b202208021644_clean_workflow_transitions extends \Tuleap\ForgeUpgrade\Bucket
{
    public function description(): string
    {
        return 'Clean Workflow transitions when from or to are bound to deleted values';
    }

    public function preUp(): void
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up(): void
    {
        // Delete transition when `to` is an unknown value
        $sql = '
            DELETE transition
                FROM tracker_workflow_transition AS transition
            LEFT JOIN tracker_field_list_bind_static_value AS list_value ON transition.to_id = list_value.id
            WHERE list_value.id IS NULL;
        ;
        ';

        if ($this->db->dbh->exec($sql) === false) {
            throw new \Tuleap\ForgeUpgrade\Bucket\BucketUpgradeNotCompleteException(
                'An error occurred while deleting unknown transitions for `to_id`'
            );
        }

        // Delete transition when `from` is an unknown value but keep `0` as its the value used form `New artifact` transitions
        $sql = '
            DELETE transition
                FROM tracker_workflow_transition AS transition
            LEFT JOIN tracker_field_list_bind_static_value AS list_value ON transition.from_id = list_value.id
            WHERE list_value.id IS NULL AND transition.from_id != 0;
        ;
        ';

        if ($this->db->dbh->exec($sql) === false) {
            throw new \Tuleap\ForgeUpgrade\Bucket\BucketUpgradeNotCompleteException(
                'An error occurred while deleting unknown transitions for `from_id`'
            );
        }
    }
}
