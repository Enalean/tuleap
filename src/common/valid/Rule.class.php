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
        if(preg_match('/^(\d{1,4})-(\d{1,2})-(\d{1,2}?)$/', $val, $m)) {
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
 *
 * The faulty email address is available with $this->getErrorMessage();
 */
class Rule_Email
extends Rule {
    var $separator;

    function Rule_Email($separator = null) {
        $this->separator = $separator;
    }

    function isValid($val) {
        if($this->separator !== null) {
            // If separator is defined, split the string and check each email.
            $emails = split($this->separator, $val);
            $valid = true;
            while((list($key,$email) = each($emails)) && $valid) {
                $valid = $valid & $this->validEmail(trim(rtrim($email)));
            }
        } else {
            // $val must contains only one email address
            $valid = $this->validEmail($val);
        }
        return $valid;
    }

    /**
     * Check email validity
     *
     * Important note: this is very important to keep the 'D' regexp modifier
     * as this is the only way not to be bothered by injections of \n into the
     * email address.
     *
     * Spaces are allowed at the beginning and the end of the address.
     */
    function validEmail($email) {
        $valid_chars='-!#$%&\'*+0-9=?A-Z^_`a-z{|}~\.';
        if (array_key_exists('sys_disable_subdomains', $GLOBALS)
            && $GLOBALS['sys_disable_subdomains']) {
            $valid_domain='['.$valid_chars.']+';
        } else {
            $valid_domain='['.$valid_chars.']+\.['.$valid_chars.']+';
        }
        $regexp = '/^['.$valid_chars.']+'.'@'.$valid_domain.'$/D';
        return preg_match($regexp, $email);
    }
}

/**
 * Check if value match CodeX user names format.
 *
 * This rule doesn't check that user actually exists.
 */
class Rule_UserNameFormat
extends Rule {

    function containsIllegalChars($val) {
        return (strspn($val,"abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789-_") != strlen($val));
    }

    function isNotLegalName($val) {
        return preg_match('/^((root)|(bin)|(daemon)|(adm)|(lp)|(sync)|(shutdown)|(halt)|(mail)|(news)'
                          .'|(uucp)|(operator)|(games)|(mysql)|(httpd)|(nobody)|(dummy)'
                          .'|(www)|(cvs)|(shell)|(ftp)|(irc)|(debian)|(ns)|(download))$/i', $val);
    }

    function isCvsAccount($val) {
        return preg_match('/^anoncvs_/i', $val);
    }

    function lessThanMin($val) {
        return (strlen($val) < 3);
    }

    function greaterThanMax($val) {
        return (strlen($val) > 30);
    }

    function isValid($val) {
        return !$this->isNotLegalName($val)
            && !$this->isCvsAccount($val)
            && !$this->lessThanMin($val)
            && !$this->greaterThanMax($val)
            && !$this->containsIllegalChars($val);
    }
}

/**
 * Check that file was correctly uploaded doesn't by pass CodeX limits.
 *
 * Tests mainly rely on PHP $_FILES error code but add a double check of file
 * size because MAX_FILE_SIZE (used by PHP to check allowed size) is submitted
 * by the client.
 *
 * By default the maxSize is defined by 'sys_max_size_upload' CodeX
 * variable but may be customized with setMaxSize.
 */
class Rule_File
extends Rule {
    var $maxSize;
    var $i18nPageName;

    function Rule_File() {
        $this->maxSize = $GLOBALS['sys_max_size_upload'];
        $this->i18nPageName = 'rule_valid';
    }

    function setMaxSize($max) {
        $this->maxSize = $max;
    }

    function geti18nError($key, $params="") {
        $GLOBALS['Language']->loadLanguageMsg('valid/valid');
        return $GLOBALS['Language']->getText($this->i18nPageName, $key, $params);
    }

    /**
     * Check file upload validity
     *
     * @param  Array   One entry in $_FILES superarray (e.g. $_FILES['test'])
     * @return Boolean Is file upload valid or not.
     */
    function isValid($file) {
        $ok = false;
        if(is_array($file)) {
            switch($file['error']) {
            case UPLOAD_ERR_OK:
                // all is OK
                $ok = true;
                break;
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                $this->error = $this->geti18nError('error_upload_size', $file['error']);
                break;
            case UPLOAD_ERR_PARTIAL:
                $this->error = $this->geti18nError('error_upload_partial', $file['error']);
                break;
            case UPLOAD_ERR_NO_FILE:
                $this->error = $this->geti18nError('error_upload_nofile', $file['error']);
                break;
                //case UPLOAD_ERR_NO_TMP_DIR: PHP 5.0.3
                //case UPLOAD_ERR_CANT_WRITE: PHP 5.1.0
                //case UPLOAD_ERR_EXTENSION: PHP 5.2.0
            default:
                $this->error = $this->geti18nError('error_upload_unknown', $file['error']);
            }
            if($ok && $file['name'] == '') {
                $ok = false;
                $this->error = $this->geti18nError('error_upload');
            }
            if($ok) {
                // Re-check filesize (do not trust uploaded MAX_FILE_SIZE)
                if(filesize($file['tmp_name']) > $this->maxSize) {
                   $ok = false;
                   $this->error = $this->geti18nError('error_upload_size', 1);
                }
            }
        }
        return $ok;
    }
}

?>
