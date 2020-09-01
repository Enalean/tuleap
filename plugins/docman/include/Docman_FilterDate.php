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
class Docman_FilterDate extends \Docman_Filter
{
    public $operator;
    public $field_operator_name;
    public $field_value_name;
    public function __construct($md)
    {
        parent::__construct($md);
        $this->operator = \null;
        if ($md !== \null) {
            $this->field_operator_name = $md->getLabel() . '_operator';
            $this->field_value_name = $md->getLabel() . '_value';
        }
    }
    public function initFromRow($row)
    {
        $this->setOperator($row['value_date_op']);
        $this->setValue($row['value_date1']);
    }
    public function getFieldOperatorName()
    {
        return $this->field_operator_name;
    }
    public function getFieldValueName()
    {
        return $this->field_value_name;
    }
    public function setOperator($v)
    {
        $this->operator = $v;
    }
    public function getOperator()
    {
        return $this->operator;
    }
    public function isValidDateFormat($value)
    {
        if (\preg_match('/^([0-9]{4})-([0-9]{1,2})-([0-9]{1,2})$/', $value, $d)) {
            return \true;
        }
        return \false;
    }
    public function isValidOperator($op)
    {
        if ($op == 0 || $op == -1 || $op == 1) {
            return \true;
        }
        return \false;
    }
    public function getUrlParameters()
    {
        $param = [];
        //if($this->value !== null) {
        $param[$this->field_value_name] = $this->value;
        //if($this->operator !== null) {
        $param[$this->field_operator_name] = $this->operator;
        //}
        //}
        return $param;
    }
    public function _urlMatchUpdate($request)
    {
        // Simple date
        if ($request->exist($this->getFieldValueName())) {
            $val = $request->get($this->getFieldValueName());
            if ($this->isValidDateFormat($val)) {
                $this->setValue($val);
            }
            $op = $request->get($this->getFieldOperatorName());
            if ($this->isValidOperator($op)) {
                $this->setOperator($op);
            }
            return \true;
        }
        // If no values found, try to get fields from advanced search
        $advSearch = new \Docman_FilterDateAdvanced($this->md);
        if ($request->exist($advSearch->getFieldStartValueName()) && $this->isValidDateFormat($request->get($advSearch->getFieldStartValueName()))) {
            $startValue = $request->get($advSearch->getFieldStartValueName());
            $endValue = '';
            if ($request->exist($advSearch->getFieldEndValueName()) && $this->isValidDateFormat($request->get($advSearch->getFieldEndValueName()))) {
                $endValue = $request->get($advSearch->getFieldEndValueName());
            }
            if ($startValue != '') {
                if ($endValue == $startValue) {
                    // Both dates are equal -> = operator
                    $this->setValue($startValue);
                    $this->setOperator(0);
                } elseif ($endValue == '') {
                    // No end date -> > operator
                    $this->setValue($startValue);
                    $this->setOperator(1);
                }
            } elseif ($endValue != '') {
                // No start date -> < operator
                $this->setValue($endValue);
                $this->setOperator(-1);
            }
            return \true;
        }
        return \false;
    }
}
