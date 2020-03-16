<?php
/**
 * Copyright (c) Enalean, 2019 - present. All Rights Reserved.
 * Copyright (c) STMicroelectronics, 2006. All Rights Reserved.
 *
 * Originally written by Manuel Vacelet, 2006
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

class Docman_MetadataListOfValuesElementDao extends DataAccessObject
{

    public function serachByValueId($id)
    {
        $sql = sprintf(
            'SELECT *' .
                       ' FROM plugin_docman_metadata_love AS love' .
                       ' WHERE love.value_id = %d',
            $id
        );
        return $this->retrieve($sql);
    }

    public function searchByFieldId($id, $onlyActive)
    {
        $where_clause = '';
        if ($onlyActive === true) {
            $where_clause .= ' AND love.status IN ("A", "P")';
        }

        $sql = sprintf(
            'SELECT *' .
                       ' FROM plugin_docman_metadata_love AS love,' .
                       '  plugin_docman_metadata_love_md AS lovemd' .
                       ' WHERE lovemd.field_id = %d' .
                       ' AND love.value_id = lovemd.value_id' .
                       $where_clause .
                       ' ORDER BY love.rank',
            $id
        );

        return $this->retrieve($sql);
    }

    public function searchByName($metadataId, $name, $onlyActive)
    {
        $where_clause = '';
        if ($onlyActive === true) {
            $where_clause .= ' AND love.status IN ("A", "P")';
        }
        $sql = sprintf(
            'SELECT love.*' .
                       ' FROM plugin_docman_metadata_love AS love,' .
                       '  plugin_docman_metadata_love_md AS lovemd' .
                       ' WHERE love.name = %s' .
                       $where_clause .
                       '  AND lovemd.value_id = love.value_id' .
                       '  AND lovemd.field_id = %d',
            $this->da->quoteSmart($name),
            $metadataId
        );
        return $this->retrieve($sql);
    }

    // Special query to get values in rank order.
    public function searchListValuesById($fieldId, $itemId)
    {
        $sql = sprintf(
            'SELECT love.*' .
                       ' FROM plugin_docman_metadata_value as mdv' .
                       '  INNER JOIN plugin_docman_metadata_love AS love ' .
                       '   ON (love.value_id = mdv.valueInt)' .
                       ' WHERE mdv.field_id = %d' .
                       ' AND mdv.item_id = %d' .
                       ' AND love.status IN ("A", "P")' .
                       ' ORDER BY love.rank',
            $fieldId,
            $itemId
        );
        return $this->retrieve($sql);
    }

    protected function prepareLoveRanking($metadataId, $rank)
    {
        // Build the list of values needed in following queries.
        $sql = sprintf(
            'SELECT value_id ' .
                        ' FROM plugin_docman_metadata_love_md AS lovemd' .
                        ' WHERE lovemd.field_id = %d' .
                        ' AND lovemd.value_id != 100',
            $metadataId
        );
        $dar = $this->retrieve($sql);
        $valId = array();
        $dar->rewind();
        while ($dar->valid()) {
            $row = $dar->current();
            $valId[] = $row['value_id'];
            $dar->next();
        }

        if (count($valId) <= 0) {
            $rank = 1;
        } else {
            $valIdList = implode(',', $valId);

            switch ($rank) {
                case 'end':
                    //print 'Put a the end<br>';
                    $sql = sprintf('SELECT MAX(rank)+1 AS rank' .
                               ' FROM plugin_docman_metadata_love AS love' .
                               ' WHERE value_id IN (' . $valIdList . ')');
                    $dar = $this->retrieve($sql);
                    if ($dar && $dar->valid()) {
                        $row = $dar->current();
                        $rank = $row['rank'];
                    }
                    //print '  with rank: '.$rank.'<br>';
                    break;
                case 'beg':
                    //print 'Put a the beginning<br>';
                    $sql = sprintf('SELECT MIN(rank) AS rank' .
                               ' FROM plugin_docman_metadata_love AS love' .
                               ' WHERE value_id IN (' . $valIdList . ')');
                    $dar = $this->retrieve($sql);
                    if ($dar && $dar->valid()) {
                        $row = $dar->current();
                        $rank = $row['rank'];
                    }
                    //print '  with rank: '.$rank.'<br>';
                    // no break
                default:
                    $sql = sprintf(
                        'UPDATE plugin_docman_metadata_love AS love' .
                               ' SET rank = rank + 1' .
                               ' WHERE rank >= %d' .
                               ' AND value_id IN (' . $valIdList . ')',
                        $rank
                    );
                    //print $sql."<br>";
                    $updated = $this->update($sql);
                    if ($updated == false) {
                        $rank = false;
                    }
            }
        }
        return $rank;
    }

    public function createElement($name, $description, $rank, $status)
    {
        $sql = sprintf(
            'INSERT INTO plugin_docman_metadata_love(' .
                       'name, description, rank, status' .
                       ') VALUES (' .
                       '%s, %s, %d, %s' .
                       ')',
            $this->da->quoteSmart($name),
            $this->da->quoteSmart($description),
            $rank,
            $this->da->quoteSmart($status)
        );
        return $this->_createAndReturnId($sql);
    }

    public function createMetadataElementBond($metadataId, $elementId)
    {
        $sql = sprintf(
            'INSERT INTO plugin_docman_metadata_love_md(' .
                       'field_id, value_id' .
                       ') VALUES (' .
                       '%d, %d' .
                       ')',
            $metadataId,
            $elementId
        );
        return $this->_createAndReturnId($sql);
    }

    public function create($metadataId, $name, $description, $rank, $status)
    {
        $rank = $this->prepareLoveRanking($metadataId, $rank);
        if ($rank !== false) {
            $elementId = $this->createElement($name, $description, $rank, $status);
            if ($elementId !== false) {
                $inserted = $this->createMetadataElementBond($metadataId, $elementId);
                return $inserted;
            } else {
                return false;
            }
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

    public function updateElement($metadataId, $valueId, $name, $description, $rank, $status)
    {
        $updated = false;

        $rankStmt = false;
        if ($rank != '--') {
            $r = $this->prepareLoveRanking($metadataId, $rank);
            if ($r !== false) {
                $rankStmt = '  , love.rank = ' . $r;
            }
        } else {
            $rankStmt = '';
        }

        if ($rankStmt !== false) {
            $sql = sprintf(
                'UPDATE plugin_docman_metadata_love AS love' .
                           ' SET love.name = %s' .
                           '  , love.description = %s' .
                           $rankStmt .
                           '  , love.status = %s' .
                           ' WHERE love.value_id = %d',
                $this->da->quoteSmart($name),
                $this->da->quoteSmart($description),
                $this->da->quoteSmart($status),
                $valueId
            );
            $updated = $this->update($sql);
        }

        return $updated;
    }

    public function updateFromRow($row)
    {
        $updated = false;
        $id = false;
        if (!isset($row['value_id'])) {
            return false;
        }
        $id = (int) $row['value_id'];
        if ($id) {
            $dar = $this->serachByValueId($id);
            if (!$dar->isError() && $dar->valid()) {
                $current = $dar->current();
                $set_array = array();
                foreach ($row as $key => $value) {
                    if ($key != 'value_id' && isset($current[$key]) && $value != $current[$key]) {
                        $set_array[] = $key . ' = ' . $this->da->quoteSmart($value);
                    }
                }
                if (count($set_array)) {
                    $sql = 'UPDATE plugin_docman_metadata_love'
                        . ' SET ' . implode(' , ', $set_array)
                        . ' WHERE value_id=' . $id;
                    $updated = $this->update($sql);
                }
            }
        }
        return $updated;
    }

    public function delete($id)
    {
        $row = array('value_id' => $id,
                     'status'   => 'D');
        return $this->updateFromRow($row);
    }

    public function deleteByMetadataId($id)
    {
        $sql = sprintf(
            'UPDATE plugin_docman_metadata_love AS love' .
                       ' SET status = \'D\'' .
                       ' WHERE value_id IN (' .
                       '  SELECT value_id' .
                       '   FROM plugin_docman_metadata_love_md AS lovemd' .
                       '   WHERE lovemd.field_id = %d' .
                       '     AND lovemd.value_id > 100' .
                       '  )',
            $id
        );
        return $this->update($sql);
    }
}
