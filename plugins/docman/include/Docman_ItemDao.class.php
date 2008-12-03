<?php
/**
 * Copyright (c) STMicroelectronics, 2006. All Rights Reserved.
 *
 * Originally written by Manuel Vacelet, 2006
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
 *
 * 
 */
require_once('DocmanConstants.class.php');
require_once('common/dao/include/DataAccessObject.class.php');
require_once('Docman_SqlFilter.class.php');
require_once('Docman_SqlReportColumn.class.php');

class Docman_ItemDao extends DataAccessObject {

    function Docman_ItemDao(&$da) {
        DataAccessObject::DataAccessObject($da);
    }
    
    /**
     * Return the timestamp of the current day at 00:00
     */
    function getObsoleteToday() {
        $today = getdate();
        $time = mktime(0,0,1,$today['mon'], $today['mday'], $today['year']);
        return $time;
    }

    /**
     * Return the SQL statement that exclude the items that are obsolete as of today.
     * static
     */
    function getExcludeObsoleteItemsStmt($table) {
        $sql = '';
        $sql .= '('.$table.'.obsolescence_date = 0 OR ';
        $sql .= ' '.$table.'.obsolescence_date > '.Docman_ItemDao::getObsoleteToday().')';
        return $sql;
    }

    /**
     * Return the SQL statement that exclude the items that are deleted.
     */
    function getExcludeDeletedItemsStmt($table) {
        $sql = $table.'.delete_date IS NULL';
        return $sql;
    }

    /**
     * Return the SQL statements that exclude deleted & obsolete items.
     */
    function getCommonExcludeStmt($table) {
        return Docman_ItemDao::getExcludeDeletedItemsStmt($table).' AND '.
            Docman_ItemDao::getExcludeObsoleteItemsStmt($table);
    }

    /**
     * Return the row that match given id.
     *
     * @return DataAccessResult
     */
    function searchById($id, $params = array()) {
        $_id = (int) $id;
        return $this->_searchWithCurrentVersion(' i.item_id = '.$_id, '', '', array(), $params);
    }

    function searchByIdList($idList) {        
        if(is_array($idList) && count($idList) > 0) {
            $sql_where = sprintf(' i.item_id IN (%s)', implode(', ', $idList));
        }
        return $this->_searchWithCurrentVersion($sql_where, '', '');
    }

    /**
     * Look for a folder by title.
     *
     * @param $title    Either a string to search or an array of string.
     * @param $groupId  The group id on which the search applies
     * @param $parentId The parent folder where to search.
     */
    function searchByTitle($title, $groupId=null, $parentId=null) {
        if(is_array($title)) {
            $where = ' i.title IN ("'.implode('", "', array_map('db_es', $title)).'")';
        } else {
            $where = ' i.title = '.$this->da->quoteSmart($title);
        }
        if($groupId !== null) {
            $where .= ' AND i.group_id = '.$this->da->escapeInt($groupId);
        }
        if($parentId !== null) {
            $where .= ' AND i.parent_id = '.$this->da->escapeInt($parentId);
        }

        $order = ' ORDER BY version_date DESC';
        return $this->_searchWithCurrentVersion($where, '', $order);
    }

    function searchObsoleteByGroupId($groupId) {
        $sql = '';
        $sql .= $this->_getItemSearchSelectStmt();
        $sql .= $this->_getItemSearchFromStmt();
        $sql .= sprintf(' WHERE i.group_id = %d'.
                        ' AND (obsolescence_date > 0'.
                        '  AND obsolescence_date < %d)'.
                        ' ORDER BY obsolescence_date DESC',
                        $groupId,
                        $this->getObsoleteToday());
        return $this->retrieve($sql);
    }

