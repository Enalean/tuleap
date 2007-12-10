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
 * @package CodeX
 */

class Rule {
    var $error;
    function isValid() {
        trigger_error(get_class($this).'::isValid() => Not yet implemented', E_USER_ERROR);
    }
    function getErrorMessage($key) {
        return $this->error;
    }
}

/**
 * Validate date provided by CodeX calendar.
 *
 * Note: this date format is more restrictive than php check date because in
 * this case, 2007-01-01 format (with zero in month or day) is not allowed.
 */
class Rule_Date
extends Rule {
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

    function error() {

    }

}

class Rule_Comparator
extends Rule {
    var $ref;
    function Rule_Comparator($ref) {
        $this->ref = $ref;
    }
}

class Rule_GreaterThan
extends Rule_Comparator {
    function isValid($val) {
        if(is_numeric($val) && $val > $this->ref) {
            return true;
        }
        return false;
    }
}

class Rule_LessThan
extends Rule_Comparator {
    function isValid($val) {
        if(is_numeric($val) && $val < $this->ref) {
            return true;
        }
        return false;
    }
}

class Rule_GreaterOrEqual
extends Rule_Comparator {
    function isValid($val) {
        if(is_numeric($val) && $val >= $this->ref) {
            return true;
        }
        return false;
    }
}

class Rule_lessOrEqual
extends Rule_Comparator {
    function isValid($val) {
        if(is_numeric($val) && $val <= $this->ref) {
            return true;
        }
        return false;
    }
}

class Rule_WhiteList
extends Rule_Comparator {
    function isValid($val) {
        if(is_array($this->ref)
           && count($this->ref) > 0
           && in_array($val, $this->ref)) {
            return true;
        }
        return false;
    }
}

class Rule_Int
extends Rule {
    /**
     * Check the format according to PHP definition of a decimal integer.
     * @see http://php.net/int
     * @access private
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
