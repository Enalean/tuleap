<?php
/**
 * Copyright (c) Xerox Corporation, CodeX Team, 2001-2009. All rights reserved
 *
 * This file is a part of CodeX.
 *
 * CodeX is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * CodeX is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Codendi. If not, see <http://www.gnu.org/licenses/>.
 */

class NewsBytesDao extends DataAccessObject {
    public function __construct($da) {
        parent::__construct($da);
        $this->table_name = 'news_bytes';
    }

    public function searchByForumId($forum_id){
        $forum_id= $this->da->quoteSmart($forum_id);
        $sql = "SELECT group_id 
                FROM $this->table_name
                WHERE forum_id=$forum_id";
        return $this->retrieve($sql);
    }
}
?>