    /**
     * Return the list of items for a given projet according to filters
     *
     * @return DataAccessResult
     */
    function searchByGroupId($id, &$report, $params) {
        // Where clause        
        // Select on group_id
        $_id = (int) $id;
        $sql_where = ' i.group_id = '.$_id;
                
        //
        // Order clause        
        $sql_order = '';


        $fromStmts = array();

        //
        // Report 
        if($report !== null) {
            // Filters
            $fi = $report->getFilterIterator();
            $fi->rewind();
            while($fi->valid()) {
                $f = $fi->current();

                $sqlFilter = Docman_SqlFilterFactory::getFromFilter($f);
                if($sqlFilter !== null) {   
                    // Handle 'from' clause
                    $fromStmts = array_merge($fromStmts, $sqlFilter->getFrom());

                    // Handle 'where' clause
                    $where = $sqlFilter->getWhere();
                    if($where != '') {
                        $sql_where .= ' AND '.$where;
                    }
                }

                $fi->next();
            }

            // Sort
            $ci = $report->getColumnIterator();
            $fi->rewind();
            while($ci->valid()) {
                $c = $ci->current();
                
                $sqlCol = Docman_SqlReportColumnFactory::getFromColumn($c);
                if($sqlCol !== null) {
                    $order = $sqlCol->getOrderBy();
                    if($order != '') {
                        if($sql_order != '') {
                            $sql_order .= ', ';
                        }
                        $sql_order .= $order;
                    }
                }

                $ci->next();
            }
        }

        // Prepare 'order' clause if any
        if($sql_order != '') {
            $sql_order = ' ORDER BY '.$sql_order;
        }
        
        $from = array_unique($fromStmts);
             
        return $this->_searchWithCurrentVersion($sql_where, '', $sql_order, $from, $params);
    }
        
    function _getItemSearchSelectStmt() {
        $sql = 'SELECT i.*, '.
            ' v.id as version_id, v.number as version_number,'.
            ' v.user_id as version_user_id, v.label as version_label,'.
            ' v.changelog as version_changelog, v.date as version_date,'.
            ' v.filename as version_filename, v.filesize as version_filesize,'.
            ' v.filetype as version_filetype, v.path as version_path, '.
            ' 1 as folder_nb_of_children ';
        return $sql;
    }

    /**
     * The 2 LEFT JOIN statements gives automaticaly the most recent version of
     * each item because there is a 'v2.id IS NULL' in the WHERE part of the
     * query.
     */
    function _getItemSearchFromStmt() {
        $sql = 'FROM plugin_docman_item AS i'.
            ' LEFT JOIN plugin_docman_version AS v'.
            '  ON (i.item_id = v.item_id)'.
            ' LEFT JOIN plugin_docman_version AS v2'.
            '  ON (v2.item_id = v.item_id AND v.number < v2.number) ';
        return $sql;
    }

    /**
     * $params['ignore_deleted'] boolean By default the query *exclude* deleted items.
     * $params['ignore_obsolete'] boolean By default the query *include* obsolete items.
     */
    function _searchWithCurrentVersion($where, $group = '', $order = '', $from = array(), $params = array()) {
        $sql = '';
        $sql .= $this->_getItemSearchSelectStmt();
        $sql .= $this->_getItemSearchFromStmt();
        $sql .= (count($from) > 0 ? ' LEFT JOIN '.implode(' LEFT JOIN ', $from) : '')
            .' WHERE 1 AND ';
        if (!isset($params['ignore_deleted']) || !$params['ignore_deleted']) {
            $sql .= ' '.$this->getExcludeDeletedItemsStmt('i').' AND ';
        }
        if (isset($params['ignore_obsolete']) && $params['ignore_obsolete'] == true) {
            $sql .= $this->getExcludeObsoleteItemsStmt('i').' AND ';
        }
        if(isset($params['ignore_folders']) && $params['ignore_folders'] == true) {
            $sql .= ' i.item_type <> '.PLUGIN_DOCMAN_ITEM_TYPE_FOLDER.' AND ';
        }
        // Related to the 2 LEFT JOIN on docman_version in _getItemSearchFromStmt()
        $sql .= ' v2.id IS NULL AND ';

        $sql .= $where . $group . $order;
        //print $sql."<br>";
        return $this->retrieve($sql);
    }
    
