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
class Docman_FilterListAdvanced extends \Docman_FilterList
{
    public function __construct($md)
    {
        parent::__construct($md);
        $this->setValue([]);
    }
    public function _urlValueIsValid($request)
    {
        if ($request->exist($this->md->getLabel())) {
            $val = $request->get($this->md->getLabel());
            if (\is_array($val)) {
                $allInt = \true;
                foreach ($val as $v) {
                    $allInt = $allInt && $this->isValidListValue($v);
                }
                return $allInt;
            } else {
                if ($this->isValidListValue($val)) {
                    return \true;
                }
            }
        }
        return \false;
    }
    public function _urlMatchUpdate($request)
    {
        if (\Docman_Filter::_urlMatchUpdate($request)) {
            if (! \is_array($this->getValue())) {
                if ($this->getValue() !== \null && $this->getValue() != '') {
                    // Convert simple value to advanced
                    $this->setValue([$this->getValue()]);
                } else {
                    $this->setValue([]);
                }
            } elseif (\count($this->getValue()) == 1) {
                // If empty value, clean-up
                $v = $this->getValue();
                if ($v[0] == '') {
                    $this->setValue([0]);
                }
            }
            return \true;
        }
        return \false;
    }
    public function _urlMatchAdd($request)
    {
        if (parent::_urlMatchAdd($request)) {
            $this->setValue([0]);
            return \true;
        }
        return \false;
    }
    public function addValue($val)
    {
        $this->value[] = $val;
    }
    public function initFromRow($row)
    {
        $this->addValue($row['value_love']);
    }
}
