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

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotCamelCaps
final class b202105250900_move_program_increment_tracker_id_in_program_table extends ForgeUpgrade_Bucket
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
        $this->db->dbh->beginTransaction();
        $this->addProgramIncrementTrackerIdColumnInProgram();
        $this->addProjectIdColumnInPlan();
        $this->changePlanPrimaryKeyAndDropProgramIncrementTrackerId();
        $this->db->dbh->commit();
    }

    private function addProgramIncrementTrackerIdColumnInProgram(): void
    {
        $this->db->alterTable(
            'plugin_program_management_plan_config',
            'tuleap',
            'program_increment_tracker_id',
            'ALTER TABLE plugin_program_management_program ADD COLUMN program_increment_tracker_id INT(11) NOT NULL'
        );

        $sql = 'INSERT INTO plugin_program_management_program (program_project_id, program_increment_tracker_id)
                SELECT group_id, id
                FROM tracker JOIN plugin_program_management_plan ON (tracker.id = plugin_program_management_plan.program_increment_tracker_id)
                ON DUPLICATE KEY UPDATE program_increment_tracker_id=id';

        $this->executeSql($sql);
    }

    private function addProjectIdColumnInPlan(): void
    {
        $this->db->alterTable(
            'plugin_program_management_plan',
            'tuleap',
            'project_id',
            'ALTER TABLE plugin_program_management_plan ADD COLUMN project_id INT(11) NOT NULL'
        );

        $sql = 'INSERT INTO plugin_program_management_plan (program_increment_tracker_id, project_id, plannable_tracker_id)
                SELECT id, group_id, plan.plannable_tracker_id
                FROM tracker JOIN plugin_program_management_plan AS plan ON (tracker.id = plan.program_increment_tracker_id)
                ON DUPLICATE KEY UPDATE program_increment_tracker_id=id, project_id=group_id, plannable_tracker_id=plan.plannable_tracker_id;';

        $this->executeSql($sql);
    }

    private function changePlanPrimaryKeyAndDropProgramIncrementTrackerId(): void
    {
        $sql = "ALTER TABLE plugin_program_management_plan DROP PRIMARY KEY;";
        $this->executeSql($sql);

        $sql = "ALTER TABLE plugin_program_management_plan ADD PRIMARY KEY (project_id, plannable_tracker_id);";
        $this->executeSql($sql);

        $sql = "ALTER TABLE plugin_program_management_plan DROP COLUMN program_increment_tracker_id;";
        $this->executeSql($sql);
    }

    private function executeSql($sql): void
    {
        $result = $this->db->dbh->exec($sql);
        if ($result === false) {
            $error_message = implode(', ', $this->db->dbh->errorInfo());
            $this->rollBackOnError($error_message);
        }
    }

    private function rollBackOnError(string $message): void
    {
        $this->db->dbh->rollBack();
        throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete($message);
    }
}
