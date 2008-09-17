<?php
/**
 * Copyright (c) STMicroelectronics, 2008. All Rights Reserved.
 *
 * Originally written by Sabri LABBENE, 2008
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
 * along with CodeX; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */
require_once('database.php');
require_once('CodeXUpgrade.class.php');

class Update_006 extends CodeXUpgrade {

    function _process() {
        $this->ids_list = array();
        $this->ids = array();
        $this->escaped = array();
        
        echo $this->getLineSeparator();
        print("Gathering items to synchronize ...");
        echo $this->getLineSeparator();
        $projects_with_docman = $this->getProjectsWithDocman();
	
        // Gather docman item ids for a project
        foreach($projects_with_docman as $id) {
            $this->ids_list[$id] = $this->getDocmanItemIdsByProject($id);
        }

        // Remove items that don't reference an existant wiki page.
        $this->ids_list = $this->removeNoWikiItems($this->ids_list);

        // If there are some projects that don't have docman items referencing existants wiki pages, we remove the project empty array.
        $this->ids = $this->removeProjectsWithNoItems($this->ids_list);

        // Free this array, we dont need it any more
        unset($this->ids_list);

        // Remove wiki pages that have specific permissions already set at wiki side.
        $this->ids = $this->removeWikiPagesWithSpecificPerms($this->ids);

        // Clean empty projects
        $this->ids = $this->removeProjectsWithNoItems($this->ids);

        // Remove wiki pages that are referenced more than once in the same project.
        $this->ids = $this->removeMultiplereferences($this->ids);

        print("Items list created.");
        echo $this->getLineSeparator();
        print("Permissions synchronization started ...");
        echo $this->getLineSeparator();

        //Synchronize Permissions
        $this->synchronizer($this->ids);
        print("Synchronization successfully completed.");
        echo $this->getLineSeparator();
        $this->displayNotSynchronizedItemsList();
        echo $this->getLineSeparator();
    }

    /**
     * Creates the list of projects that have any number of docman items.
     *
     * @return array $project_ids array of project ids
     *
     */
    function getProjectsWithDocman() {
        $project_ids = array();
        $sql = "SELECT group_id FROM plugin_docman_item GROUP BY group_id";
        $dar = $this->retrieve($sql);
        if ($dar && !$dar->isError() && $dar->rowCount() > 0) {
            $dar->rewind();
            while ($dar->valid()) {
                $row = $dar->current();
                $project_ids[] = $row['group_id'];
                $dar->next();
            }
        }
        return $project_ids;
    }

    /**
     * Gathers docman items by project.
     *
     * @param int $project_id
     *
     * @return array $docman_items a list of docman items ids of a project.
     */
    function getDocmanItemIdsByProject($project_id) {
        $obsoleteToday = $this->getObsoleteToday();
        $sql = "SELECT item_id FROM plugin_docman_item".
              " WHERE group_id=" . $project_id .
              " AND wiki_page IS NOT NULL".
              " AND delete_date IS NULL".
              " AND (obsolescence_date > " . $obsoleteToday . " OR obsolescence_date=0)";
        $dar = $this->retrieve($sql);
        if ($dar && !$dar->isError() && $dar->rowCount() > 0) {
            $dar->rewind();
            while($dar->valid()) {
                $row = $dar->current();
                $docman_items[] = $row['item_id'];
                $dar->next();
            }
            return $docman_items;
        }
        else {
            return null;
        }
    }

    /**
     *
     * Gets today date.
     *
     */
    function getObsoleteToday() {
        $today = getdate();
        $time = mktime(0,0,1,$today['mon'], $today['mday'], $today['year']);
        return $time;
    }

    /**
     *
     * Removes items that dont reference an existant wiki page in wiki service
     *
     * @param array $hash project_id => item_ids(array)
     *
     * @return array $hash project_id => item_ids(array) cleaned.
     */
    function removeNoWikiItems($hash) {
        foreach($hash as $prj => $item_ids) {
            if(is_array($item_ids)) {
                foreach($item_ids as $index => $id) {
                    if(!$this->isItemAWikiPage($id, $prj)) {
                        unset($hash[$prj][$index]);
                    }
                }
            }
        }
        return $hash;
    }

    /**
     *
     * Removes a project from projects list if it doesn't contain any docman item.
     *
     * @param array $hash project_id => item_ids(array)
     *
     * @return array $hash project_id => item_ids(array) cleaned.
     */
    function removeProjectsWithNoItems($hash) {
        foreach($hash as $prj => $ids) {
            if(empty($ids)) {
                unset($hash[$prj]);			
            }
        }
        return $hash;
    }

    /**
     *
     * Removes wiki pages that have specific perms already setted at wiki side.
     *
     * @param array $hash docman item id => wiki page id
     *
     * @return array $hash docman item id => wiki page id (cleaned)
     *
     */
    function removeWikiPagesWithSpecificPerms($hash) {
        foreach($hash as $prj => $items) {
            foreach($items as $docman_id => $wiki_id) {
                if($this->wikiHaveSpecificPerms($wiki_id)) {
                    unset($hash[$prj][$docman_id]);
                }
            }
        }
        return $hash;
    }

