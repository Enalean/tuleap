<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

/**
 * Docman_MetadataValue is a container for User defined values of RealMedatada.
 */
class Docman_MetadataValue
{
    public $fieldId;
    public $itemId;

    public $type;

    public function __construct()
    {
        $this->fieldId = null;
        $this->itemId = null;

        $this->type = null;
    }

    public function setFieldId($v)
    {
        $this->fieldId = $v;
    }
    public function getFieldId()
    {
        return $this->fieldId;
    }

    public function setItemId($v)
    {
        $this->itemId = $v;
    }
    public function getItemId()
    {
        return $this->itemId;
    }

    public function setValue($v)
    {
        trigger_error(
            'Docman_MetadataValue::setValue is virtual but is not implemented.',
            E_USER_ERROR
        );
    }

    public function getValue()
    {
         trigger_error(
             'Docman_MetadataValue::getValue is virtual but is not implemented.',
             E_USER_ERROR
         );
    }

    public function setType($v)
    {
        $this->type = $v;
    }
    public function getType()
    {
        return $this->type;
    }

    public function initFromRow($row)
    {
        if (isset($row['field_id'])) {
            $this->fieldId = $row['field_id'];
        }
        if (isset($row['item_id'])) {
            $this->itemId = $row['item_id'];
        }
    }
}

/**
 * Docman_MetadataValueList contains selected values of a ListOfValues
 * metadata.
 *
 * Docman_MetadataValueList may have serveral values.
 */
class Docman_MetadataValueList extends Docman_MetadataValue
{
    public $listOfValues;

    public function __construct()
    {
        parent::__construct();
        $this->listOfValues = null;
    }

    public function setType($v)
    {
        return;
    }
    public function getType()
    {
        return PLUGIN_DOCMAN_METADATA_TYPE_LIST;
    }

    public function setValue($v)
    {
        $this->listOfValues = $v;
    }
    public function getValue()
    {
        return new ArrayIterator($this->listOfValues);
    }
}

/**
 * Docman_MetadataValueScalar contains scalar metadata.
 *
 * Scalar metadata are: Text, Date and String.
 * A scalar metadata can only have one value per metadata.
 */
class Docman_MetadataValueScalar extends Docman_MetadataValue
{
    public $valueText;
    public $valueDate;
    public $valueString;

    public function __construct()
    {
        parent::__construct();
        $this->valueText = null;
        $this->valueFloat = null;
        $this->valueDate = null;
        $this->valueString = null;
    }

    public function setValueText($v)
    {
        $this->valueText = $v;
    }
    public function getValueText()
    {
        return $this->valueText;
    }

    public function setValueDate($v)
    {
        $this->valueDate = $v;
    }
    public function getValueDate()
    {
        return $this->valueDate;
    }

    public function setValueString($v)
    {
        $this->valueString = $v;
    }
    public function getValueString()
    {
        return $this->valueString;
    }

    public function getValue()
    {
        switch ($this->type) {
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
    public function setValue($v)
    {
        switch ($this->type) {
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

    public function initFromRow($row)
    {
        parent::initFromRow($row);

        if (isset($row['valueText'])) {
            $this->valueText = $row['valueText'];
            $this->type = PLUGIN_DOCMAN_METADATA_TYPE_TEXT;
        }
        if (isset($row['valueDate'])) {
            $this->valueDate = $row['valueDate'];
            $this->type = PLUGIN_DOCMAN_METADATA_TYPE_DATE;
        }
        if (isset($row['valueString'])) {
            $this->valueString = $row['valueString'];
            $this->type = PLUGIN_DOCMAN_METADATA_TYPE_STRING;
        }
    }
}
