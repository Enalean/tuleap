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

class Docman_MetadataValueDao extends DataAccessObject
{

    public function searchById($fieldId, $itemId)
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

    public function _matchSqlType($type, $value, &$field, &$dataType, &$escapedValue)
    {
        switch ($type) {
            case PLUGIN_DOCMAN_METADATA_TYPE_TEXT:
                $field        = 'valueText';
                $dataType     = '%s';
                $escapedValue = $this->da->quoteSmart($value);
                break;

            case PLUGIN_DOCMAN_METADATA_TYPE_STRING:
                $field        = 'valueString';
                $dataType     = '%s';
                $escapedValue = $this->da->quoteSmart($value);
                break;

            case PLUGIN_DOCMAN_METADATA_TYPE_DATE:
                $field        = 'valueDate';
                $dataType     = '%d';
                $escapedValue = $value;
                break;

            case PLUGIN_DOCMAN_METADATA_TYPE_LIST:
                $field        = 'valueInt';
                $dataType     = '%d';
                $escapedValue = $value;
                break;

            default:
                $field        = false;
                $dataType     = false;
                $escapedValue = false;
        }
    }

    // Special query to get values in rank order.
    public function searchListValuesById($fieldId, $itemId)
    {
        $sql = sprintf(
            'SELECT valueInt' .
                       ' FROM plugin_docman_metadata_value as mdv,' .
                       '      plugin_docman_metadata_love as love' .
                       ' WHERE mdv.field_id = %d' .
                       ' AND mdv.item_id = %d' .
                       ' AND love.value_id = mdv.valueInt' .
                       ' ORDER BY love.rank',
            $fieldId,
            $itemId
        );
        return $this->retrieve($sql);
    }

