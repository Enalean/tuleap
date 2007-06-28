<?php
/**
 * Copyright � STMicroelectronics, 2006. All Rights Reserved.
 * 
 * Originally written by Manuel VACELET, 2006.
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with CodeX; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 * 
 * 
 */

/**
 * Docman_MetadataValue is a container for User defined values of RealMedatada.
 */
class Docman_MetadataValue {
    var $fieldId;
    var $itemId;

    var $type;

    function Docman_MetadataValue() {
        $this->fieldId = null;
        $this->itemId = null;

        $this->type = null;
    }

    function setFieldId($v) {
        $this->fieldId = $v;
    }
    function getFieldId() {
        return $this->fieldId;
    }

    function setItemId($v) {
        $this->itemId = $v;
    }
    function getItemId() {
        return $this->itemId;
    }

    function setValue($v) {
        trigger_error('Docman_MetadataValue::setValue is virtual but is not implemented.',
                      E_USER_ERROR);
    }

    function getValue() {
         trigger_error('Docman_MetadataValue::getValue is virtual but is not implemented.',
                      E_USER_ERROR);
    }

    function setType($v) {
        $this->type = $v;
    }
    function getType() {
        return $this->type;
    }

    function initFromRow() {
        if(isset($row['field_id'])) $this->fieldId = $row['field_id'];
        if(isset($row['item_id'])) $this->itemId = $row['item_id'];
    }
}

/**
 * Docman_MetadataValueList contains selected values of a ListOfValues
 * metadata.
 *
 * Docman_MetadataValueList may have serveral values.
 */
class Docman_MetadataValueList extends Docman_MetadataValue {
    var $listOfValues;

    function Docman_MetadataValueList() {
        parent::Docman_MetadataValue();        
        $this->listOfValues = null;
    }

    function setType($v) {
        return;
    }
    function getType() {
        return PLUGIN_DOCMAN_METADATA_TYPE_LIST;
    }

    function setValue(&$v) {
        $this->listOfValues =& $v;
    }
    function &getValue() {
        $i = new ArrayIterator($this->listOfValues);
        return $i;
    }

}

/**
 * Docman_MetadataValueScalar contains scalar metadata.
 *
 * Scalar metadata are: Text, Date and String.
 * A scalar metadata can only have one value per metadata.
 */
class Docman_MetadataValueScalar extends Docman_MetadataValue {
    var $valueText;
    var $valueDate;
    var $valueString;

    function Docman_MetadataValueScalar() {
        parent::Docman_MetadataValue();        
        $this->valueText = null;
        $this->valueFloat = null;
        $this->valueDate = null;
        $this->valueString = null;        
    }
    
    function setValueText($v) {
        $this->valueText = $v;
    }
    function getValueText() {
        return $this->valueText;
    }

    function setValueDate($v) {
        $this->valueDate = $v;
    }
    function getValueDate() {
        return $this->valueDate;
    }

    function setValueString($v) {
        $this->valueString = $v;
    }
    function getValueString() {
        return $this->valueString;
    }    
    
    function getValue() {
        switch($this->type) {
        case PLUGIN_DOCMAN_METADATA_TYPE_TEXT:
            return $this->valueText;
            break;
        case PLUGIN_DOCMAN_METADATA_TYPE_STRING:
            return $this->valueString;
            break;
        case PLUGIN_DOCMAN_METADATA_TYPE_DATE:
            return $this->valueDate;
            break;
        default:
            return null;
        }
    }
    function setValue($v) {
        switch($this->type) {
        case PLUGIN_DOCMAN_METADATA_TYPE_TEXT:
            $this->valueText = $v;
            break;
        case PLUGIN_DOCMAN_METADATA_TYPE_STRING:
            $this->valueString = $v;
            break;
        case PLUGIN_DOCMAN_METADATA_TYPE_DATE:
            $this->valueDate = $v;
            break;
        default:
            return null;
        }
    }

    function initFromRow($row) {
        parent::initFromRow($row);

        if(isset($row['valueText'])) {
            $this->valueText = $row['valueText'];
            $this->type = PLUGIN_DOCMAN_METADATA_TYPE_TEXT;
        }
        if(isset($row['valueDate'])) {
            $this->valueDate = $row['valueDate'];
            $this->type = PLUGIN_DOCMAN_METADATA_TYPE_DATE;
        }
        if(isset($row['valueString'])) {
            $this->valueString = $row['valueString'];
            $this->type = PLUGIN_DOCMAN_METADATA_TYPE_STRING;
        }
    }
}

?>
