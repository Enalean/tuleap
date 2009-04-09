<?php
/*
 * Copyright (c) STMicroelectronics, 2007. All Rights Reserved.
 *
 * Originally written by Manuel Vacelet, 2007
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

require_once('common/dao/include/DataAccessObject.class.php');
require_once('Docman_ItemDao.class.php');

class Docman_ReportDao
extends DataAccessObject {

    function Docman_ReportDao(&$da) {
        DataAccessObject::DataAccessObject($da);
    }

    function searchById($id) {
        $sql = sprintf('SELECT *'.
                       ' FROM plugin_docman_report'.
                       ' WHERE report_id = %d',
                       $id);
        return $this->retrieve($sql);
    }

    function searchProjectReportByGroupId($id) {
        $sql = sprintf('SELECT *'.
                       ' FROM plugin_docman_report'.
                       ' WHERE group_id = %d'.
                       ' AND scope = "P"'.
                       ' ORDER BY name',
                       $id);
        return $this->retrieve($sql);
    }

    function searchPersonalReportByUserId($groupId, $userId) {
        $sql = sprintf('SELECT *'.
                       ' FROM plugin_docman_report'.
                       ' WHERE group_id = %d'.
                       ' AND user_id = %d'.
                       ' AND scope = "I"'.
                       ' ORDER BY name',
                       $groupId,
                       $userId);
        return $this->retrieve($sql);
    }

    function searchItemsInReports($groupId, $reportId=null) {
        $sql = 'SELECT i.*'.
            ' FROM plugin_docman_report r'.
            ' JOIN plugin_docman_item i ON (i.item_id = r.item_id)'.
            ' WHERE r.group_id = '.$this->da->escapeInt($groupId).
            ' AND r.item_id != 0 '.
            ' AND r.item_id IS NOT NULL '.
            ' AND '.Docman_ItemDao::getCommonExcludeStmt('i');
        if($reportId !== null) {
            $sql .= ' AND r.report_id = '.$this->da->escapeInt($reportId);
        }
        return $this->retrieve($sql);
    }


    function create($name, $title, $groupId, $userId, $itemId, $scope, $isDefault, $advancedSearch, $description, $image) {
        $sql = sprintf('INSERT INTO plugin_docman_report'.
                       ' (name, title, group_id, user_id, item_id, scope, is_default, advanced_search, description, image)'.
                       ' VALUES '.
                       ' (%s, %s, %d, %d, %d, %s, %d, %d, %s, %d)',
                       $this->da->quoteSmart($name),
                       ($title === null ? 'NULL' : $this->da->quoteSmart($title)),
                       $groupId,
                       $userId,
                       $itemId,
                       $this->da->quoteSmart($scope),
                       $isDefault,
                       $advancedSearch,
                       $this->da->quoteSmart($description),
                       $image);
        return $this->createAndReturnId($sql);
    }

    function addFieldToReport($reportId, $mdLabel, $type, $value) {
        $sql = sprintf('INSERT INTO plugin_docman_report_metadata'.
                       '(report_id, label)'.
                       ' VALUES'.
                       ' (%d, %s)',
                       $reportId, $this->da->quoteSmart($mdLabel));
        $this->update($sql);
    }

    function createAndReturnId($sql) {
        $inserted = $this->update($sql);
        if ($inserted) {
            $dar = $this->retrieve("SELECT LAST_INSERT_ID() AS id");
            if ($row = $dar->getRow()) {
                return $row['id'];
            } else {
                $inserted = $dar->isError();
            }
        }
        return $inserted;
    }

    function updateReport($id, $name, $title, $itemId, $advancedSearch, $scope, $description, $image) {
        $sql = sprintf('UPDATE plugin_docman_report'.
                       ' SET advanced_search = %d,'.
                       ' name = %s,'.
                       ' title = %s,'.
                       ' item_id = %d,'.
                       ' scope = %s,'.
                       ' description = %s,'.
                       ' image = %d'.
                       ' WHERE report_id = %d',
                       $advancedSearch,
                       $this->da->quoteSmart($name),
                       $this->da->quoteSmart($title),
                       $itemId,
                       $this->da->quoteSmart($scope),
                       $this->da->quoteSmart($description),
                       $image,
                       $id);
        return $this->update($sql);
    }

    function deleteById($id) {
        $sql = sprintf('DELETE FROM plugin_docman_report'.
                       ' WHERE report_id = %d',
                       $id);
        return $this->update($sql);
    }
}

?>
