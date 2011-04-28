<?php
/**
 * Copyright (c) STMicroelectronics, 2010. All Rights Reserved.
 *
 * This file is a part of Codendi.
 *
 * Codendi is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Codendi is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Codendi. If not, see <http://www.gnu.org/licenses/>.
 */

require_once('common/event/EventManager.class.php');
require_once('common/include/URLVerification.class.php');

/**
 * Manage the use of URLVerification
 */
class URLVerificationFactory {

    /**
     * Returns an instance of EventManager
     *
     * @return EventManager
     */
    public function getEventManager() {
        return EventManager::instance();
    }

    /**
     * If a plugin answers to this hook, the urlVerification corresponding to
     * this plugin will be in charge of the check of url validity, else it is
     * delegated to the standard urlVerification class
     *
     * We assume that the first plugin (using plugin's priorities) which answers,
     * will determinate the right url
     *
     * @param Array $server
     *
     * @return URLVerification
     */
    public function getURLVerification($server) {
        $em = $this->getEventManager();
        $em->processEvent('url_verification_instance', array('server_param' => $server,
                                                    'url_verification' =>&$urlVerification));
        if (isset($urlVerification)) {
            return ($urlVerification);
        } else {
            return (new URLVerification());
        }
    }
}
?>