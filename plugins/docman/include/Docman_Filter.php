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
class Docman_Filter
{
    public $value;
    public $md;
    public function __construct($md)
    {
        $this->value = \null;
        $this->md = $md;
    }
    public function setValue($v)
    {
        $this->value = $v;
    }
    public function getValue()
    {
        return $this->value;
    }
    public function initFromRow($row)
    {
    }
    public function getUrlParameters()
    {
        $param = [];
        //if($this->value !== null) {
        $param[$this->md->getLabel()] = $this->value;
        //}
        return $param;
    }
    public function _urlMatchDelete($request)
    {
        if ($request->exist('del_filter') && $this->md->getLabel() == $request->get('del_filter')) {
            return \true;
        }
        return \false;
    }
    public function _urlValueIsValid($request)
    {
        if ($request->exist($this->md->getLabel())) {
            return \true;
        }
        return \false;
    }
    public function _urlMatchUpdate($request)
    {
        if ($this->_urlValueIsValid($request)) {
            $this->setValue($request->get($this->md->getLabel()));
            return \true;
        }
        return \false;
    }
    // Add new fields
    public function _urlMatchAdd($request)
    {
        if ($request->exist('add_filter') && $this->md->getLabel() == $request->get('add_filter')) {
            return \true;
        }
        return \false;
    }
    public function initOnUrlMatch($request)
    {
        if ($this->md !== \null) {
            if (! $this->_urlMatchDelete($request)) {
                if ($this->_urlMatchUpdate($request)) {
                    return \true;
                } else {
                    return $this->_urlMatchAdd($request);
                }
            }
        }
        return \false;
    }
}
