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

    /**
     * Delete entry from wiki_page table identified by wiki page Id.
     *
     * @param int $id id of wiki page
     * @return true if there is no error
     */
    function deleteWikiPage($id) {
        $sql = sprintf('DELETE FROM wiki_page'.
                    ' WHERE id=%d', $id);
        return $this->update($sql);
    }

    /**
     * Delete all entries from wiki_version table that refers to the same wiki page identified by  its Id
     *
     * @param int $id id of wiki page
     * @return true if there is no error
     */
    function deleteWikiPageVersion($id) {
        $sql = sprintf('DELETE FROM wiki_version'.
                    ' WHERE id=%d', $id);
        return $this->update($sql);
    }

    /**
     * Delete links from and to wiki page identified by  its Id
     *
     * @param int $id id of wiki page
     * @return true if there is no error
     */
    function deleteLinksFromToWikiPage($id) {
        $sql = sprintf('DELETE FROM wiki_link'.
                    ' WHERE linkfrom=%d'.
                    ' OR linkto=%d', $id, $id);
        return $this->update($sql);
    }

    /**
     * Delete wiki page identified by  its Id from non empty pages list
     *
     * @param int $id id of wiki page
     * @return true if there is no error
     */
    function deleteWikiPageFromNonEmptyList($id) {
        $sql = sprintf('DELETE FROM wiki_nonempty'.
                    ' WHERE id=%d', $id);
        return $this->update($sql);
    }

    /**
     * Delete recent infos of wiki page identified by  its Id.
     *
     * @param int $id id of wiki page
     * @return true if there is no error
     */
    function deleteWikiPageRecentInfos($id) {
        $sql = sprintf('DELETE FROM wiki_recent'.
                    ' WHERE id=%d', $id);
        return $this->update($sql);
    }
    
    /**
     * Update wiki page
     * @param PFUser   $user
     * @param String $new_name
     * @return Boolean
     */
    function updatePageName($user, $new_name) {
        $sql = 'UPDATE wiki_page SET pagename = '.$this->da->quoteSmart($new_name). 
               ' WHERE pagename = '.$this->da->quoteSmart($user->getUserName());
        return $this->update($sql);
    }
    
}

?>
