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

/**
 * Check if value match Codendi user names format.
 *
 * This rule doesn't check that user actually exists.
 */
class Rule_UserName extends \Rule // phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotPascalCase
{
    public const RESERVED_PREFIX = 'forge__';

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
        if ($um->getUserByUserName($val) !== \null) {
            $this->error = $this->_getErrorExists();
            return \true;
        }
        return \false;
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
        if ($pm->getProjectByUnixName($val) !== \null) {
            $this->error = $this->_getErrorExists();
            return \true;
        }
        return \false;
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
        if (\strrpos($val, ' ') !== \false) {
            $this->error = $this->_getErrorNoSpaces();
            return \false;
        }
        return \true;
    }

    public function atLeastOneChar(string $val): bool
    {
        if (is_numeric($val)) {
            $this->error = _('The name must start with a letter.');
            return \false;
        }
        return \true;
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
        if (\strspn($val, 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789-_.') != \strlen($val)) {
            $this->error = _('Illegal character in name.');
            return \true;
        }
        return \false;
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
        $is_reserved_name   = \preg_match('/^(' . '(www[0-9]?)|(cvs[0-9]?)|(shell[0-9]?)|(ftp[0-9]?)|(irc[0-9]?)|(news[0-9]?)' . '|(mail[0-9]?)|(ns[0-9]?)|(download[0-9]?)|(pub)|(users)|(compile)|(lists)' . '|(slayer)|(orbital)|(tokyojoe)|(webdev)|(projects)|(cvs)|(monitor)|(mirrors?)' . '|(root)|(bin)|(daemon)|(adm)|(lp)|(sync)|(shutdown)|(halt)|(mail)' . '|(uucp)|(operator)|(games)|(mysql)|(httpd)|(nobody)|(dummy)' . '|(munin)|(mailman)|(ftpadmin)|(codendiadm)|(imadmin-bot)|(apache)|(nscd)' . '|(git)|(gitolite)' . ')$/i', $val);
        $is_reserved_prefix = $this->isReservedPrefix($val);
        if ($is_reserved_name || $is_reserved_prefix) {
            $this->error = _('Name is reserved.');
            return \true;
        }
        return \false;
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
        if (\strpos($val, self::RESERVED_PREFIX) === 0) {
            return \true;
        }
        return \false;
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
        if (\strlen($val) < 3) {
            $this->error = _('Name is too short. It must be at least 3 characters.');
            return \true;
        }
        return \false;
    }

    /**
     * Test maximal length of name
     *
     * @param String  $val Value to test
     * @param int $max maximal length (default = 30)
     *
     * @return bool
     */
    public function greaterThanMax(string $val, int $max = 30)
    {
        if (\mb_strlen($val) > $max) {
            $this->error = sprintf(
                gettext('Name is too long. It must be less than %s characters.'),
                $max
            );
            return \true;
        }
        return \false;
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
        if (! $sm->isUserNameAvailable($val)) {
            $this->error = sprintf(_('%s is already reserved for another rename operation. Please check System Event Monitor'), $val);
            return \false;
        }
        return \true;
    }

    /**
     * Test if name is valid
     *
     * @param String $val Value to test
     *
     * @return bool
     */
    #[\Override]
    public function isValid($val)
    {
        return $this->isUnixValid($val) && ! $this->isReservedName($val) && ! $this->isAlreadyUserName($val) && ! $this->isAlreadyProjectName($val) && $this->getPendingUserRename($val);
    }

    public function isUnixValid(string $val): bool
    {
        return $this->noSpaces($val) && ! $this->containsIllegalChars($val) && ! $this->lessThanMin($val) && ! $this->greaterThanMax($val);
    }

    /**
     * Returns error message when the username already exists
     *
     * Dedicate a method to be able to override it in descendent classes
     */
    protected function _getErrorExists(): string // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        return _('Login name already exists.');
    }

    /**
     * Returns error message when name contains a space
     *
     * Dedicate a method to be able to override it in descendent classes
     */
    protected function _getErrorNoSpaces(): string // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        return _('There cannot be any spaces in the login name.');
    }

    /**
     * Wrapper
     *
     * @return ProjectManager
     */
    protected function _getProjectManager() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        return \ProjectManager::instance();
    }

    /**
     * Wrapper
     *
     * @return UserManager
     */
    protected function _getUserManager() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        return \UserManager::instance();
    }

    /**
     * Wrapper
     *
     * @return SystemEventManager
     */
    protected function _getSystemEventManager() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        return \SystemEventManager::instance();
    }
}
