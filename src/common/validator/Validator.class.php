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
class Validator {
    var $_errors;

    /**
     *
     */
    function Validator() {
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
class DateValidator
extends Validator {
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
 * Validate decimal integer values bigger than 2^31-1 or lesser than -2^31.
 *
 * Php has a limitation with integer handeling. On most platform integers are
 * coded on 32 bits so the valid range of int values is (-2^31;2^31-1).
 * This class validate any kind of integers.
 *
 */
class BigIntValidator
extends Validator {
    /**
     * Check is $val is a valid integer or not.
     *
     * @param string $val Value to validate
     * @return boolean Whether the value is a valid integer or not
     */
    function isValid($val) {
        if(preg_match('/^([+-]?[1-9][0-9]*|[+-]?0)$/', $val)) {
            return true;
        } else {
            return false;
        }
    }

}

/**
 * Validate decimal integer value between (-2^31;2^31-1).
 *
 * @see BigIntValidator
 */
class IntValidator
extends Validator {
    /**
     * Check is $val is a valid integer or not.
     *
     * @param string $val Value to validate
     * @return boolean Whether the value is a valid integer or not
     */
    function isValid($val) {
        // Need to check with the regexp because of octal form '0123' that is
        // equal to '123' with string '==' comparison.
        if(BigIntValidator::isValid($val)) {
            // Check (-2^31;2^31-1) range
            if(strval(intval($val)) == $val) {
                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

}

?>
