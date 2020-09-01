<?php
/**
 * Copyright (c) Enalean, 2018-Present. All Rights Reserved.
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
 * For metadata that aims to provide a list of values to use we add two special
 * methods that store and restore all the values provided by the metadata
 * (ie. the select box).
 *
 * Actually, Docman_ListMetadata objects are quite complex because they provide
 * - a list of values the user can select (this is the purpose of the two
 *   function bellow)
 * - a list of values the user selected, accessible by regular setValue() and
 *   getValue().
 */
// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotCamelCaps
class Docman_ListMetadata extends \Docman_Metadata
{
    public $listOfValue;
    public function __construct()
    {
        parent::__construct();
        $this->defaultValue = [];
    }
    /**
     * @param array of Docman_MetadataListOfValuesElements
     */
    public function setListOfValueElements(&$l)
    {
        $this->listOfValue = $l;
    }
    /**
     * @return iterator of Docman_MetadataListOfValuesElements
     */
    public function &getListOfValueIterator()
    {
        $i = new \ArrayIterator($this->listOfValue);
        return $i;
    }
    public function setDefaultValue($v)
    {
        if (\is_a($v, 'Iterator')) {
            $v->rewind();
            //if(is_a($love, 'Docman_MetadataListOfValuesElement')) {
            while ($v->valid()) {
                $love = $v->current();
                $this->defaultValue[] = $love->getId();
                $v->next();
            }
        } else {
            $this->defaultValue[] = $v;
        }
    }
}
