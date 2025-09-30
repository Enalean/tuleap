<?php
/**
 * Copyright (c) Enalean 2021 -  Present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
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

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotPascalCase
final class b202108100839_Who_can_prioritize_should_depends_on_program_id extends \Tuleap\ForgeUpgrade\Bucket
{
    public function description(): string
    {
        return 'Replace program_increment_tracker_id by program_id plugin_program_management_can_prioritize_features';
    }

    public function up(): void
    {
        if ($this->api->columnNameExists('plugin_program_management_can_prioritize_features', 'project_id')) {
            return;
        }
        $this->dropTemporaryTablesIfExists();
        $this->createPluginProgramManagementCanPrioritizeFeaturesTmp();
        $this->renameTemporaryTable();
    }

    private function dropTemporaryTablesIfExists(): void
    {
        $sql = 'DROP TABLE IF EXISTS plugin_program_management_can_prioritize_features_tmp';
        $this->api->dbh->exec($sql);
    }

    private function createPluginProgramManagementCanPrioritizeFeaturesTmp(): void
    {
        $sql = 'CREATE TABLE plugin_program_management_can_prioritize_features_tmp(
                    project_id INT(11) NOT NULL,
                    user_group_id INT(11) NOT NULL,
                    PRIMARY KEY (project_id, user_group_id)
                ) ENGINE=InnoDB';

        $this->api->createTable('plugin_program_management_can_prioritize_features_tmp', $sql);

        $sql = 'INSERT IGNORE INTO plugin_program_management_can_prioritize_features_tmp (project_id, user_group_id)
                SELECT program.program_project_id, permsission.user_group_id
                FROM plugin_program_management_can_prioritize_features AS permsission
                    JOIN plugin_program_management_program AS program
                        ON (permsission.program_increment_tracker_id = program.program_increment_tracker_id);';

        $res = $this->api->dbh->exec($sql);
        if ($res === false) {
            throw new \Tuleap\ForgeUpgrade\Bucket\BucketUpgradeNotCompleteException('An error occurred while inserting data in plugin_program_management_can_prioritize_features_tmp');
        }
    }

    private function renameTemporaryTable(): void
    {
        $sql = 'RENAME TABLE plugin_program_management_can_prioritize_features TO plugin_program_management_can_prioritize_features_old,
                             plugin_program_management_can_prioritize_features_tmp TO plugin_program_management_can_prioritize_features';

        $res = $this->api->dbh->exec($sql);

        if ($res === false) {
            throw new \Tuleap\ForgeUpgrade\Bucket\BucketUpgradeNotCompleteException('An error occurred while renaming of plugin_program_management_can_prioritize_features_tmp');
        }
    }
}
