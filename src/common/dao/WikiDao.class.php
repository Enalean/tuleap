<?php

/**
 * Copyright (c) STMicroelectronics, 2006. All Rights Reserved.
 *
 * Originally written by Sabri LABBENE, 2008
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
 * along with Codendi. If not, see <http://www.gnu.org/licenses/>.
 */

require_once('include/DataAccessObject.class.php');

/**
 *  Data Access Object for wiki db access from other codendi components
 */
class WikiDao extends DataAccessObject {
    /**
    * Constructs WikiDao
    * @param $da instance of the DataAccess class
    */
    function WikiDao( & $da ) {
        DataAccessObject::DataAccessObject($da);
    }

    /** 
    * This function retreives an id from wiki_page table using the pagename attribute   
    *   
    * @param string $pagename   
    * @param int $group_id   
    * @return int $id id in wiki of a wiki page.   
    */   
    function retrieveWikiPageId($pagename, $group_id){
        $sql = sprintf('SELECT id'.
            ' FROM wiki_page'.
            ' WHERE pagename = %s'.
            ' AND group_id = %d'
            , $this->da->quoteSmart($pagename), $this->da->escapeInt($group_id));
        $res = $this->retrieve($sql);
        if($res && !$res->isError() && $res->rowCount() == 1) {
            $res->rewind();
            if($res->valid()) {
                $row = $res->current();
                $id = $row['id'];
                return $id;
            }
            else {
                return null;
            }
        }
        else {
            return null;
        }
    }

    /**
    * Searches for the latest version of a wiki page
    *
    * @param int $groupId
    * @param string $pagename
    * @return int version number
    */
    function searchCurrentWikiVersion($groupId, $pagename) {
        $version = null;
        $sql = sprintf('SELECT MAX(version) AS version'.
                       ' FROM wiki_page '.
                       '  INNER JOIN wiki_version USING(id)'.
                       ' WHERE group_id = %d'.
                       ' AND pagename = %s',
                       $groupId, $this->da->quoteSmart($pagename));
        $dar = $this->retrieve($sql);
        if($dar && !$dar->isError() && $dar->rowCount() == 1) {
            $row = $dar->current();
            $version = $row['version'];
        }
        return $version;
    }
}
?>
