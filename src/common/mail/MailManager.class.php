<?php
/**
 * Copyright (c) Enalean, 2011. All Rights Reserved.
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

require_once 'common/mail/Codendi_Mail_Interface.class.php';
require_once 'common/mail/Mail.class.php';
require_once 'common/mail/Codendi_Mail.class.php';
require_once 'common/include/Tuleap_Template.class.php';

require_once 'common/user/UserManager.class.php';
require_once 'common/include/Config.class.php';

/**
 * Mail manager is the key interface to send emails in the platform
 *
 */
class MailManager {
    
    /**
     * Prepare the mail according to user preferences
     * 
     * @param PFUser $user The user to whom send the mail
     * 
     * @return Mail 
     */
    public function getMailForUser(PFUser $user) {
        $mail = $this->getMailByType($this->getMailPreferencesByUser($user));
        $mail->setToUser(array($user));
        return $mail;
    }
    
    /**
     * Return a mail object depending of the requested format
     * 
     * @param String $type Type of mail (text or html)
     * 
     * @return Mail
     */
    public function getMailByType($type = null) {
        $mail = new Codendi_Mail();
        if ($type == Codendi_Mail_Interface::FORMAT_TEXT) {
            $mail = new Mail();
        }
        $mail->setFrom($this->getConfig('sys_noreply'));
        return $mail;
    }
    
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
    public function getMailPreferencesByEmail($addresses) {
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
     * @param PFUser $user
     * 
     * @return String
     */
    public function getMailPreferencesByUser(PFUser $user) {
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
    public function getAllMailFormats() {
        return array(Codendi_Mail_Interface::FORMAT_TEXT, Codendi_Mail_Interface::FORMAT_HTML);
    }

    /**
     * Wrapper for configuration access
     * 
     * @param String $var
     * 
     * @return String 
     */
    protected function getConfig($var) {
        return Config::get($var);
    }
    
    /**
     * Wrapper for UserManager
     * 
     * @return UserManager 
     */
    protected function getUserManager() {
        return UserManager::instance();
    }
}

?>
