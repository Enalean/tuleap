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
 * Check if a project name is valid
 *
 * This extends the user name validation
 */
class Rule_ProjectName extends \Rule_UserName // phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotCamelCaps
{
    public const PATTERN_PROJECT_NAME = '[a-zA-Z][A-Za-z0-9-_.]{2,254}';
    /**
     * Group name cannot contain underscore or dots for DNS reasons.
     *
     * @param String $val
     *
     * @return bool
     */
    public function isDNSCompliant($val)
    {
        if (\strpos($val, '_') === \false && \strpos($val, '.') === \false) {
            return \true;
        }
        $this->error = $GLOBALS['Language']->getText('include_account', 'dns_error');
        return \false;
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
        if (! $backendSVN->isNameAvailable($val)) {
            $this->error = $GLOBALS['Language']->getText('include_account', 'used_by_svn');
            return \false;
        } else {
            $backendSystem = $this->_getBackend('System');
            if (! $backendSystem->isProjectNameAvailable($val)) {
                $this->error = $GLOBALS['Language']->getText('include_account', 'used_by_sys');
                return \false;
            } else {
                $result = \true;
                // Add Hook for plugins to check the name validity under plugins directories
                $error = '';
                $this->getEventManager()->processEvent('file_exists_in_data_dir', ['new_name' => $val, 'result' => &$result, 'error' => &$error]);
                if ($result == \false) {
                    $this->error = $error;
                    return \false;
                }
            }
        }
        return \true;
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
        if (! $sm->isProjectNameAvailable($val)) {
            $this->error = $GLOBALS['Language']->getText('rule_user_name', 'error_event_reserved', [$val]);
            return \false;
        }
        return \true;
    }

    /**
     * Wrapper for event manager
     *
     * @return EventManager
     */
    protected function getEventManager()
    {
        return \EventManager::instance();
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
        return $this->isStartingWithAlphanumericCharacter($val) && $this->isDNSCompliant($val) && parent::isValid($val) && $this->isNameAvailable($val) && $this->getPendingProjectRename($val);
    }

    protected function _getErrorExists() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        return $GLOBALS['Language']->getText('rule_group_name', 'error_exists');
    }

    protected function _getErrorNoSpaces() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        return $GLOBALS['Language']->getText('include_account', 'project_spaces');
    }

    private function isStartingWithAlphanumericCharacter(string $val): bool
    {
        $is_starting_with_alphanumeric_character = ctype_alnum($val[0]);
        if (! $is_starting_with_alphanumeric_character) {
            $this->error = _("Short name must start with an alphanumeric character.");
        }

        return $is_starting_with_alphanumeric_character;
    }
}
