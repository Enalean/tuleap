<?php
/**
 * Copyright (c) Enalean, 2011-2018. All Rights Reserved.
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
 * Mail manager is the key interface to send emails in the platform
 *
 */
class MailManager
{
    /**
     * Return users corresponding to email addresses mapped according to their
     * preferences.
     *
     * @deprecated
     *
     * @param Array $addresses A set of addresses
     *
     * @return Array of Array of User
     */
    public function getMailPreferencesByEmail($addresses)
    {
        $default = Codendi_Mail_Interface::FORMAT_HTML;
        $res     = array('html' => array(), 'text' => array());
        $um      = $this->getUserManager();
        foreach ($addresses as $address) {
            $users = $um->getAllUsersByEmail($address);
            $pref  = $default;
            if (count($users) > 0) {
                foreach ($users as $user) {
                    $pref_user   = $this->getMailPreferencesByUser($user);
                    $user_status = $user->getStatus();
                    if ($pref_user != $default && ($user_status == 'A' || $user_status == 'R')) {
                        $pref = $pref_user;
                        break;
                    }
                }
            } else {
                $user = new PFUser(array('user_id' => 0, 'language_id' => $this->getConfig('sys_lang')));
                $user->setEmail($address);
            }
            $res[$pref][] = $user;
        }
        return $res;
    }

    /**
     * Returns whether the user wants an HTML or a Text notification
     *
     *
     * @return String
     */
    public function getMailPreferencesByUser(PFUser $user)
    {
        if ($user->getPreference(Codendi_Mail_Interface::PREF_FORMAT) == Codendi_Mail_Interface::FORMAT_TEXT) {
            return Codendi_Mail_Interface::FORMAT_TEXT;
        }
        return Codendi_Mail_Interface::FORMAT_HTML;
    }

    /**
     * Returns all possible mail formats
     *
     * @return Array
     */
    public function getAllMailFormats()
    {
        return array(Codendi_Mail_Interface::FORMAT_TEXT, Codendi_Mail_Interface::FORMAT_HTML);
    }

    /**
     * Wrapper for configuration access
     *
     * @param String $var
     *
     * @return String
     */
    protected function getConfig($var)
    {
        return ForgeConfig::get($var);
    }

    /**
     * Wrapper for UserManager
     *
     * @return UserManager
     */
    protected function getUserManager()
    {
        return UserManager::instance();
    }
}
