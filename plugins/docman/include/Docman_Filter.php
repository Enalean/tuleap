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

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotPascalCase
class Docman_Filter
{
    public $value;
    public Docman_Metadata $md;
    private ?string $alternate_value = null;

    public function __construct(Docman_Metadata $md)
    {
        $this->value = \null;
        $this->md    = $md;
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

    public function _urlMatchDelete($request) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        if ($request->exist('del_filter') && $this->md->getLabel() == $request->get('del_filter')) {
            return \true;
        }
        return \false;
    }

    public function _urlValueIsValid($request) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        if ($request->exist($this->md->getLabel())) {
            return \true;
        }
        return \false;
    }

    public function _urlMatchUpdate($request) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        if ($this->_urlValueIsValid($request)) {
            $this->setValue($request->get($this->md->getLabel()));
            return \true;
        }
        return \false;
    }
    // Add new fields
    public function _urlMatchAdd($request) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
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

    public function getAlternateValue(): ?string
    {
        return $this->alternate_value;
    }

    public function setAlternateValue(?string $alternate_value): void
    {
        $this->alternate_value = $alternate_value;
    }
}