    public function create($itemId, $fieldId, $type, $value)
    {
        $fields = array('field_id', 'item_id');
        $types  = array('%d', '%d');

        $val     = null;
        $dtype   = null;
        $field   = null;
        $this->_matchSqlType($type, $value, $field, $dtype, $val);

        if ($field !== false) {
            $fields[] = $field;
            $types[]  = $dtype;

            $sql = sprintf(
                'INSERT INTO plugin_docman_metadata_value' .
                           ' (' . implode(',', $fields) . ')' .
                           ' VALUES (' . implode(',', $types) . ')',
                $fieldId,
                $itemId,
                $val
            );

            return $this->_createAndReturnId($sql);
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

    public function updateValue($itemId, $fieldId, $type, $value)
    {
        $val     = null;
        $dtype   = null;
        $field   = null;
        $this->_matchSqlType($type, $value, $field, $dtype, $val);

        if ($field !== false) {
            $sql = sprintf(
                'UPDATE plugin_docman_metadata_value' .
                           ' SET ' . $field . ' = ' . $dtype .
                           ' WHERE field_id = %d' .
                           ' AND item_id = %d',
                $val,
                $fieldId,
                $itemId
            );

            return $this->update($sql);
        } else {
            return false;
        }
    }

    public function exist($itemId, $fieldId)
    {
        $sql = sprintf(
            'SELECT count(*) AS nb' .
                       ' FROM plugin_docman_metadata_value' .
                       ' WHERE item_id = %d' .
                       ' AND field_id = %d',
            $itemId,
            $fieldId
        );
        return $this->retrieve($sql);
    }

    public function updateToListOfValueElementDefault($fieldId, $previousValue, $newValue)
    {
        $sql = sprintf(
            'UPDATE plugin_docman_metadata_value' .
                       ' SET valueInt = %d' .
                       ' WHERE field_id = %d' .
                       '  AND valueInt = %d',
            $newValue,
            $fieldId,
            $previousValue
        );
        return $this->update($sql);
    }

    /**
     * Create a record in plugin_docman_metadata_value for each item of
     * in the project where $fieldId is defined without value for $fieldId
     * metadata. The default value is 'None'.
     *
     * Add a condition to ensure this only affect
     * PLUGIN_DOCMAN_METADATA_TYPE_LIST metadata.
     */
    public function updateOrphansLoveItem($fieldId)
    {
        $sql = sprintf(
            'INSERT INTO plugin_docman_metadata_value' .
                       ' (field_id, item_id, valueInt)' .
                       ' SELECT md.field_id, i.item_id, ' . PLUGIN_DOCMAN_ITEM_STATUS_NONE .
                       ' FROM plugin_docman_item i' .
                       '  JOIN plugin_docman_metadata md' .
                       '   ON (i.group_id = md.group_id)' .
                       '  LEFT JOIN plugin_docman_metadata_value mdv' .
                       '   ON (mdv.item_id = i.item_id' .
                       '       AND mdv.field_id = md.field_id)' .
                       ' WHERE i.delete_date IS NULL' .
                       '  AND mdv.valueInt IS NULL' .
                       '  AND md.data_type = ' . PLUGIN_DOCMAN_METADATA_TYPE_LIST .
                       '  AND md.field_id = %d',
            $fieldId
        );
        return $this->update($sql);
    }

    /**
     * Delete the metadata values for metadata '$fieldId' and item
     * '$itemId'. $itemId is an integer or an array of integer (In order to
     * delete the metadata values of several item).
     *
     * $fieldId int The metadata id.
     * $itemId int or array of item_id.
     */
    public function delete($fieldId, $itemId)
    {
        if (!is_array($itemId)) {
            $itemId = array($itemId);
        }
        $sql = sprintf(
            'DELETE FROM plugin_docman_metadata_value' .
                       ' WHERE field_id = %d' .
                       ' AND item_id IN (%s)',
            $fieldId,
            implode(',', $itemId)
        );
        return $this->update($sql);
    }

    /**
     * Delete all usage of given $loveId.
     */
    public function deleteLove($loveId)
    {
        $sql = sprintf(
            'DELETE FROM plugin_docman_metadata_value' .
                       ' WHERE valueInt = %d',
            $loveId
        );
        return $this->update($sql);
    }

    /**
     * Copy a given metadata value to a list of item.
     */
    public function massUpdate($srcItemId, $fieldId, $type, $dstItemIdArray)
    {
        $value     = null;
        $val       = null;
        $dtype     = null;
        $fieldType = null;
        $this->_matchSqlType($type, $value, $fieldType, $dtype, $val);
        return $this->massUpdateArray($srcItemId, $fieldId, $fieldType, $dstItemIdArray);
    }

    /**
     * First delete all $fieldId values for $dstItemIdArray
     * Then for each value for ($srcItemId, $fieldId) create a new entry
     * for each item in $dstItemIdArray.
     */
    public function massUpdateArray($srcItemId, $fieldId, $fieldType, $dstItemIdArray)
    {
        $this->delete($fieldId, $dstItemIdArray);

        // Here is the trick:
        // - INSERT multiple rows (INSERT INTO ... SELECT)
        // - SELECT: Create a record set with
        //   - the current metadata ($fieldId, const).
        //   - each item listed in $dstItemIdArray.
        //   - the metadata value_s_ of ($srcItemId, $fieldId).
        $sql = sprintf(
            'INSERT INTO plugin_docman_metadata_value' .
                       ' (field_id,item_id,' . $fieldType . ')' .
                       ' SELECT mdv_src.field_id, item.item_id, mdv_src.' . $fieldType .
                       ' FROM plugin_docman_item item, plugin_docman_metadata_value mdv_src' .
                       ' WHERE mdv_src.item_id = %d' .
                       ' AND mdv_src.field_id = %d' .
                       ' AND item.item_id IN (%s)',
            $srcItemId,
            $fieldId,
            implode(',', $dstItemIdArray)
        );
        return $this->update($sql);
    }

    public function inheritMetadataFromParent(int $item_id, int $parent_id): void
    {
        $sql = sprintf(
            'INSERT INTO plugin_docman_metadata_value' .
                       ' (field_id, item_id, valueInt, valueText, valueDate, valueString)' .
                       ' SELECT field_id, %d, valueInt, valueText, valueDate, valueString ' .
                       ' FROM plugin_docman_metadata_value mdv_src' .
                       ' WHERE mdv_src.item_id = %d',
            $this->da->escapeInt($item_id),
            $this->da->escapeInt($parent_id)
        );
        $this->update($sql);
    }
}
