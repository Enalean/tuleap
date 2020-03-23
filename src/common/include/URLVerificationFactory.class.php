<?php
/**
 * Copyright (c) Enalean SAS, 2018. All Rights Reserved.
 * Copyright (c) STMicroelectronics, 2010. All Rights Reserved.
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
 * Manage the use of URLVerification
 */
class URLVerificationFactory
{

    /**
     * @var EventManager
     */
    private $event_manager;

    public function __construct(EventManager $event_manager)
    {
        $this->event_manager = $event_manager;
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
    public function getURLVerification($server)
    {
        $urlVerification = null;
        $this->event_manager->processEvent('url_verification_instance', array('server_param' => $server,
                                                    'url_verification' => &$urlVerification));
        if ($urlVerification !== null) {
            return $urlVerification;
        } else {
            return (new URLVerification());
        }
    }
}
