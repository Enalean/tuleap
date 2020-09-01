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
 * Docman_MetadataValueList contains selected values of a ListOfValues
 * metadata.
 *
 * Docman_MetadataValueList may have serveral values.
 */
// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotCamelCaps
class Docman_MetadataValueList extends \Docman_MetadataValue
{
    public $listOfValues;
    public function __construct()
    {
        parent::__construct();
        $this->listOfValues = \null;
    }
    public function setType($v)
    {
        return;
    }
    public function getType()
    {
        return \PLUGIN_DOCMAN_METADATA_TYPE_LIST;
    }
    public function setValue($v)
    {
        $this->listOfValues = $v;
    }
    public function getValue()
    {
        return new \ArrayIterator($this->listOfValues);
    }
}
