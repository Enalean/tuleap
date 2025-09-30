<?php
/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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
final class b202105250900_move_program_increment_tracker_id_in_program_table extends \Tuleap\ForgeUpgrade\Bucket
{
    public function description(): string
    {
        return 'Move program_increment_tracker_id in plugin_program_increment_program table';
    }

    public function preUp(): void
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up(): void
    {
        if (! $this->db->tableNameExists('plugin_program_management_label_program_increment')) {
            return;
        }
        $this->dropTemporaryTablesIfExists();
        $this->createPluginProgramManagementPlan();
        $this->createPluginProgramManagementProgram();
        $this->cleanTemporaryTables();
    }

    private function dropTemporaryTablesIfExists(): void
    {
        $sql = 'DROP TABLE IF EXISTS plugin_program_management_plan_tmp';
        $this->db->dbh->exec($sql);

        $sql = 'DROP TABLE IF EXISTS plugin_program_management_program_tmp';
        $this->db->dbh->exec($sql);
    }

    private function createPluginProgramManagementPlan(): void
    {
        $sql = 'CREATE TABLE plugin_program_management_plan_tmp(
                    project_id INT(11) NOT NULL,
                    plannable_tracker_id INT(11) NOT NULL,
                    PRIMARY KEY (project_id, plannable_tracker_id)
                ) ENGINE=InnoDB';

        $this->db->createTable('plugin_program_management_plan_tmp', $sql);

        $sql = 'INSERT IGNORE INTO plugin_program_management_plan_tmp (project_id, plannable_tracker_id)
                SELECT group_id, plan.plannable_tracker_id
                FROM tracker JOIN plugin_program_management_plan AS plan ON (tracker.id = plan.program_increment_tracker_id);';

        $res = $this->db->dbh->exec($sql);
        if ($res === false) {
            throw new \Tuleap\ForgeUpgrade\Bucket\BucketUpgradeNotCompleteException('An error occured while inserting data in plugin_program_management_plan_tmp');
        }
    }

    private function createPluginProgramManagementProgram(): void
    {
        $sql = 'CREATE TABLE plugin_program_management_program_tmp(
                    program_project_id INT(11) NOT NULL,
                    program_increment_tracker_id INT(11) NOT NULL,
                    iteration_tracker_id INT(11) DEFAULT NULL,
                    iteration_label VARCHAR(255) DEFAULT NULL,
                    iteration_sub_label VARCHAR(255) DEFAULT NULL,
                    program_increment_label VARCHAR(255) DEFAULT NULL,
                    program_increment_sub_label VARCHAR(255) DEFAULT NULL,
                    PRIMARY KEY (program_project_id)
                ) ENGINE=InnoDB';

        $this->db->createTable('plugin_program_management_program_tmp', $sql);

        $sql = 'INSERT IGNORE INTO plugin_program_management_program_tmp (
                   program_project_id,
                   program_increment_tracker_id,
                   iteration_tracker_id,
                   iteration_label,
                   iteration_sub_label,
                   program_increment_label,
                   program_increment_sub_label
                )
                SELECT group_id, id, program.iteration_tracker_id, program.iteration_label, program.iteration_sub_label, label.label, label.sub_label
                FROM tracker
                    JOIN plugin_program_management_plan ON (tracker.id = plugin_program_management_plan.program_increment_tracker_id)
                    JOIN plugin_program_management_label_program_increment as label ON (tracker.id = label.program_increment_tracker_id)
                    JOIN plugin_program_management_program as program ON (tracker.group_id = program.program_project_id)';

        $res = $this->db->dbh->exec($sql);
        if ($res === false) {
            throw new \Tuleap\ForgeUpgrade\Bucket\BucketUpgradeNotCompleteException('An error occured while inserting data in plugin_program_management_program_tmp');
        }
    }

    private function cleanTemporaryTables(): void
    {
        $sql = 'RENAME TABLE plugin_program_management_program TO plugin_program_management_program_old,
                             plugin_program_management_program_tmp TO plugin_program_management_program,
                             plugin_program_management_plan TO plugin_program_management_plan_old,
                             plugin_program_management_plan_tmp TO plugin_program_management_plan';

        $res = $this->db->dbh->exec($sql);

        if ($res === false) {
            throw new \Tuleap\ForgeUpgrade\Bucket\BucketUpgradeNotCompleteException('An error occurred while renaming of plugin_program_management_program_tmp and plugin_program_management_plan_tmp');
        }
    }
}
