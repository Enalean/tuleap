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

require_once('Docman_MetadataFactory.class.php');

class Docman_MetadataSqlQueryChunk
{
    public $isRealMetadata;

    // SQL aliase for field name in metadata_value or item tables
    public $field;
    // SQL aliase for metadata_value table
    public $mdv;
    public $mdId;

    public function __construct($md)
    {
        $this->mdv = 'mdv_' . $md->getLabel();
        $this->mdId = $md->getId();

        $this->isRealMetadata = Docman_MetadataFactory::isRealMetadata($md->getLabel());

        if ($this->isRealMetadata) {
            switch ($md->getType()) {
                case PLUGIN_DOCMAN_METADATA_TYPE_TEXT:
                    $this->field = $this->mdv . '.valueText';
                    break;
                case PLUGIN_DOCMAN_METADATA_TYPE_STRING:
                    $this->field = $this->mdv . '.valueString';
                    break;
                case PLUGIN_DOCMAN_METADATA_TYPE_DATE:
                    $this->field = $this->mdv . '.valueDate';
                    break;
                case PLUGIN_DOCMAN_METADATA_TYPE_LIST:
                    $this->field = $this->mdv . '.valueInt';
                    break;
            }
        } else {
            switch ($md->getLabel()) {
                case 'owner':
                    $this->field = 'i.user_id';
                    break;
                default:
                    $this->field = 'i.' . $md->getLabel();
            }
        }
    }

    public function getFrom()
    {
        return '';
    }

    public function getWhere()
    {
        return '';
    }

    public function getOrderBy()
    {
        return '';
    }

    public function _getMdvJoin($label = null)
    {
        if ($label !== null) {
            $mdv = 'mdv_' . $label;
            $fieldId = substr($label, 6);
        } else {
            $mdv = $this->mdv;
            $fieldId = $this->mdId;
        }
        $stmt = 'plugin_docman_metadata_value AS ' . $mdv .
            ' ON (' . $mdv . '.item_id = i.item_id' .
            '  AND ' . $mdv . '.field_id = ' . $fieldId . ')';
        return $stmt;
    }
}
