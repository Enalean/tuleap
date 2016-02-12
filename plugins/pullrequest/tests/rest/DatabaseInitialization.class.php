<?php
/**
 * Copyright (c) Enalean, 2016. All rights reserved
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/
 */

namespace Tuleap\PullRequest\REST;

use ForgeConfig;

class DatabaseInitialization extends \DatabaseInitialization {

    public function setUp() {
        $this->mysqli->select_db(ForgeConfig::get('sys_dbname'));
        $this->insertPullRequest();
    }

    private function insertPullRequest() {
        echo "Create PullRequest \n";

        $sql = "INSERT INTO plugin_pullrequest_review (repository_id, user_id, creation_date, branch_src, sha1_src, branch_dest, sha1_dest)
                VALUES (1, 102, UNIX_TIMESTAMP(), 'dev', 'fake_sha1_srcaaaaaaaaaaaaaaaaaaaaaaaaaaa', 'master', 'fake_sha1_destaaaaaaaaaaaaaaaaaaaaaaaaaa')";

        $this->mysqli->real_query($sql);
    }
}