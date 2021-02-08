<?php
/**
 *  Copyright (c) Maximaster, 2020. All rights reserved
 *
 *  This file is a part of Tuleap.
 *
 *  Tuleap is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  Tuleap is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with Tuleap. If not, see <http://www.gnu.org/licenses/
 */

class b202011201410_add_tracker_private_comment_permission extends ForgeUpgrade_Bucket // @codingStandardsIgnoreLine
{
    public function description()
    {
        return 'Add tracker_private_comment_permission for for storing permission to private comments';
    }

    public function preUp()
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }
    public function up()
    {
        $sql = "CREATE TABLE IF NOT EXISTS tracker_private_comment_permission (
                  id            INT(11) NOT NULL AUTO_INCREMENT,
                  tracker_id    INT(11) NOT NULL,
                  ugroup_id     INT(11) NOT NULL,
                  PRIMARY KEY (id)
                ) ENGINE=InnoDB";

        $this->db->createTable('tracker_private_comment_permission', $sql);
    }
}