    /**
     *
     * Removes wiki pages referenced more than one time in the same project
     * No permissions synchronization for those pages. 
     *
     * @param array $hash docman item id => wiki page id
     *
     * @return array $hash docman item id => wiki page id (cleanned)
     */
    function removeMultipleReferences($hash){
        foreach($hash as $prj => $ids) {
            foreach($ids as $key => $value) {
                $result = array_search($value, $ids);
                if($result != $key) {
                    if(array_key_exists($key, $ids)) {
                        unset($hash[$prj][$key]);
                    }
                    if(array_key_exists($result, $ids)) {
                        unset($hash[$prj][$result]);
                    }
                    $this->escaped[$prj][$key] = $value;
                }
            }
        }
        return $hash;
    }

    /**
     *
     * Output the list of escaped pages.
     *
     *
     */
    function displayNotSynchronizedItemsList(){
        if(empty($this->escaped)){
            print("No items escaped");
            echo $this->getLineSeparator();
        }
        else {
            print("The following items were escaped because they are referenced more than one time in the same project and may have different set of permissions:");
            echo $this->getLineSeparator();
            foreach($this->escaped as $prj => $ids) {
                foreach($ids as $key => $value) {
                    print("Wiki page: " . $value . " in project: " . $prj);
                    echo $this->getLineSeparator();
                }
            }
        }
    }

    /**
     *
     * This checks if a wiki page have a specific permissions already set at wiki service side.
     *
     * @param $id_in_wiki wiki page id
     *
     * @return boolean
     */
    function wikiHaveSpecificPerms($id_in_wiki) {
        $sql = "SELECT * FROM permissions WHERE object_id=". $id_in_wiki . " AND permission_type='WIKIPAGE_READ'";
        $dar = $this->retrieve($sql);
        if ($dar && !$dar->isError() && $dar->rowCount() > 0) {
            return true;
        }
        else {
            return false;
        }
    }

    /**
     * This checks if the docman item really refrences an existant wiki page or not in the same project.
     *
     * It also creates the correspondance between valid docman item ids and ids in wiki of their wiki pages.
     *
     * @param int $item_id docman item id
     * @param int $group_id project id
     *
     * @return boolean 
     */
    function isItemAWikiPage($item_id, $group_id) {
        $sql = "select wiki_page from plugin_docman_item where item_id=". $item_id;
        $dar = $this->retrieve($sql);
        if($dar && !$dar->isError() && $dar->rowCount() == 1) {
            $dar->rewind();
            $row = $dar->current();
            // check existance in wiki service.
            $pagename = $row['wiki_page'];
            $sql1 = "SELECT id FROM wiki_page WHERE pagename='" . db_es($pagename) . "' AND group_id=". $group_id;
            $dar1 = $this->retrieve($sql1);
            if($dar1 && !$dar1->isError() && $dar1->rowCount() == 1) { // The wiki page exists in wiki service
                $dar1->rewind();
                $row1 = $dar1->current();
                $id_in_wiki = $row1['id'];
                // construct correspondance betwwen docman and wiki ids: docman_item_id => wiki_page_id
                $this->ids[$group_id][$item_id] = $id_in_wiki;
                return true;
            }
            else {
                return false;
            }
        }
        else {
            return false;
        }
    }

    /**
     *
     * This wals through the list of correspondant ids and calls for perms synchronization fro each couple of docman_item_id => wiki_page_id.
     *
     * @param array $hash docman_item_id => wiki_page_id
     *
     */
    function synchronizer($hash) {
        foreach($hash as $prj => $ids) {
            foreach($ids as $docman_item_id => $wiki_item_id) {
                $perms[] = $this->synchronizePermissions($docman_item_id, $wiki_item_id);
            }
        }
    }

    /**
     * Synchronizes permissions between docman items and wiki pages
     *
     * It fetches a docman item permissions then applyies equivalent ones on the correspondant wiki page at wiki service.
     *
     * @param int $docman_item_id
     * @param int $wiki_page_id
     *
     */
    function synchronizePermissions($docman_item_id, $wiki_page_id) {
        $sql = "SELECT * FROM permissions WHERE object_id=". $docman_item_id;
        $dar = $this->retrieve($sql);
        $dar->rewind();
        while ($dar->valid()){
            $row = $dar->current();
            if($row['permission_type'] == 'PLUGIN_DOCMAN_READ' || $row['permission_type'] == 'PLUGIN_DOCMAN_WRITE' || $row['permission_type'] == 'PLUGIN_DOCMAN_MANAGE') {
                $sql_sync = "INSERT INTO permissions (permission_type, object_id, ugroup_id) ".
                           " VALUES('WIKIPAGE_READ', " . $wiki_page_id . ", " . $row['ugroup_id'] . ")";
                $this->update($sql_sync);
            }
            $dar->next();
        }
    }
}
?>