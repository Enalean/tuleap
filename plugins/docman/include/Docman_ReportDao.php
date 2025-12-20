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

class Docman_ReportDao extends DataAccessObject //phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotPascalCase
{
    public function searchById($id)
    {
        $sql = sprintf(
            'SELECT *' .
                       ' FROM plugin_docman_report' .
                       ' WHERE report_id = %d',
            $id
        );
        return $this->retrieve($sql);
    }

    public function create($name, $title, $groupId, $userId, $itemId, $scope, $isDefault, $advancedSearch, $description, $image)
    {
        $sql = sprintf(
            'INSERT INTO plugin_docman_report' .
                       ' (name, title, group_id, user_id, item_id, scope, is_default, advanced_search, description, image)' .
                       ' VALUES ' .
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
            $image
        );
        return $this->createAndReturnId($sql);
    }

    public function createAndReturnId($sql)
    {
        $inserted = $this->update($sql);
        if ($inserted) {
            $dar = $this->retrieve('SELECT LAST_INSERT_ID() AS id');
            if ($row = $dar->getRow()) {
                return $row['id'];
            } else {
                $inserted = $dar->isError();
            }
        }
        return $inserted;
    }

    public function deleteById($id)
    {
        $sql = sprintf(
            'DELETE FROM plugin_docman_report' .
                       ' WHERE report_id = %d',
            $id
        );
        return $this->update($sql);
    }
}
