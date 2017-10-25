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

class b201710241421_remove_empty_comment_from_fulltext_table extends ForgeUpgrade_Bucket
{
    public function description()
    {
        return 'Remove empty comment from tracker_changeset_comment_fulltext';
    }

    public function preUp()
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up()
    {
        $sql = "DELETE FROM tracker_changeset_comment_fulltext
                  WHERE stripped_body = ''";

        $res = $this->db->dbh->exec($sql);

        if ($res === false) {
            $this->rollBackOnError('An error occured while removing empty comments in comment_fulltext table');
        }
    }
}