    /**
     * Return filters widely use when looking for items.
     *
     * Those filters are:
     * - ignore deleted items.
     * - ignore obsolete items.
     *
     * @param $table The table name on which the filters applies.
     * @param $ignoreDeleted Ignore delted items if true.
     * @param $ignoreObsolete Ignore obsolete items if true.
     * @return array An array of 'WHERE' statements.
     */
    function _getCommonItemFilters($table='i', $ignoreDeleted=true, $ignoreObsolete=true) {
        $filters = array();
        if($ignoreDeleted) {
            $filters['del'] = $this->getExcludeDeletedItemsStmt($table);
        }
        if($ignoreObsolete) {
            $filters['obs'] = $this->getExcludeObsoleteItemsStmt($table);
        }
        return $filters;
    }

    /**
     * Create a string from an array of sql statements.
     *
     * @param $op Operator (AND, OR, LEFT JOIN, ...)
     * @param $stmtArray Array of statements.
     * @return string
     */
    function _stmtArrayToString($op, $stmtArray) {
        $str = '';
        if(count($stmtArray) > 0) {
            $str = ' '.$op.' '.implode(' '.$op.' ', $stmtArray);
        }
        return $str;
    }

    /**
     * Return the list of user preferences regarding collapsed folders for
     * given group_id and user_id.
     *
     * @return DataAccessResult
     */
    function searchExpandedUserPrefs($group_id, $user_id) {
        $pref_base = PLUGIN_DOCMAN_EXPAND_FOLDER_PREF.'_'.((int)$group_id);

        $sql = sprintf('SELECT preference_name, preference_value'
                       .' FROM user_preferences'
                       .' WHERE user_id=%d'
                       .' AND preference_name LIKE "%s"'
                       ,$user_id
                       ,$pref_base.'_%');

        return $this->retrieve($sql);
    }  

    function createFromRow($row) {
        if (isset($row['create_date']) && $row['create_date'] != '') {
            $updateParent = false;
        } else {
            $updateParent = true;
            $row['create_date'] = time();
        }

        if (!isset($row['update_date']) || $row['update_date'] == '') {
            $row['update_date'] = time();
        }
        
        $arg    = array();
        $values = array();
        $cols   = array('parent_id', 'group_id', 'title', 'description', 'create_date', 'update_date', 'user_id', 'status', 'obsolescence_date', 'rank', 'item_type', 'link_url', 'wiki_page', 'file_is_embedded');
        foreach ($row as $key => $value) {
            if (in_array($key, $cols)) {
                $arg[]    = $key;
                $values[] = $this->da->quoteSmart($value);
            }
        }
        if (count($arg)) {
            $sql = 'INSERT INTO plugin_docman_item '
                .'('.implode(', ', $arg).')'
                .' VALUES ('.implode(', ', $values).')';
            return $this->_createAndReturnId($sql, $updateParent);
        } else {
            return false;
        }
    }

    /**
     * Creates an item by calling the given SQL request, and returns the new ID
     *  
     * @param $sql          SQL request
     * @param $updateParent Determines if the parent folder "update date" must be updated
     */
    function _createAndReturnId($sql, $updateParent) {
        $inserted = $this->update($sql);
        if ($inserted) {
            $dar = $this->retrieve("SELECT LAST_INSERT_ID() AS id");
            if ($row = $dar->getRow()) {
                $inserted = $row['id'];
                if ($inserted && $updateParent) {
                    $this->_updateUpdateDateOfParent($row['id']);
                }
            } else {
                $inserted = $dar->isError();
            }
        }
        return $inserted;
    }
    /**
     * Update a row in the table plugin_docman_item 
     *
     * @return true if there is no error
     */
    function updateById($item_id, $parent_id=null, $group_id=null, $title=null,
                    $description=null, $create_date=null, $update_date=null, 
                    $user_id=null, $rank=null, $item_type=null, $link_url=null,
                    $wiki_page=null, $file_is_embedded=null) {       
       
        $argArray = array();
		
        if($parent_id !== null) {
            $argArray[] = 'parent_id='.((int) $parent_id);
        }

        if($group_id !== null) {
            $argArray[] = 'group_id='.((int) $group_id);
        }

        if($title !== null) {
            $argArray[] = 'title='.$this->da->quoteSmart($title);
        }

        if($description !== null) {
            $argArray[] = 'description='.$this->da->quoteSmart($description);
        }

        if($create_date !== null) {
            $argArray[] = 'create_date='.((int) $create_date);
        }

        if($update_date !== null) {
            $argArray[] = 'update_date='.((int) $update_date);
        }

        if($user_id !== null) {
            $argArray[] = 'user_id='.((int) $user_id);
        }

        if($rank !== null) {
            $argArray[] = 'rank='.((int) $rank);
        }

        if($item_type !== null) {
            $argArray[] = 'item_type='.((int) $item_type);
        }

        if($link_url !== null) {
            $argArray[] = 'link_url='.$this->da->quoteSmart($link_url);
        }

        if($wiki_page !== null) {
            $argArray[] = 'wiki_page='.$this->da->quoteSmart($wiki_page);
        }

        if($file_is_embedded !== null) {
            $argArray[] = 'file_is_embedded='.((int) $file_is_embedded);
        }
        
        $sql = 'UPDATE plugin_docman_item'
            .' SET '.implode(', ', $argArray)
            .' WHERE item_id='.((int) $item_id);

        $inserted = $this->update($sql);
        if ($inserted) {
            $this->_updateUpdateDateOfParent($this->da->quoteSmart($item_id));
        }
        return $inserted;
    }

