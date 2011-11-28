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

require_once 'common/user/User.class.php';
require_once 'common/include/Config.class.php';

/**
 * Mail manager is the key interface to send emails in the platform
 *
 */
class MailManager {
    
    /**
     * Prepare the mail according to user preferences
     * 
     * @param User $user The user to whom send the mail
     * 
     * @return Mail 
     */
    public function getMailForUser(User $user) {
        $mail = $this->getMailByType($user->getPreference('user_tracker_mailformat'));
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
        if ($type == 'text') {
            $mail = new Mail();
        }
        $mail->setFrom($this->getConfig('sys_noreply'));
        return $mail;
    }
    
    protected function getConfig($var) {
        return Config::get($var);
    }
}

?>
