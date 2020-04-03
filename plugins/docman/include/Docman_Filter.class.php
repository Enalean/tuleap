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

class Docman_Filter
{
    public $value;
    public $md;

    public function __construct($md)
    {
        $this->value = null;
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
        $param = array();
        //if($this->value !== null) {
            $param[$this->md->getLabel()] = $this->value;
            //}
        return $param;
    }

    public function _urlMatchDelete($request)
    {
        if (
            $request->exist('del_filter')
            && $this->md->getLabel() == $request->get('del_filter')
        ) {
            return true;
        }
        return false;
    }

    public function _urlValueIsValid($request)
    {
        if ($request->exist($this->md->getLabel())) {
            return true;
        }
        return false;
    }

    public function _urlMatchUpdate($request)
    {
        if ($this->_urlValueIsValid($request)) {
            $this->setValue($request->get($this->md->getLabel()));
            return true;
        }
        return false;
    }

    // Add new fields
    public function _urlMatchAdd($request)
    {
        if (
            $request->exist('add_filter')
            && $this->md->getLabel() == $request->get('add_filter')
        ) {
            return true;
        }
        return false;
    }

    public function initOnUrlMatch($request)
    {
        if ($this->md !== null) {
            if (!$this->_urlMatchDelete($request)) {
                if ($this->_urlMatchUpdate($request)) {
                    return true;
                } else {
                    return $this->_urlMatchAdd($request);
                }
            }
        }
        return false;
    }
}

/**
 * Filter on date metadata
 */
class Docman_FilterDate extends Docman_Filter
{
    public $operator;
    public $field_operator_name;
    public $field_value_name;

    public function __construct($md)
    {
        parent::__construct($md);
        $this->operator = null;
        if ($md !== null) {
            $this->field_operator_name  = $md->getLabel() . '_operator';
            $this->field_value_name     = $md->getLabel() . '_value';
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
        if (preg_match('/^([0-9]{4})-([0-9]{1,2})-([0-9]{1,2})$/', $value, $d)) {
            return true;
        }
        return false;
    }

    public function isValidOperator($op)
    {
        if (
            $op == 0 ||
            $op == -1 ||
            $op == 1
        ) {
            return true;
        }
        return false;
    }

    public function getUrlParameters()
    {
        $param = array();
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
            return true;
        }

        // If no values found, try to get fields from advanced search
        $advSearch = new Docman_FilterDateAdvanced($this->md);
        if (
            $request->exist($advSearch->getFieldStartValueName()) &&
            $this->isValidDateFormat($request->get($advSearch->getFieldStartValueName()))
        ) {
            $startValue = $request->get($advSearch->getFieldStartValueName());
            $endValue = '';
            if (
                $request->exist($advSearch->getFieldEndValueName()) &&
                $this->isValidDateFormat($request->get($advSearch->getFieldEndValueName()))
            ) {
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
            return true;
        }
        return false;
    }
}

class Docman_FilterDateAdvanced extends Docman_FilterDate
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
        $this->fieldNameEnd   = $base . '_end';
        $this->valueStart = '';
        $this->valueEnd   = '';
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
        $param = array();
        $param[$this->fieldNameStart] = $this->valueStart;
        $param[$this->fieldNameEnd]   = $this->valueEnd;
        return $param;
    }

    public function _urlMatchUpdate($request)
    {
        $fieldExist = false;

        $startValue = false;
        if ($request->exist($this->fieldNameStart)) {
            $fieldExist = true;
            if ($this->isValidDateFormat($request->get($this->fieldNameStart))) {
                $this->setValueStart($request->get($this->fieldNameStart));
                $startValue = true;
            }
        }
        $endValue = false;
        if ($request->exist($this->fieldNameEnd)) {
            $fieldExist = true;
            if ($this->isValidDateFormat($request->get($this->fieldNameEnd))) {
                $this->setValueEnd($request->get($this->fieldNameEnd));
                $endValue = true;
            }
        }

        // If no values found, try to get values from simple search
        if (!$startValue && !$endValue) {
            if (
                $request->exist($this->getFieldOperatorName())
                && $request->exist($this->getFieldValueName())
            ) {
                switch ($request->get($this->getFieldOperatorName())) {
                    case '-1': // '<'
                        $this->setValueEnd($request->get($this->getFieldValueName()));
                        break;
                    case '0': // '='
                        $this->setValueEnd($request->get($this->getFieldValueName()));
                        $this->setValueStart($request->get($this->getFieldValueName()));
                        break;
                    case '1': // '>'
                    default:
                        $this->setValueStart($request->get($this->getFieldValueName()));
                }
                $fieldExist = true;
            }
        }
        return $fieldExist;
    }
}