    function updateFromRow($row) {
        $updated = false;
        $id = false;
        if (isset($row['id'])) {
            $id = $row['id'];
        } else if (isset($row['item_id'])) {
            $id = $row['item_id'];
        }
        
        if (isset($row['update_date']) && $row['update_date'] != '') {
            $updateParent = false;
        } else {
            $updateParent = true;
            $row['update_date'] = time();
        }
        
        if ($id) {
            $dar = $this->searchById($id);
            if (!$dar->isError() && $dar->valid()) {
                $current =& $dar->current();
                $set_array = array();
                foreach($row as $key => $value) {
                    if ($key != 'id' && $value != $current[$key]) {
                        $set_array[] = $key .' = '. $this->da->quoteSmart($value);
                    }
                }
                $sql = 'UPDATE plugin_docman_item'
                    .' SET '.implode(' , ', $set_array)
                    .' WHERE item_id='. $this->da->quoteSmart($id);
                $updated = $this->update($sql);
                if ($updated && $updateParent) {
                    $this->_updateUpdateDateOfParent($this->da->quoteSmart($id));
                }
            }
        }
        return $updated;
    }

    function _updateUpdateDateOfParent($item_id_quoted) {
        $sql = 'SELECT parent_id, update_date FROM plugin_docman_item WHERE item_id = '. $item_id_quoted;
        $dar = $this->retrieve($sql);
        if ($dar && !$dar->isError() && $dar->valid()) {
            $item = $dar->current();
            $sql = 'UPDATE plugin_docman_item SET update_date = '. $item['update_date'] .' WHERE item_id = '. $item['parent_id'];
            $this->update($sql);
        }
    }

    function massUpdate($srcItemId, $mdLabel, $itemIdArray) {
        $sql = sprintf('UPDATE plugin_docman_item item_src,  plugin_docman_item item_dst'.
                       ' SET item_dst.'.$mdLabel.' = item_src.'.$mdLabel.
                       ' WHERE item_src.item_id = %d'.
                       '  AND item_dst.item_id IN (%s)',
                       $srcItemId,
                       implode(',',$itemIdArray));
        return $this->update($sql);
    }


    /**
     * Delete entry that match $item_id in plugin_docman_item
     *
     * @param $item_id int
     * @return true if there is no error
     */
    function delete($item_id) {
        $sql = sprintf("DELETE FROM plugin_docman_item WHERE item_id=%d",
                       $item_id);

        $deleted = $this->update($sql);
        
        return $deleted;
    }
    
    /**
     * Check the number of children of root.
     *
     * This function count the number of children of root. Only the case of
     * unique children matters because we need the item_id in this case
     * otherwise, if more than one child exists is the only useful info (this
     * is why there is a LIMIT 2).
     */
    function hasRootOnlyOneChild($groupId) {
        $cFilters = $this->_stmtArrayToString('AND', $this->_getCommonItemFilters());
        $sql = sprintf('SELECT i.item_id'.
                       ' FROM plugin_docman_item i'.
                       ' JOIN plugin_docman_item r'.
                       '  ON (i.parent_id = r.item_id)'.
                       ' WHERE r.parent_id = 0'.
                       ' AND r.group_id = %d'.
                       $cFilters.
                       ' LIMIT 2',
                       $groupId);
        $dar = $this->retrieve($sql);
        return $dar;
    }

