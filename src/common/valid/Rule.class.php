<?php
/**
 * Copyright (c) Enalean, 2012-Present. All Rights Reserved.
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

abstract class Rule
{
    /**
     * @access private
     */
    public $error;

    /**
     * Constructor
     */
    public function __construct()
    {
    }

    /**
     * Check if $val is a valid not.
     *
     * @param String $val Value to check.
     * @return bool
     */
    abstract public function isValid($val);

    /**
     * Default error message if rule is not apply on value.
     *
     * @param string $key
     * @return string
     */
    public function getErrorMessage($key = '')
    {
        return $this->error;
    }
}

/**
 * Validate date provided by Codendi calendar.
 *
 * Note: this date format is more restrictive than php check date because in
 * this case, 2007-01-01 format (with zero in month or day) is not allowed.
 */
class Rule_Date extends Rule
{
    public const DAY_REGEX     = '/^(\d{1,4})-(\d{1,2})-(\d{1,2}?)$/';

    public function isValid($val)
    {
        if (preg_match(self::DAY_REGEX, $val, $m)) {
            return checkdate($m[2], $m[3], $m[1]);
        } else {
            return false;
        }
    }
}

class Rule_Date_Time extends Rule
{
    public const DAYTIME_REGEX = '/^(\d{1,4})-(\d{1,2})-(\d{1,2}?) (\d{2}):(\d{2})(?::\d{2})?$/';

    public function isValid($val)
    {
        if (! preg_match(self::DAYTIME_REGEX, $val, $m)) {
            return false;
        }
        return (bool) strtotime($val);
    }
}

class Rule_Timestamp extends Rule
{
    public const TIMESTAMP_REGEX = '/^[0-9]+$/';

    public function isValid($val)
    {
        return preg_match(self::TIMESTAMP_REGEX, $val) === 1;
    }
}

/**
 * Abstract class that define left-hand operand for a comparison.
 */
abstract class Rule_Comparator extends Rule
{
    /**
     * @access private
     */
    public $ref;
    public function __construct($ref)
    {
        $this->ref = $ref;
    }
}

class Rule_GreaterThan extends Rule_Comparator
{
    public function isValid($val)
    {
        if (is_numeric($val) && $val > $this->ref) {
            return true;
        }
        return false;
    }
}

class Rule_LessThan extends Rule_Comparator
{
    public function isValid($val)
    {
        if (is_numeric($val) && $val < $this->ref) {
            return true;
        }
        return false;
    }
}

class Rule_GreaterOrEqual extends Rule_Comparator
{
    public function isValid($val)
    {
        if (is_numeric($val) && $val >= $this->ref) {
            return true;
        }
        return false;
    }
}

