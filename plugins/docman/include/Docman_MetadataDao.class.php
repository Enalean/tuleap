<?php
/*
 * Copyright (c) STMicroelectronics, 2006. All Rights Reserved.
 *
 * Originally written by Manuel Vacelet, 2006
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

class Docman_MetadataDao extends DataAccessObject
{
    public $deletedStmt;
    public $notDeletedStmt;

    public function __construct($da)
    {
        parent::__construct($da);

        $this->deletedStmt    = 'special = 100';
        $this->notDeletedStmt = 'special != 100';
    }

    public function searchById($id)
    {
        $sql = sprintf(
            'SELECT *'
                       . ' FROM plugin_docman_metadata'
                       . ' WHERE field_id = %d'
                       . ' AND ' . $this->notDeletedStmt,
            $id
        );
        return $this->retrieve($sql);
    }

    public function searchByGroupId($id, $onlyUsed, $type = array())
    {
        $where_clause = '';
        if ($onlyUsed) {
            $where_clause .= ' AND use_it = ' . PLUGIN_DOCMAN_METADATA_USED;
        }

        if (is_array($type) && count($type) > 0) {
            $where_clause .= ' AND data_type IN (' . implode(',', $type) . ')';
        }

        $sql = sprintf(
            'SELECT *'
                       . ' FROM plugin_docman_metadata'
                       . ' WHERE group_id = %d'
                       . $where_clause
                       . ' AND ' . $this->notDeletedStmt
                       . ' ORDER BY label ASC',
            $id
        );

        return $this->retrieve($sql);
    }

    // Very limited implementation of update
    // right now, only 'use_it' field is concerned by update
    public function updateById($id, $name, $description, $emptyAllowed, $mulValuesAllowed, $useIt)
    {
        $row = array('field_id' => $id,
                     'name' => $name,
                     'description' => $description,
                     'empty_ok' => $emptyAllowed,
                     'mul_val_ok' => $mulValuesAllowed,
                     'use_it' => $useIt);
        return $this->updateFromRow($row);
    }

    public function updateFromRow($row)
    {
        $updated = false;
        $id = false;
        if (!isset($row['field_id'])) {
            return false;
        }
        $id = (int) $row['field_id'];
        if ($id) {
            $dar = $this->searchById($id);
            if (!$dar->isError() && $dar->valid()) {
                $current = $dar->current();
                $set_array = array();
                foreach ($row as $key => $value) {
                    if ($key != 'field_id' && $value != $current[$key]) {
                        $set_array[] = $key . ' = ' . $this->da->quoteSmart($value);
                    }
                }
                if (count($set_array)) {
                    $sql = 'UPDATE plugin_docman_metadata'
                        . ' SET ' . implode(' , ', $set_array)
                        . ' WHERE field_id=' . $id;
                    $updated = $this->update($sql);
                }
            }
        }
        return $updated;
    }

    public function create(
        $groupId,
        $name,
        $type,
        $description,
        $isRequired,
        $isEmptyAllowed,
        $mulValuesAllowed,
        $special,
        $useIt
    ) {
        $sql = sprintf(
            'INSERT INTO plugin_docman_metadata(' .
                       'group_id, name, data_type, description,' .
                       'required, empty_ok, mul_val_ok, special,' .
                       'use_it' .
                       ') VALUES (' .
                       '%d, %s, %d, %s,' .
                       '%d, %d, %d, %d,' .
                       '%d' .
                       ')',
            $groupId,
            $this->da->quoteSmart($name),
            $type,
            $this->da->quoteSmart($description),
            $isRequired,
            $isEmptyAllowed,
            $mulValuesAllowed,
            $special,
            $useIt
        );

        $mdId = $this->_createAndReturnId($sql);
        if ($mdId !== false) {
            //update label
            $row = array('field_id' => $mdId,
                         'label'    => 'field_' . $mdId);
            $updated = $this->updateFromRow($row);
            if (!$updated) {
                return false;
            } else {
                return $mdId;
            }
        } else {
            return false;
        }
    }

    public function _createAndReturnId($sql)
    {
        $inserted = $this->update($sql);
        if ($inserted) {
            $dar = $this->retrieve("SELECT LAST_INSERT_ID() AS id");
            if ($row = $dar->getRow()) {
                $inserted = $row['id'];
            } else {
                $inserted = $dar->isError();
            }
        }
        return $inserted;
    }

    public function delete($id)
    {
        $sql = sprintf(
            'UPDATE plugin_docman_metadata' .
                       ' SET special = 100' .
                       ' WHERE field_id = %d',
            $id
        );
        return $this->update($sql);
    }

    public function searchByName($groupId, $name)
    {
        $sql = sprintf(
            'SELECT *' .
                       ' FROM plugin_docman_metadata' .
                       ' WHERE group_id = %d' .
                       '  AND TRIM(name) = %s' .
                       '  AND ' . $this->notDeletedStmt,
            $groupId,
            $this->da->quoteSmart($name)
        );
        return $this->retrieve($sql);
    }

    public function searchValueById($fieldId, $itemId)
    {
        $sql = sprintf(
            'SELECT *' .
                       ' FROM plugin_docman_metadata_value' .
                       ' WHERE field_id = %d' .
                       ' AND item_id = %d',
            $fieldId,
            $itemId
        );
        return $this->retrieve($sql);
    }
}