    /**
     * This function intend to reorganize items under $parentId
     *
     * This function only affect rank parameter of sibling bellow $parentId.
     * -> It doesn't move item itself except for 'down' and 'up'. These 2
     * special move (that require $item_id param) change ranking of the item
     * to move up or down.
     */
    function _changeSiblingRanking($parentId, $ordering, $item_id = false) {
        $rank = 0;
        switch ($ordering) {
            case 'beginning':
            case 'end':
                $_select = $ordering == 'end' ? 'MAX(rank)+1' : 'MIN(rank)-1';
                $sql = sprintf('SELECT %s AS rank'.
                               ' FROM plugin_docman_item'.
                               ' WHERE parent_id = %d',
                               $_select,
                               $parentId);
                $dar = $this->retrieve($sql);
                if ($dar && $dar->valid()) {
                    $row = $dar->current();
                    $rank = $row['rank'];
                }
                break;
            case 'down':
            case 'up':
                if($item_id !== false) {
                    if ($ordering == 'down') {
                        $op    = '>';
                        $order = 'ASC';
                    } else {
                        $op    = '<';
                        $order = 'DESC';
                    }
                    $sql = sprintf('SELECT i1.item_id as item_id, i1.rank as rank'.
                                   ' FROM plugin_docman_item i1'.
                                   '  INNER JOIN plugin_docman_item i2 USING(parent_id)'.
                                   ' WHERE i2.item_id = %d'.
                                   '  AND i1.rank %s i2.rank'.
                                   ' ORDER BY i1.rank %s'.
                                   ' LIMIT 1', 
                                   $item_id,
                                   $op,
                                   $order);
                    $dar = $this->retrieve($sql);
                    if ($dar && $dar->valid()) {
                        $row = $dar->current();
                        
                        $sql = sprintf('UPDATE plugin_docman_item i1, plugin_docman_item i2'.
                                       ' SET i1.rank = i2.rank, i2.rank = %d'.
                                       ' WHERE i1.item_id = %d '.
                                       '  AND i2.item_id = %d',
                                       $row['rank'],
                                       $row['item_id'],
                                       $item_id);
                        $res = $this->update($sql);
                        //$can_update = false;
                        // Message for setNewParent function
                        $rank = -1;
                    }
                }
                break;
            default:
                $rank = $ordering?$ordering:0;
                $sql = sprintf('UPDATE plugin_docman_item'.
                               ' SET rank = rank + 1 '.
                               ' WHERE  parent_id = %d '.
                               '  AND rank >= %d',
                               $parentId,
                               $rank);
                $this->update($sql);
                break;
        }

        return $rank;
    }

    function setNewParent($item_id, $new_parent_id, $ordering) {
        $can_update = true;

        $rank = $this->_changeSiblingRanking($new_parent_id, $ordering, $item_id);
        if($ordering == 'down' || $ordering == 'up') {
            $can_update = ($rank == -1) ? false : true;
        }
        $res = false;
        if ($can_update) {
            $sql = sprintf('UPDATE plugin_docman_item SET parent_id = %s, rank = %s '.
                ' WHERE  item_id = %s ',
                $this->da->quoteSmart($new_parent_id),
                $this->da->quoteSmart($rank),
                $this->da->quoteSmart($item_id)
            );
            $res = $this->update($sql);
        }
        return $res;
    }
    
    function searchByParentsId($parents) {
        $sql = sprintf('SELECT * FROM plugin_docman_item WHERE parent_id IN (%s) AND delete_date IS NULL AND (obsolescence_date = 0 OR obsolescence_date > '.$this->getObsoleteToday().') ORDER BY rank',
            implode(', ', $parents)
        );
        return $this->retrieve($sql);
    }
    
