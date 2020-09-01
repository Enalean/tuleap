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
class Docman_FilterDateAdvanced extends \Docman_FilterDate
{
    public $fieldNameStart;
    public $fieldNameEnd;
    public $valueStart;
    public $valueEnd;
    public function __construct($md)
    {
        parent::__construct($md);
        $base = $md->getLabel() . '_value';
        $this->fieldNameStart = $base . '_start';
        $this->fieldNameEnd = $base . '_end';
        $this->valueStart = '';
        $this->valueEnd = '';
    }
    public function setValueStart($v)
    {
        $this->valueStart = $v;
    }
    public function getValueStart()
    {
        return $this->valueStart;
    }
    public function setValueEnd($v)
    {
        $this->valueEnd = $v;
    }
    public function getValueEnd()
    {
        return $this->valueEnd;
    }
    public function initFromRow($row)
    {
        $this->setValueStart($row['value_date1']);
        $this->setValueEnd($row['value_date2']);
    }
    public function getFieldStartValueName()
    {
        return $this->fieldNameStart;
    }
    public function getFieldEndValueName()
    {
        return $this->fieldNameEnd;
    }
    public function getUrlParameters()
    {
        $param = [];
        $param[$this->fieldNameStart] = $this->valueStart;
        $param[$this->fieldNameEnd] = $this->valueEnd;
        return $param;
    }
    public function _urlMatchUpdate($request)
    {
        $fieldExist = \false;
        $startValue = \false;
        if ($request->exist($this->fieldNameStart)) {
            $fieldExist = \true;
            if ($this->isValidDateFormat($request->get($this->fieldNameStart))) {
                $this->setValueStart($request->get($this->fieldNameStart));
                $startValue = \true;
            }
        }
        $endValue = \false;
        if ($request->exist($this->fieldNameEnd)) {
            $fieldExist = \true;
            if ($this->isValidDateFormat($request->get($this->fieldNameEnd))) {
                $this->setValueEnd($request->get($this->fieldNameEnd));
                $endValue = \true;
            }
        }
        // If no values found, try to get values from simple search
        if (! $startValue && ! $endValue) {
            if ($request->exist($this->getFieldOperatorName()) && $request->exist($this->getFieldValueName())) {
                switch ($request->get($this->getFieldOperatorName())) {
                    case '-1':
                        // '<'
                        $this->setValueEnd($request->get($this->getFieldValueName()));
                        break;
                    case '0':
                        // '='
                        $this->setValueEnd($request->get($this->getFieldValueName()));
                        $this->setValueStart($request->get($this->getFieldValueName()));
                        break;
                    case '1':
                    // '>'
                    default:
                        $this->setValueStart($request->get($this->getFieldValueName()));
                }
                $fieldExist = \true;
            }
        }
        return $fieldExist;
    }
}
