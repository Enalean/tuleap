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

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotCamelCaps
class Docman_FilterList extends \Docman_Filter
{
    public function __construct($md)
    {
        $mdFactory = new \Docman_MetadataFactory($md->getGroupId());
        $mdFactory->appendMetadataValueList($md, \false);
        parent::__construct($md);
        $this->setValue(0);
    }
    public function initFromRow($row)
    {
        $this->setValue($row['value_love']);
    }
    /**
     * @todo: should valid an int
     */
    public function isValidListValue($val)
    {
        if (\is_numeric($val)) {
            return \true;
        }
        return \false;
    }
    public function _urlValueIsValid($request)
    {
        if (parent::_urlValueIsValid($request)) {
            if ($this->isValidListValue($request->get($this->md->getLabel()))) {
                return \true;
            }
        }
        return \false;
    }
    public function _urlMatchUpdate($request)
    {
        if (parent::_urlMatchUpdate($request)) {
            $v = $this->getValue();
            if (\is_array($v)) {
                // Convert advanced filter value to simple
                if (\count($v) == 1 && $this->isValidListValue($v[0])) {
                    $this->setValue($v[0]);
                } else {
                    $this->setValue(0);
                }
            }
            return \true;
        }
        return \false;
    }
}
