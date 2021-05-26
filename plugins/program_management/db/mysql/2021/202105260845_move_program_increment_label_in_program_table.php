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
final class b202105260845_move_program_increment_label_in_program_table extends ForgeUpgrade_Bucket
{
    public function description(): string
    {
        return 'Move label and sub-label of Program Increment in plugin_program_management_program table';
    }
    public function preUp(): void
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }
    public function up(): void
    {
        $this->db->dbh->beginTransaction();
        $this->addProgramIncrementLabel();
        $this->addProgramIncrementSubLabel();
        $this->moveLabelsInProgramTable();
        $this->deleteLabelsTable();
        $this->db->dbh->commit();
    }

    private function addProgramIncrementLabel(): void
    {
        $this->db->alterTable(
            'plugin_program_management_program',
            'tuleap',
            'program_increment_label',
            'ALTER TABLE plugin_program_management_program ADD COLUMN program_increment_label VARCHAR(255) DEFAULT NULL'
        );
    }

    private function addProgramIncrementSubLabel(): void
    {
        $this->db->alterTable(
            'plugin_program_management_program',
            'tuleap',
            'program_increment_sub_label',
            'ALTER TABLE plugin_program_management_program ADD COLUMN program_increment_sub_label VARCHAR(255) DEFAULT NULL'
        );
    }

    private function moveLabelsInProgramTable(): void
    {
        $sql = 'INSERT INTO plugin_program_management_program (program_project_id, program_increment_label, program_increment_sub_label)
                SELECT program.program_project_id, label, sub_label
                FROM plugin_program_management_program AS program JOIN plugin_program_management_label_program_increment AS label on (program.program_increment_tracker_id = label.program_increment_tracker_id)
                ON DUPLICATE KEY UPDATE program_increment_label=label, program_increment_sub_label=sub_label';

        $this->executeSql($sql);
    }

    private function deleteLabelsTable(): void
    {
        $sql = 'DROP TABLE IF EXISTS plugin_program_management_label_program_increment;';
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
