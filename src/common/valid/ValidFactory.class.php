<?php
/**
 * Copyright (c) Enalean, 2012 - 2018. All Rights Reserved.
 * Copyright (c) STMicroelectronics, 2007. All Rights Reserved.
 *
 * Originally written by Manuel VACELET, 2007.
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * Check that value is a decimal integer.
 */
class Valid_Int extends Valid
{
    public function validate($value)
    {
        $this->addRule(new Rule_Int());
        return parent::validate($value);
    }
}

/**
 * Check that value is a decimal integer greater or equal to zero.
 */
class Valid_UInt extends Valid_Int
{
    public function validate($value)
    {
        $this->addRule(new Rule_GreaterOrEqual(0));
        return parent::validate($value);
    }
}

/**
 * Check that value is a numeric greater than 0.
 */
class Valid_Numeric extends Valid
{

    public function validate($value)
    {
        $this->addRule(new Rule_GreaterOrEqual(0));
        return parent::validate($value);
    }
}

/**
 * Check that group_id variable is valid
 */
class Valid_GroupId extends Valid
{
    public function __construct()
    {
        parent::__construct('group_id');
        //$this->setErrorMessage($GLOBALS['Language']->getText('include_exit','no_gid_err'));
    }

    public function validate($value)
    {
        $this->addRule(new Rule_Int());
        $this->addRule(new Rule_GreaterThan(0));
        return parent::validate($value);
    }
}

/**
 * Check that 'pv' parameter is set to an acceptable value.
 */
class Valid_Pv extends Valid
{
    public function __construct()
    {
        parent::__construct('pv');
    }

    public function validate($value)
    {
        $this->addRule(new Rule_WhiteList(array(0,1,2)));
        return parent::validate($value);
    }
}

/**
 * Check that value is a string (should always be true).
 */
class Valid_Text extends Valid
{
    public function validate($value)
    {
        $this->addRule(new Rule_String());
        return parent::validate($value);
    }
}

/**
 * Check that value is a string with neither carrige return nor null char.
 */
class Valid_String extends Valid_Text
{
    public function validate($value)
    {
        $this->addRule(new Rule_NoCr());
        return parent::validate($value);
    }
}

/**
 * Check that value is a possible HTTP(S) URI
 */
class Valid_HTTPURI extends Valid_String
{
    public function validate($value)
    {
        $this->addRule(new Rule_Regexp('/^(http:\/\/|https:\/\/)/i'));
        return parent::validate($value);
    }
}

/**
 * Check that value is a possible HTTPS URI
 */
class Valid_HTTPSURI extends Valid_String
{
    public function validate($value)
    {
        $this->addRule(new Rule_Regexp('/^https:\/\//i'));
        return parent::validate($value);
    }
}

/**
 * Check that value is a possible local URI
 */
class Valid_LocalURI extends Valid_String
{
    public const URI_REGEXP = '/^(http:\/\/|https:\/\/|#|\/|\?)/i';

    public function validate($value)
    {
        $this->addRule(new Rule_Regexp(self::URI_REGEXP));

        return parent::validate($value);
    }
}

/**
 * Check that value is a possible FTP(S) URI
 */
class Valid_FTPURI extends Valid_String
{
    public const URI_REGEXP = '/^ftps?:\/\/.+/i';

    public function validate($value)
    {
        $this->addRule(new Rule_Regexp(self::URI_REGEXP));

        return parent::validate($value);
    }
}

/**
 * Check that value is a possible mail URI
 */
class Valid_MailtoURI extends Valid_String
{
    public function validate($value)
    {
        $this->addRule(new Rule_Regexp('/^mailto:[^[:space:]].*/i'));
        return parent::validate($value);
    }
}

/**
 * Check that value is an array.
 */
class Valid_Array extends Valid
{
    public function validate($value)
    {
        $this->addRule(new Rule_Array());
        return parent::validate($value);
    }
}


/**
 * Wrapper for 'WhiteList' rule
 */
class Valid_WhiteList extends Valid
{
    public function __construct($key, $whitelist)
    {
        parent::__construct($key);
        $this->addRule(new Rule_WhiteList($whitelist));
    }
}

/**
 * Check that value match user short name format.
 *
 * This rule doesn't check that user actually exists.
 */
class Valid_UserNameFormat extends Valid_String
{
    public function validate($value)
    {
        $this->addRule(new Rule_String());
        $this->addRule(new Rule_UserName());
        return parent::validate($value);
    }
}

class Valid_GenericUserNameSuffix extends Valid_UserNameFormat
{
    /**
     * Append a fake prefix to leverage on username format checking.
     *
     * As we want to validate a suffix, we need to append it to something
     * we now as valid otherwise the check might be invalid. For instance:
     * '-team' is a valid suffix but an invalid UserNameFormat (cannot start
     * by '-'
     * But aaa-team is a valid name at whole.
     */
    public const FAKE_PREFIX = 'aaa';

    public function validate($value)
    {
        return parent::validate(self::FAKE_PREFIX . $value);
    }
}

/**
 * Check that value match user real name format.
 */
class Valid_RealNameFormat extends Valid_String
{
    public function validate($value)
    {
        $this->addRule(new Rule_String());
        $this->addRule(new Rule_RealName());
        return parent::validate($value);
    }
}


/**
 * Check that submitted value is a simple string and a valid email.
 */
class Valid_Email extends Valid_String
{
    public $separator;

    public function __construct($key = null, $separator = null)
    {
        if (is_string($separator)) {
            $this->separator = $separator;
        } else {
            $this->separator = null;
        }
        parent::__construct($key);
    }

    public function validate($value)
    {
        $this->addRule(new Rule_Email($this->separator));
        return parent::validate($value);
    }
}

/**
 * Check uploaded file validity.
 */
class Valid_File extends Valid
{

    /**
     * Is uploaded file empty or not.
     *
     * @param Array One entry of $_FILES
     */
    public function isEmptyValue($file)
    {
        if (!is_array($file)) {
            return false;
        } elseif (parent::isEmptyValue($file['name'])) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * Check rules on given file.
     *
     * @param  Array  $files $_FILES superarray.
     * @param  String Index of file to check in $_FILES array.
     * @return bool
     */
    public function validate($files, $index = '')
    {
        if (is_array($files) && isset($files[$index])) {
            $this->addRule(new Rule_File());
            return parent::validate($files[$index]);
        } elseif ($this->isRequired) {
            return false;
        } else {
            return true;
        }
    }
}


class ValidFactory
{
    /**
     * If $validator is an instance of a Validator, do nothing and returns it
     * If $validator is a string and a validator exists (Valid_String for 'string', Valid_UInt for 'uint', ...) then creates an instance and returns it
     * Else returns null
     */
    public static function getInstance($validator, $key = null)
    {
        if ($validator instanceof \Valid) {
            return $validator;
        }
        if (! is_string($validator)) {
            return null;
        }
        $class_name = 'Valid_' . $validator;
        if (! class_exists($class_name)) {
            return null;
        }
        return new $class_name($key);
    }
}