/**
 * Filter on ListOfValues
 */
class Docman_FilterList extends Docman_Filter
{

    public function __construct($md)
    {
        $mdFactory = new Docman_MetadataFactory($md->getGroupId());
        $mdFactory->appendMetadataValueList($md, false);
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
        if (is_numeric($val)) {
            return true;
        }
        return false;
    }

    public function _urlValueIsValid($request)
    {
        if (parent::_urlValueIsValid($request)) {
            if ($this->isValidListValue($request->get($this->md->getLabel()))) {
                return true;
            }
        }
        return false;
    }

    public function _urlMatchUpdate($request)
    {
        if (parent::_urlMatchUpdate($request)) {
            $v = $this->getValue();

            if (is_array($v)) {
                // Convert advanced filter value to simple
                if (count($v) == 1 && $this->isValidListValue($v[0])) {
                    $this->setValue($v[0]);
                } else {
                    $this->setValue(0);
                }
            }
            return true;
        }
        return false;
    }
}

/**
 * Advanced filter on ListOfValues: can select several values
 */
class Docman_FilterListAdvanced extends Docman_FilterList
{

    public function __construct($md)
    {
        parent::__construct($md);
        $this->setValue(array());
    }

    public function _urlValueIsValid($request)
    {
        if ($request->exist($this->md->getLabel())) {
            $val = $request->get($this->md->getLabel());
            if (is_array($val)) {
                $allInt = true;
                foreach ($val as $v) {
                    $allInt = ($allInt && $this->isValidListValue($v));
                }
                return $allInt;
            } else {
                if ($this->isValidListValue($val)) {
                    return true;
                }
            }
        }
        return false;
    }

    public function _urlMatchUpdate($request)
    {
        if (Docman_Filter::_urlMatchUpdate($request)) {
            if (!is_array($this->getValue())) {
                if ($this->getValue() !== null && $this->getValue() != '') {
                    // Convert simple value to advanced
                    $this->setValue(array($this->getValue()));
                } else {
                    $this->setValue(array());
                }
            } elseif (count($this->getValue()) == 1) {
                // If empty value, clean-up
                $v = $this->getValue();
                if ($v[0] == '') {
                    $this->setValue(array(0));
                }
            }
            return true;
        }
        return false;
    }

    public function _urlMatchAdd($request)
    {
        if (parent::_urlMatchAdd($request)) {
            $this->setValue(array(0));
            return true;
        }
        return false;
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

/**
* Item type filters
*/
class Docman_FilterItemTypeAdvanced extends Docman_FilterListAdvanced
{
    public function __construct($md)
    {
        Docman_Filter::__construct($md);
        $this->setValue(array());
    }
}

class Docman_FilterItemType extends Docman_FilterList
{
    public function __construct($md)
    {
        Docman_Filter::__construct($md);
        $this->setValue(0);
    }
}

/**
 * Filter on any textual values
 */
class Docman_FilterText extends Docman_Filter
{

    public function __construct($md)
    {
        parent::__construct($md);
    }

    public function initFromRow($row)
    {
        $this->setValue($row['value_string']);
    }

    public function getUrlParameters()
    {
        $hp = Codendi_HTMLPurifier::instance();
        $param = array($this->md->getLabel() => $hp->purify($this->value));
        return $param;
    }
}

/**
 * Filter on all the text fields
 */
class Docman_FilterGlobalText extends Docman_FilterText
{
    public $dynTextFields;

    public function __construct($md, $dynTextFields)
    {
        parent::__construct($md);
        $this->dynTextFields = $dynTextFields;
    }

    public function initFromRow($row)
    {
        $this->setValue($row['value_string']);
    }
}

class Docman_FilterOwner extends Docman_Filter
{

    public function __construct($md)
    {
        parent::__construct($md);
    }

    public function initFromRow($row)
    {
        $this->setValue($row['value_string']);
    }

    public function _urlMatchUpdate($request)
    {
        if (parent::_urlMatchUpdate($request)) {
            $user = UserManager::instance()->findUser($this->getValue());
            if ($user) {
                $this->setValue($user->getUserName());
            }
            return true;
        }
        return false;
    }
}
