<?php
/*
 * Copyright (c) Xerox, 2009. All Rights Reserved.
 *
 * Originally written by Nicolas Terray, 2005. Xerox Codendi Team.
 *
 * This file is a part of Codendi.
 *
 * Codendi is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Codendi is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with CodeX; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

require_once('include/DataAccessObject.class.php');

/**
 *  Data Access Object for mailing lists 
 */
class MailingListDao extends DataAccessObject {

    /**
    * Search active (=not deteted) mailing lists 
    * return all active lists
    * @return DataAccessResult
    */
    function searchAllActiveML() {
        $sql = "SELECT * 
                FROM mail_group_list 
                WHERE is_public IN (0,1)";
        return $this->retrieve($sql);
    }

    /**
    * Searches by group_list_id
    * @return DataAccessResult
    */
    function searchByGroupListId($group_list_id) {
        $group_list_id = $this->da->escapeInt($group_list_id);
        $sql = "SELECT * FROM mail_group_list 
                WHERE group_list_id = $group_list_id";
        return $this->retrieve($sql);
    }
}
?>