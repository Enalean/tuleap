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
    var $value;
    var $md;

    function __construct($md)
    {
        $this->value = null;
        $this->md = $md;
    }

    function setValue($v)
    {
        $this->value = $v;
    }
    function getValue()
    {
        return $this->value;
    }

    function initFromRow($row)
    {
    }

    function getUrlParameters()
    {
        $param = array();
        //if($this->value !== null) {
            $param[$this->md->getLabel()] = $this->value;
            //}
        return $param;
    }

    function _urlMatchDelete($request)
    {
        if ($request->exist('del_filter')
           && $this->md->getLabel() == $request->get('del_filter')) {
            return true;
        }
        return false;
    }

    function _urlValueIsValid($request)
    {
        if ($request->exist($this->md->getLabel())) {
            return true;
        }
        return false;
    }

    function _urlMatchUpdate($request)
    {
        if ($this->_urlValueIsValid($request)) {
            $this->setValue($request->get($this->md->getLabel()));
            return true;
        }
        return false;
    }

    // Add new fields
    function _urlMatchAdd($request)
    {
        if ($request->exist('add_filter')
           && $this->md->getLabel() == $request->get('add_filter')) {
            return true;
        }
        return false;
    }

    function initOnUrlMatch($request)
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
    var $operator;
    var $field_operator_name;
    var $field_value_name;

    function __construct($md)
    {
        parent::__construct($md);
        $this->operator = null;
        if ($md !== null) {
            $this->field_operator_name  = $md->getLabel().'_operator';
            $this->field_value_name     = $md->getLabel().'_value';
        }
    }

    function initFromRow($row)
    {
        $this->setOperator($row['value_date_op']);
        $this->setValue($row['value_date1']);
    }

    function getFieldOperatorName()
    {
        return $this->field_operator_name;
    }

    function getFieldValueName()
    {
        return $this->field_value_name;
    }

    function setOperator($v)
    {
        $this->operator = $v;
    }

    function getOperator()
    {
        return $this->operator;
    }

    function isValidDateFormat($value)
    {
        if (preg_match('/^([0-9]{4})-([0-9]{1,2})-([0-9]{1,2})$/', $value, $d)) {
            return true;
        }
        return false;
    }

    function isValidOperator($op)
    {
        if ($op == 0 ||
           $op == -1 ||
           $op == 1) {
            return true;
        }
        return false;
    }

    function getUrlParameters()
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

    function _urlMatchUpdate($request)
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
        if ($request->exist($advSearch->getFieldStartValueName()) &&
           $this->isValidDateFormat($request->get($advSearch->getFieldStartValueName()))) {
            $startValue = $request->get($advSearch->getFieldStartValueName());
            $endValue = '';
            if ($request->exist($advSearch->getFieldEndValueName()) &&
               $this->isValidDateFormat($request->get($advSearch->getFieldEndValueName()))) {
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
    var $fieldNameStart;
    var $fieldNameEnd;
    var $valueStart;
    var $valueEnd;

    function __construct($md)
    {
        parent::__construct($md);

        $base = $md->getLabel().'_value';
        $this->fieldNameStart = $base.'_start';
        $this->fieldNameEnd   = $base.'_end';
        $this->valueStart = '';
        $this->valueEnd   = '';
    }

    function setValueStart($v)
    {
        $this->valueStart = $v;
    }
    function getValueStart()
    {
        return $this->valueStart;
    }

    function setValueEnd($v)
    {
        $this->valueEnd = $v;
    }
    function getValueEnd()
    {
        return $this->valueEnd;
    }

    function initFromRow($row)
    {
        $this->setValueStart($row['value_date1']);
        $this->setValueEnd($row['value_date2']);
    }

    function getFieldStartValueName()
    {
        return $this->fieldNameStart;
    }
    function getFieldEndValueName()
    {
        return $this->fieldNameEnd;
    }

    function getUrlParameters()
    {
        $param = array();
        $param[$this->fieldNameStart] = $this->valueStart;
        $param[$this->fieldNameEnd]   = $this->valueEnd;
        return $param;
    }

    function _urlMatchUpdate($request)
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
            if ($request->exist($this->getFieldOperatorName())
               && $request->exist($this->getFieldValueName())) {
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

    function __construct($md)
    {
        $mdFactory = new Docman_MetadataFactory($md->getGroupId());
        $mdFactory->appendMetadataValueList($md, false);
        parent::__construct($md);
        $this->setValue(0);
    }

    function initFromRow($row)
    {
        $this->setValue($row['value_love']);
    }

    /**
     * @todo: should valid an int
     */
    function isValidListValue($val)
    {
        if (is_numeric($val)) {
            return true;
        }
        return false;
    }

    function _urlValueIsValid($request)
    {
        if (parent::_urlValueIsValid($request)) {
            if ($this->isValidListValue($request->get($this->md->getLabel()))) {
                return true;
            }
        }
        return false;
    }

    function _urlMatchUpdate($request)
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

    function __construct($md)
    {
        parent::__construct($md);
        $this->setValue(array());
    }

    function _urlValueIsValid($request)
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

    function _urlMatchUpdate($request)
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

    function _urlMatchAdd($request)
    {
        if (parent::_urlMatchAdd($request)) {
            $this->setValue(array(0));
            return true;
        }
        return false;
    }

    function addValue($val)
    {
        $this->value[] = $val;
    }

    function initFromRow($row)
    {
        $this->addValue($row['value_love']);
    }
}

/**
* Item type filters
*/
class Docman_FilterItemTypeAdvanced extends Docman_FilterListAdvanced
{
    function __construct($md)
    {
        Docman_Filter::__construct($md);
        $this->setValue(array());
    }
}

class Docman_FilterItemType extends Docman_FilterList
{
    function __construct($md)
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

    function __construct($md)
    {
        parent::__construct($md);
    }

    function initFromRow($row)
    {
        $this->setValue($row['value_string']);
    }

    function getUrlParameters()
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
    var $dynTextFields;

    function __construct($md, $dynTextFields)
    {
        parent::__construct($md);
        $this->dynTextFields = $dynTextFields;
    }

    function initFromRow($row)
    {
        $this->setValue($row['value_string']);
    }
}

class Docman_FilterOwner extends Docman_Filter
{

    function __construct($md)
    {
        parent::__construct($md);
    }

    function initFromRow($row)
    {
        $this->setValue($row['value_string']);
    }

    function _urlMatchUpdate($request)
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
