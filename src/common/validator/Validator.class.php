<?php
/**
 * Copyright (c) STMicroelectronics, 2007. All Rights Reserved.
 *
 * Originally written by Manuel VACELET, 2007.
 *
 * This file is a part of CodeX.
 *
 * CodeX is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * CodeX is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with CodeX; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 */

/**
 *
 */
class Valid {
    var $_errors;

    /**
     *
     */
    function Valid() {
        $this->_errors = array();
    }

    /**
     *
     */
    function addError($error) {
        $this->_errors[] = $error;
    }

    /**
     *
     */
    function getErrors() {
        return $this->_errors;
    }

    /**
     *
     */
    function isValid() {
        trigger_error(get_class($this).'::isValid() => Not yet implemented', E_USER_ERROR);
    }
}

/**
 * Validate date provided by CodeX calendar.
 *
 * Note: this date format is more restrictive than php check date because in
 * this case, 2007-01-01 format (with zero in month or day) is not allowed.
 *
 * Usage:
 * <pre>
 * $request = new HTTPRequest();
 * $validator = new DateValidator();
 *
 * if($validator->isValid($request->get('date'))) {
 *    // do stuff
 * }
 * </pre>
 *
 */
class Valid_Date
extends Valid {
    /**
     * Check if $val is a valid date or not.
     *
     * @param string $val Value to validate
     * @return boolean Whether the date is valid or not
     */
    function isValid($val) {
        if(preg_match('/^([0-9]+)-([1-9][0-2]?)-([1-9][0-9]?)$/', $val, $m)) {
            return checkdate($m[2], $m[3], $m[1]);
        } else {
            return false;
        }
    }

}

/**
 * Contains mathematical constraints shared by all numeric validators.
 *
 * By default, the principle of much restrictive applies. If lower bound is 0
 * and allowed values are ('-1', '2'). Only '2' will be a valid number.
 */
class Valid_Numerical
extends Valid {
    var $minStrict;
    var $maxStrict;
    var $minEqual;
    var $maxEqual;
    var $allowedValues;

    function Valid_Numerical() {
        $this->minStrict     = null;
        $this->maxStrict     = null;
        $this->minEqual      = null;
        $this->maxEqual      = null;
        $this->allowedValues = null;
        parent::valid();
    }

    /**
     * Define the lower bound for strict value comparison.
     */
    function biggerThan($min) {
        $this->minStrict = $min;
    }

    /**
     * Define the lower bound for strict value comparison.
     */
    function biggerOrEqual($min) {
        $this->minEqual = $min;
    }


    /**
     * Define the upper bound for strict value comparison.
     */
    function lesserThan($max) {
        $this->maxStrict = $max;
    }

    /**
     * Define the upper bound for strict value comparison.
     */
    function lesserOrEqual($max) {
        $this->maxEqual = $max;
    }

    /**
     * Define a list of allowed value.
     */
    function allowedValues($v) {
        $this->allowedValues = $v;
    }

    /**
     * @access protected
     */
    function checkMinEqual($val) {
         if($this->minEqual !== null) {
             if($val >= $this->minEqual) {
                 return true;
             } else {
                 return false;
             }
         }
         return true;
    }

    /**
     * @access protected
     */
    function checkMaxEqual($val) {
         if($this->maxEqual !== null) {
             if($val <= $this->maxEqual) {
                 return true;
             } else {
                 return false;
             }
         }
         return true;
    }

    /**
     * @access protected
     */
    function checkMinStrict($val) {
        if($this->minStrict !== null) {
            if($val <= $this->minStrict) {
                return false;
            }
        }
        return true;
    }

    /**
     * @access protected
     */
    function checkMaxStrict($val) {
        if($this->maxStrict !== null) {
            if($val >= $this->maxStrict) {
                return false;
            }
        }
        return true;
    }

    /**
     * @access protected
     */
    function checkAllowedValues($val) {
        if(is_array($this->allowedValues)
           && count($this->allowedValues) > 0) {
            if(!in_array($val, $this->allowedValues)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Check that $val respect all the constraints.
     */
    function isValid($val) {
        $isValid = true;
        $isValid = $isValid && $this->checkMinEqual($val);
        $isValid = $isValid && $this->checkMaxEqual($val);
        $isValid = $isValid && $this->checkMinStrict($val);
        $isValid = $isValid && $this->checkMaxStrict($val);
        $isValid = $isValid && $this->checkAllowedValues($val);
        return $isValid;
    }

}

/**
 * Validate decimal integer value between (-2^31;2^31-1).
 *
 * @see BigIntValidator
 */
class Valid_Int
extends Valid_Numerical {
    /**
     * Check the format according to PHP definition of a decimal integer.
     * @see http://php.net/int
     */
    function checkFormat($val) {
        if(preg_match('/^([+-]?[1-9][0-9]*|[+-]?0)$/', $val)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Check is $val is a valid integer or not.
     *
     * @param string $val Value to validate
     * @return boolean Whether the value is a valid integer or not
     */
    function isValid($val) {
        // Need to check with the regexp because of octal form '0123' that is
        // equal to '123' with string '==' comparison.
        if($this->checkFormat($val)) {
            // Check (-2^31;2^31-1) range
            if(strval(intval($val)) == $val) {
                if(parent::isValid($val)) {
                    return true;
                } else {
                    return false;
                }
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

}

/**
 * Validate decimal integer values bigger than 2^31-1 or lesser than -2^31.
 *
 * Php has a limitation with integer handeling. On most platform integers are
 * coded on 32 bits so the valid range of int values is (-2^31;2^31-1).
 * This class validate any kind of integers.
 *
 * However, as those number are not well handled by PHP, we cannot make any
 * mathematical operations safly on them. This is why comparators are disabled.
 *
 */
class Valid_BigInt
extends Valid_Int {
    /**
     * Disabled
     */
    function biggerThan($min) {
        trigger_error(get_class($this).'::biggerThan() => Not possible with BigIntegers', E_USER_ERROR);
    }

    /**
     * Disabled
     */
    function lesserThan($max) {
        trigger_error(get_class($this).'::lesserThan() => Not possible with BigIntegers', E_USER_ERROR);
    }

    /**
     * Disabled
     */
    function allowedValues($v) {
        trigger_error(get_class($this).'::allowedValues() => Not possible with BigIntegers', E_USER_ERROR);
    }

    /**
     * Check is $val is a valid integer or not.
     *
     * @param string $val Value to validate
     * @return boolean Whether the value is a valid integer or not
     */
    function isValid($val) {
        if($this->checkFormat($val)) {
            return true;
        } else {
            return false;
        }
    }
}

?>
