<?php
/**
 * Copyright (c) Enalean, 2013. All Rights Reserved.
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

class Openid_ConnexionManager {

    /** @var Openid_Driver_ConnexionDriver */
    private $connexion_driver;

    public function __construct(Openid_Driver_ConnexionDriver $connexion_driver) {
        $this->connexion_driver = $connexion_driver;
    }

    /**
     * Begin the authentication of a user through openid protocol
     *
     * @param openid_url String The openid_url given by the user
     * @param return_to_url String The url to reach after authentication successful finished
     * @throws OpenId_OpenIdException
     */
    public function startAuthentication($openid_url, $return_to_url) {
        $this->connexion_driver->connect($openid_url, $return_to_url);
    }

    /**
     * Ensure OpenId correspond to an exiting authentication request
     *
     * @param String $return_to_url
     * @return String
     * @throws OpenId_AuthenticationFailedException
     */
    public function finishAuthentication($return_to_url) {
        $consumer        = $this->connexion_driver->getConsumer();
        $return_to_url   = $this->connexion_driver->getReturnTo($return_to_url);
        $openid_response = $consumer->complete($return_to_url);
        if (! $this->isAuthenticationSuccess($openid_response)) {
            throw new OpenId_AuthenticationFailedException($openid_response->message);
        }

        return $openid_response->identity_url;
    }

    private function isAuthenticationSuccess(Auth_OpenID_ConsumerResponse $response) {
        return $response->status === Auth_OpenID_SUCCESS;
    }
}
