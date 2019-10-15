<?php
/**
 * Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
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

class b201910151454_create_modal_new_layout_table extends ForgeUpgrade_Bucket //phpcs:ignore
{
    public function description(): string
    {
        return 'Create plugin_tracker_new_layout_modal_user table.';
    }

    public function preUp(): void
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up(): void
    {
        $this->db->dbh->beginTransaction();
        $this->createTable();
        $this->addAllExistingUserIds();
        $this->db->dbh->commit();
    }

    private function createTable(): void
    {
        $sql = 'CREATE TABLE IF NOT EXISTS plugin_tracker_new_layout_modal_user (
                    user_id INT(11) PRIMARY KEY
                ) ENGINE=InnoDB';

        $res = $this->db->dbh->exec($sql);
        if ($res === false) {
            $this->rollBackOnError('An error occured while adding plugin_tracker_new_layout_modal_user table.');
        }
    }

    private function addAllExistingUserIds(): void
    {
        $sql = "INSERT INTO plugin_tracker_new_layout_modal_user (user_id)
                SELECT DISTINCT user_id
                FROM user
                WHERE user_id >= 100";

        $res = $this->db->dbh->exec($sql);
        if ($res === false) {
            $this->rollBackOnError('An error occured while adding user ids.');
        }
    }

    private function rollBackOnError($message): void
    {
        $this->db->dbh->rollBack();
        throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete($message);
    }
}
