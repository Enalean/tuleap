<?php
/**
 * Copyright (c) Enalean, 2017. All Rights Reserved.
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

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotCamelCaps
class b201703301111_create_table_plugin_git_log_read extends ForgeUpgrade_Bucket
{

    public function description()
    {
        return "Create table plugin_git_log_read_daily";
    }

    public function preUp()
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up()
    {
        $sql = "CREATE TABLE plugin_git_log_read_daily (
                  repository_id int(10) unsigned NOT NULL,
                  user_id int(11) NOT NULL,
                  day int(11) UNSIGNED NOT NULL,
                  git_read int(11) UNSIGNED NOT NULL default 0,
                  PRIMARY KEY (repository_id, user_id, day),
                  INDEX time_idx(day, repository_id)
                );";

        $this->execDB($sql, 'An error occured while creating table plugin_git_log_read_daily');
    }

    protected function execDB($sql, $message)
    {
        $res = $this->db->dbh->exec($sql);
        if ($res === false) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete($message . implode(', ', $this->db->dbh->errorInfo()));
        }
    }
}
