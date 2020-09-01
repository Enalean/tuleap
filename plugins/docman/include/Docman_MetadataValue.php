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
// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotCamelCaps
class Docman_MetadataValue
{
    public $fieldId;
    public $itemId;
    public $type;
    public function __construct()
    {
        $this->fieldId = \null;
        $this->itemId = \null;
        $this->type = \null;
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
        \trigger_error('Docman_MetadataValue::setValue is virtual but is not implemented.', \E_USER_ERROR);
    }
    public function getValue()
    {
        \trigger_error('Docman_MetadataValue::getValue is virtual but is not implemented.', \E_USER_ERROR);
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
