#!/usr/share/tuleap/src/utils/php-launcher.sh
<?php
/**
 * Copyright (c) Enalean, 2015. All Rights Reserved.
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

require_once __DIR__ . '/../../../src/www/include/pre.php';

$data_access = CodendiDataAccess::instance();

$sql = 'CREATE OR REPLACE VIEW ftpusers AS
            SELECT user_name as username, unix_pw as password, unix_uid+20000 as uid, unix_uid+20000 as gid, CONCAT("/home/users/", user_name) as home, shell
            FROM user
            WHERE status IN ("A", "R")
            AND user_id > 100;';
$result = $data_access->query($sql);
if ($result->isError()) {
    file_put_contents('php://stderr', "The view ftpusers can not be updated\n");
} else {
    print("Success: the view ftpusers has been updated\n");
}