    function searchRootIdForGroupId($group_id) {
        $sql = sprintf('SELECT item_id FROM plugin_docman_item WHERE parent_id = 0 '.
            ' AND group_id = %s ',
            $this->da->quoteSmart($group_id)
        );
        $dar = $this->retrieve($sql);
        $id = false;
        if ($dar && $dar->valid()) {
            $row = $dar->current();
            $id = $row['item_id'];
        }
        return $id;
    }

    /**
     * Search for subfolders in the given item array.
     *
     * @param array $parentIds List of parent ids
     * @return DataAccessResult
     */
    function searchSubFolders($parentIds = array()) {        
        if(is_array($parentIds) && count($parentIds) > 0) {           
            $sql = sprintf('SELECT *'
                           .' FROM plugin_docman_item'
                           .' WHERE delete_date IS NULL'
                           .' AND parent_id IN (%s)'
                           .' AND item_type = %d'
                           , implode(',', $parentIds)
                           , PLUGIN_DOCMAN_ITEM_TYPE_FOLDER);
            return $this->retrieve($sql);
        }
        else {
            return null;
        }
    }

    /**
     * Search of all childrens of the items given in parameter.
     *
     * @param array $parentIds List of parents.
     * @param array $params
     * @return DataAccessResult
     */
    function searchChildren($parentIds, $params) {
        $where = " i.parent_id in (".implode(',', $parentIds).")";
        return $this->_searchWithCurrentVersion($where, '', '', array(), $params);
    }

    /*
     * Return obsolete documents between the $tsStart and $tsEnd timestamps. It
     * only concerns Active projects and non deleted documents. 
     *
     * @note: Cross-Project query.
     */
    function searchObsoleteAcrossProjects($tsStart, $tsEnd) {
        $sql = sprintf('SELECT i.*'.
                       ' FROM plugin_docman_item i, groups g'.
                       ' WHERE delete_date IS NULL'.
                       ' AND (i.obsolescence_date >= %d'.
                       '   AND i.obsolescence_date <= %d)'.
                       ' AND g.group_id = i.group_id'.
                       ' AND g.status = "A"',
                       $tsStart,
                       $tsEnd);
        return $this->retrieve($sql);
    }

    /**
     * Checks if a wiki page is referenced in docman. Referencing docman items shouldn't be obsolete or deleted.
     *
     * @param string $wikipage wiki page name.
     * @param int $group_id project id.
     *
     * @return boolean.
     */
    function isWikiPageReferenced($wikipage, $group_id) {
        $obsoleteToday = $this->getObsoleteToday();
        $sql = sprintf('SELECT item_id'.
            ' FROM plugin_docman_item'.
            ' WHERE wiki_page = \'%s\''.
            ' AND group_id = %d'.
            ' AND delete_date IS NULL'.
            ' AND (obsolescence_date > %d OR obsolescence_date=0)'
            , db_es($wikipage), db_ei($group_id), $obsoleteToday
        );
        $res = $this->retrieve($sql);
        if($res && !$res->isError() && $res->rowCount() >= 1) {
            return true;
        }
        else {
            return false;
        }
    }

    /**
     * This returns ids of docman items that refrence a wiki page in a project. Returned item shouldn't be obsolete or deleted.
     *
     * @param string $wikipage wiki page name
     * @param int $group_id project id.
     *
     * @return array $ids ids of docman items that reference the wiki page.
     */
    function getItemIdByWikiPageAndGroupId($wikipage, $group_id) {
        $ids = array();
        $sql = sprintf('SELECT item_id'.
            ' FROM plugin_docman_item i'.
            ' WHERE i.wiki_page = \'%s\''.
            ' AND i.group_id = %d'.
            ' AND '. Docman_ItemDao::getCommonExcludeStmt('i')
            , db_es($wikipage), db_ei($group_id) 
        );
        $res = $this->retrieve($sql);
        if($res && !$res->isError()) {
	        if($res->rowCount() > 1) {
                $res->rewind();
                while($res->valid()) {
		            $row = $res->current();
                    $ids[] = $row['item_id'];
                    $res->next();
                }
                return $ids;
            }
            else {
                $res->rewind();
                if($res->valid()) {
                    $row = $res->current();
                    $id = $row['item_id'];
                    return $id;
                }
            }
        }
        else {
            return null;
        }
    }
}

?>
