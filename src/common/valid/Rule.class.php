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

/**
 *
 */
class Rule {
    /**
     * @access private
     */
    var $error;

    /**
     * Check if $val is a valid not.
     *
     * @param String $val Value to check.
     * @return Boolean
     */
    function isValid($val) {
        trigger_error(get_class($this).'::isValid() => Not yet implemented', E_USER_ERROR);
    }

    /**
     * Default error message if rule is not apply on value.
     *
     * @param String $val Value to check.
     * @return Boolean
     */
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
    function isValid($val) {
        if(preg_match('/^([0-9]+)-([1-9][0-2]?)-([1-9][0-9]?)$/', $val, $m)) {
            return checkdate($m[2], $m[3], $m[1]);
        } else {
            return false;
        }
    }
}

/**
 * Abstract class that define left-hand operand for a comparison.
 */
class Rule_Comparator
extends Rule {
    /**
     * @access private
     */
    var $ref;
    function Rule_Comparator($ref) {
        $this->ref = $ref;
    }
}

/**
 * Check that given value is strictly greater than the one defined in
 * constructor.
 */
class Rule_GreaterThan
extends Rule_Comparator {
    function isValid($val) {
        if(is_numeric($val) && $val > $this->ref) {
            return true;
        }
        return false;
    }
}

/**
 * Check that given value is strictly less than the one defined in constructor.
 */
class Rule_LessThan
extends Rule_Comparator {
    function isValid($val) {
        if(is_numeric($val) && $val < $this->ref) {
            return true;
        }
        return false;
    }
}

/**
 * Check that given value is greater or equal to the one defined in
 * constructor.
 */
class Rule_GreaterOrEqual
extends Rule_Comparator {
    function isValid($val) {
        if(is_numeric($val) && $val >= $this->ref) {
            return true;
        }
        return false;
    }
}

/**
 * Check that given value is strictly less or equal to the one defined in
 * constructor.
 */
class Rule_lessOrEqual
extends Rule_Comparator {
    function isValid($val) {
        if(is_numeric($val) && $val <= $this->ref) {
            return true;
        }
        return false;
    }
}

/**
 * Check that given value belong to the array defined in constructor.
 *
 * There is no type check.
 */
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

/**
 * Check that given value is a valid signed 32 bits decimal integer.
 */
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

/**
 * Check that given value is a string.
 */
class Rule_String
extends Rule {
    function isValid($val) {
        return is_string($val);
    }
}

/**
 * Check if given string contains neither a carrige return nor a null char.
 */
class Rule_NoCr
extends Rule {
    function isValid($val) {
        if(strpos($val, 0x0A) === false && strpos($val, 0x0D) === false
           && strpos($val, 0x00) === false) {
            return true;
        }
        return false;
    }
}

/**
 * Check if an email address is valid or not in CodeX context.
 *
 * This rule is influenced by a global variable 'sys_disable_subdomain'. If
 * this variable is set (no subdomain for codex) and only in this case, emails
 * like 'user@codex' are allowed.
 */
class Rule_Email
extends Rule {

    /**
     * Check email validity
     *
     * Important note: this is very important to keep the 'D' regexp modifier
     * as this is the only way not to be bothered by injections of \n into the
     * email address.
     */
    function isValid($val) {
        $valid_chars='-!#$%&\'*+0-9=?A-Z^_`a-z{|}~\.';
        if (array_key_exists('sys_disable_subdomains', $GLOBALS)
            && $GLOBALS['sys_disable_subdomains']) {
            $valid_domain='['.$valid_chars.']+$';
        } else {
            $valid_domain='['.$valid_chars.']+\.['.$valid_chars.']+$';
        }
        $regexp = '/^['.$valid_chars.']+'.'@'.$valid_domain.'/D';
        return preg_match($regexp, $val);
    }
}

?>