class Rule_lessOrEqual extends Rule_Comparator
{
    public function isValid($val)
    {
        if (is_numeric($val) && $val <= $this->ref) {
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
class Rule_WhiteList extends Rule_Comparator
{
    public function isValid($val)
    {
        if (
            is_array($this->ref)
            && count($this->ref) > 0
            && in_array($val, $this->ref)
        ) {
            return true;
        }
        return false;
    }
}

/**
 * Check that given value is a valid signed 32 bits decimal integer.
 */
class Rule_Int extends Rule
{
    /**
     * Check the format according to PHP definition of a decimal integer.
     * @see http://php.net/int
     * @access private
     */
    public function checkFormat($val)
    {
        if (preg_match('/^([+-]?[1-9][0-9]*|[+-]?0)$/', $val)) {
            return true;
        } else {
            return false;
        }
    }

    public function isValid($val)
    {
        // Need to check with the regexp because of octal form '0123' that is
        // equal to '123' with string '==' comparison.
        if ($this->checkFormat($val)) {
            // Check (-2^31;2^31-1) range
            if (strval(intval($val)) == $val) {
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
class Rule_String extends Rule
{
    public function isValid($val)
    {
        return is_string($val);
    }
}

/**
 * Check that given value is an array.
 */
class Rule_Array extends Rule
{
    public function isValid($val)
    {
        return is_array($val);
    }
}

/**
 * Check if given string contains neither a carrige return nor a null char.
 */
class Rule_NoCr extends Rule
{
    public function isValid($val)
    {
        if (
            is_string($val) && strpos($val, 0x0A) === false && strpos($val, 0x0D) === false
            && strpos($val, 0x00) === false
        ) {
            return true;
        }
        return false;
    }
}

/**
 * Check if given string match a pattern
 */
class Rule_Regexp extends Rule
{
    protected $pattern;

    public function __construct($pattern)
    {
        parent::__construct();
        $this->pattern = $pattern;
    }

    public function isValid($val): bool
    {
        return (bool) preg_match($this->pattern, $val);
    }
}

/**
 * Check if an email address is valid or not in Codendi context.
 *
 * This rule is influenced by a global variable 'sys_disable_subdomain'. If
 * this variable is set (no subdomain for codendi) and only in this case, emails
 * like 'user@codendi' are allowed.
 *
 * The faulty email address is available with $this->getErrorMessage();
 */
class Rule_Email extends Rule
{
    public $separator;

    public function __construct($separator = null)
    {
        parent::__construct();
        $this->separator = $separator;
    }

    public function isValid($val): bool
    {
        if ($this->separator !== null) {
            // If separator is defined, split the string and check each email.
            $emails = preg_split('/' . $this->separator . '/D', $val);
            foreach ($emails as $email) {
                if (! $this->validEmail(trim(rtrim($email)))) {
                    return false;
                }
            }
            return true;
        }
        // $val must contains only one email address
        return (bool) $this->validEmail($val);
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
    public function validEmail($email)
    {
        $valid_chars = '-!#$%&\'*+0-9=?A-Z^_`a-z{|}~\.';
        if (
            array_key_exists('sys_disable_subdomains', $GLOBALS)
            && $GLOBALS['sys_disable_subdomains']
        ) {
            $valid_domain = '[' . $valid_chars . ']+';
        } else {
            $valid_domain = '[' . $valid_chars . ']+\.[' . $valid_chars . ']+';
        }
        $regexp = '/^[' . $valid_chars . ']+' . '@' . $valid_domain . '$/D';
        return preg_match($regexp, $email);
    }
}


/**
 * Check if value match Codendi user names format.
 *
 * This rule doesn't check that user actually exists.
 */
class Rule_UserName extends Rule
{

    public const RESERVED_PREFIX = 'forge__';

    /**
     * Test if value is a name on underlying OS.
     *
     * @param String $val Value to test
     *
     * @return bool
     */
    public function isSystemName($val)
    {
        $backend = $this->_getBackend();
        if ($backend->unixUserExists($val) || $backend->unixGroupExists($val)) {
            $this->error = $this->_getErrorExists();
            return true;
        }
        return false;
    }

    /**
     * Test is the value is Codendi username
     *
     * @param String $val Value to test
     *
     * @return bool
     */
    public function isAlreadyUserName($val)
    {
        $um = $this->_getUserManager();
        if ($um->getUserByUserName($val) !== null) {
            $this->error = $this->_getErrorExists();
            return true;
        }
        return false;
    }

    /**
     * Test if the value is a project name
     *
     * @param String $val Value to test
     *
     * @return bool
     */
    public function isAlreadyProjectName($val)
    {
        $pm = $this->_getProjectManager();
        if ($pm->getProjectByUnixName($val) !== null) {
            $this->error = $this->_getErrorExists();
            return true;
        }
        return false;
    }

    /**
     * Test if the value contains spaces
     *
     * @param String $val Value to test
     *
     * @return bool
     */
    public function noSpaces($val)
    {
        if (strrpos($val, ' ') !== false) {
            $this->error = $this->_getErrorNoSpaces();
            return false;
        }
        return true;
    }

    /**
     * Needs to check the name start by a char
     *
     * @param String $val
     *
     * @return bool
     */
    public function atLeastOneChar($val)
    {
        if (strspn($val, "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ") == 0) {
            $this->error = $GLOBALS['Language']->getText('include_account', 'char_err');
            return false;
        }
        return true;
    }

    /**
     * Test if the name contains illegal chars
     *
     * @param String $val Value to test
     *
     * @return bool
     */
    public function containsIllegalChars($val)
    {
        if (strspn($val, "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789-_.") != strlen($val)) {
            $this->error = $GLOBALS['Language']->getText('include_account', 'illegal_char');
            return true;
        }
        return false;
    }

    /**
     * Test if the name is already reserved
     *
     * @param String $val Value to test
     *
     * @return bool
     */
    public function isReservedName($val)
    {
        $is_reserved_name = preg_match('/^(' .
             '(www[0-9]?)|(cvs[0-9]?)|(shell[0-9]?)|(ftp[0-9]?)|(irc[0-9]?)|(news[0-9]?)' .
             '|(mail[0-9]?)|(ns[0-9]?)|(download[0-9]?)|(pub)|(users)|(compile)|(lists)' .
             '|(slayer)|(orbital)|(tokyojoe)|(webdev)|(projects)|(cvs)|(monitor)|(mirrors?)' .
             '|(root)|(bin)|(daemon)|(adm)|(lp)|(sync)|(shutdown)|(halt)|(mail)' .
             '|(uucp)|(operator)|(games)|(mysql)|(httpd)|(nobody)|(dummy)' .
             '|(munin)|(mailman)|(ftpadmin)|(codendiadm)|(imadmin-bot)|(apache)|(nscd)' .
             '|(git)|(gitolite)' .
             ')$/i', $val);
        $is_reserved_prefix = $this->isReservedPrefix($val);

        if ($is_reserved_name || $is_reserved_prefix) {
            $this->error = $GLOBALS['Language']->getText('include_account', 'reserved');
            return true;
        }
        return false;
    }

    /**
     * Test if the name begins with a reserved prefix
     *
     * @param string $val Value to test
     *
     * @return bool
     */
    private function isReservedPrefix($val)
    {
        if (strpos($val, self::RESERVED_PREFIX) === 0) {
            return true;
        }
        return false;
    }

    /**
     * Test if the name corresponds to a CVS user account
     *
     * @param String $val Value to test
     *
     * @return bool
     */
    public function isCvsAccount($val)
    {
        if (preg_match('/^anoncvs_/i', $val)) {
            $this->error = $GLOBALS['Language']->getText('include_account', 'reserved_cvs');
            return true;
        }
        return false;
    }

    /**
     * Test minimal length of name
     *
     * @param String $val Value to test
     *
     * @return bool
     */
    public function lessThanMin($val)
    {
        if (strlen($val) < 3) {
            $this->error = $GLOBALS['Language']->getText('include_account', 'name_too_short');
            return true;
        }
        return false;
    }

    /**
     * Test maximal length of name
     *
     * @param String  $val Value to test
     * @param int $max maximal length (default = 30)
     *
     * @return bool
     */
    public function greaterThanMax($val, $max = 30)
    {
        if (strlen($val) > $max) {
            $this->error = $GLOBALS['Language']->getText('include_account', 'name_too_long', $max);
            return true;
        }
        return false;
    }
    /**
     * Prevent from renaming two users on the same name
     * before that the rename is performed by the system
     *
     * @param String $val
     */
    public function getPendingUserRename($val)
    {
        $sm = $this->_getSystemEventManager();
        if (!$sm->isUserNameAvailable($val)) {
            $this->error = $GLOBALS['Language']->getText('rule_user_name', 'error_event_reserved', array($val));
            return false;
        }
        return true;
    }
    /**
     * Test if name is valid
     *
     * @param String $val Value to test
     *
     * @return bool
     */
    public function isValid($val)
    {
        return $this->isUnixValid($val)
            && !$this->isReservedName($val)
            && !$this->isCvsAccount($val)
            && !$this->isAlreadyUserName($val)
            && !$this->isAlreadyProjectName($val)
            && !$this->isSystemName($val)
            && $this->getPendingUserRename($val);
    }

    /**
     * @return bool
     */
    public function isUnixValid($val)
    {
        return $this->noSpaces($val)
            && $this->atLeastOneChar($val)
            && !$this->lessThanMin($val)
            && !$this->greaterThanMax($val)
            && !$this->containsIllegalChars($val);
    }

    /**
     * Error message
     *
     * @return string
     */
    public function getErrorMessage($key = '')
    {
        return $this->error;
    }

    /**
     * Returns error message when the username already exists
     *
     * Dedicate a method to be able to override it in descendent classes
     *
     * @param String $val Value to test
     *
     * @return bool
     */
    protected function _getErrorExists()
    {
        return $GLOBALS['Language']->getText('rule_user_name', 'error_exists');
    }

    /**
     * Returns error message when name contains a space
     *
     * Dedicate a method to be able to override it in descendent classes
     *
     * @param String $val Value to test
     *
     * @return bool
     */
    protected function _getErrorNoSpaces()
    {
        return $GLOBALS['Language']->getText('include_account', 'login_err');
    }

    /**
     * Wrapper
     *
     * @return ProjectManager
     */
    protected function _getProjectManager()
    {
        return ProjectManager::instance();
    }

    /**
     * Wrapper
     *
     * @return UserManager
     */
    protected function _getUserManager()
    {
        return UserManager::instance();
    }

    /**
     * Wrapper
     *
     * @return Backend
     */
    protected function _getBackend($type = '')
    {
        return Backend::instance($type);
    }

    /**
     * Wrapper
     *
     * @return SystemEventManager
     */
    protected function _getSystemEventManager()
    {
        return SystemEventManager::instance();
    }
}

/**
 * Check if a project name is valid
 *
 * This extends the user name validation
 */
class Rule_ProjectName extends Rule_UserName
{

    public const PATTERN_PROJECT_NAME    = '[a-zA-Z][A-Za-z0-9-_.]{2,254}';

    /**
     * Group name cannot contain underscore or dots for DNS reasons.
     *
     * @param String $val
     *
     * @return bool
     */
    public function isDNSCompliant($val)
    {
        if (strpos($val, '_') === false && strpos($val, '.') === false) {
            return true;
        }
        $this->error = $GLOBALS['Language']->getText('include_account', 'dns_error');
        return false;
    }

    /**
     * Verify group name availability in the FS
     *
     * @param String $val
     *
     * @return bool
     */
    public function isNameAvailable($val)
    {
        $backendSVN = $this->_getBackend('SVN');
        if (!$backendSVN->isNameAvailable($val)) {
            $this->error = $GLOBALS['Language']->getText('include_account', 'used_by_svn');
            return false;
        } else {
            $backendCVS = $this->_getBackend('CVS');
            if (!$backendCVS->isNameAvailable($val)) {
                $this->error = $GLOBALS['Language']->getText('include_account', 'used_by_cvs');
                return false;
            } else {
                $backendSystem = $this->_getBackend('System');
                if (!$backendSystem->isProjectNameAvailable($val)) {
                    $this->error = $GLOBALS['Language']->getText('include_account', 'used_by_sys');
                    return false;
                } else {
                    $result = true;
                    // Add Hook for plugins to check the name validity under plugins directories
                    $error = '';
                    $this->getEventManager()->processEvent(
                        'file_exists_in_data_dir',
                        array('new_name'  => $val,
                              'result'     => &$result,
                              'error' => &$error)
                    );
                    if ($result == false) {
                        $this->error = $error;
                        return false;
                    }
                }
            }
        }
        return true;
    }

    /**
     * Prevent from renaming two projects on the same name
     * before that the rename is performed by the system
     *
     * @param String $val
     */
    public function getPendingProjectRename($val)
    {
        $sm = $this->_getSystemEventManager();
        if (!$sm->isProjectNameAvailable($val)) {
            $this->error = $GLOBALS['Language']->getText('rule_user_name', 'error_event_reserved', array($val));
            return false;
        }
        return true;
    }


     /**
     * Wrapper for event manager
     *
     * @return EventManager
     */
    protected function getEventManager()
    {
        return EventManager::instance();
    }
    /**
     * Check validity
     *
     * @param String $val
     *
     * @return bool
     */
    public function isValid($val)
    {
        return $this->isDNSCompliant($val) && parent::isValid($val)  && $this->isNameAvailable($val)
                   && $this->getPendingProjectRename($val);
    }

    protected function _getErrorExists()
    {
        return $GLOBALS['Language']->getText('rule_group_name', 'error_exists');
    }

    protected function _getErrorNoSpaces()
    {
        return $GLOBALS['Language']->getText('include_account', 'project_spaces');
    }
}

/**
 * Check if a project full name is valid
 *
 * This extends the user name validation
 */
class Rule_ProjectFullName extends Rule_UserName
{

    /**
     * Check validity
     *
     * @param String $val
     *
     * @return bool
     */
    public function isValid($val)
    {
        $val = trim($val);
        return !$this->lessThanMin($val) && !$this->greaterThanMax($val, 40);
    }

    /**
     * Error message
     *
     * @return string
     */
    public function getErrorMessage($key = '')
    {
        return $this->error;
    }
}

/**
 * Check that file was correctly uploaded doesn't by pass Codendi limits.
 *
 * Tests mainly rely on PHP $_FILES error code but add a double check of file
 * size because MAX_FILE_SIZE (used by PHP to check allowed size) is submitted
 * by the client.
 *
 * By default the maxSize is defined by 'sys_max_size_upload'
 */
require_once __DIR__ . '/../../www/file/file_utils.php'; // Needed for 2 GB workaround
class Rule_File extends Rule
{
    public $maxSize;

    public function __construct()
    {
        $this->maxSize = ForgeConfig::get('sys_max_size_upload');
    }

    /**
     * Check file upload validity
     *
     * @param  string|array $file  One entry in $_FILES superarray (e.g. $_FILES['test'])
     * @return bool Is file upload valid or not.
     */
    public function isValid($file)
    {
        $ok = false;
        if (is_array($file)) {
            switch ($file['error']) {
                case UPLOAD_ERR_OK:
                    // all is OK
                    $ok = true;
                    break;
                case UPLOAD_ERR_INI_SIZE:
                case UPLOAD_ERR_FORM_SIZE:
                    $this->error = $GLOBALS['Language']->getText('rule_file', 'error_upload_size', $file['error']);
                    break;
                case UPLOAD_ERR_PARTIAL:
                    $this->error = $GLOBALS['Language']->getText('rule_file', 'error_upload_partial', $file['error']);
                    break;
                case UPLOAD_ERR_NO_FILE:
                    $this->error = $GLOBALS['Language']->getText('rule_file', 'error_upload_nofile', $file['error']);
                    break;
                default:
                    $this->error = $GLOBALS['Language']->getText('rule_file', 'error_upload_unknown', $file['error']);
            }
            if ($ok && $file['name'] == '') {
                $ok = false;
                $this->error = $GLOBALS['Language']->getText('rule_file', 'error_upload');
            }
            if ($ok) {
                // Re-check filesize (do not trust uploaded MAX_FILE_SIZE)
                if (file_utils_get_size($file['tmp_name']) > $this->maxSize) {
                    $ok = false;
                    $this->error = $GLOBALS['Language']->getText('rule_file', 'error_upload_size', 1);
                }
            }
        }
        return $ok;
    }
}

class Rule_FRSFileName extends Rule
{
    public function isValid($val)
    {
        if (preg_match("/[`!\"$%^,&*();=|{}<>?\/]/", $val)) {
            return false;
        }
        if (strpos($val, '@') === 0) { // Starts with at sign
            return false;
        }
        if (strpos($val, '~') === 0) { // Starts with at sign
            return false;
        }
        if (strstr($val, '..')) {
            return false;
        } else {
            return true;
        }
    }
}

class Rule_RealName extends Rule
{

    public function isValid($string)
    {
        if ($this->containsBackslashCharacter($string) || $this->containsNonPrintingCharacter($string)) {
            return false;
        }
        return true;
    }

    private function containsBackslashCharacter($string)
    {
        return strpos($string, "\\") !== false;
    }

    private function containsNonPrintingCharacter($string)
    {
        for ($i = 0; $i < strlen($string); $i++) {
            if ($this->isNonPrintingCharacter($string[$i])) {
                return true;
            }
        }
        return false;
    }

    private function isNonPrintingCharacter($char)
    {
        return hexdec(bin2hex($char)) < 32;
    }
}
