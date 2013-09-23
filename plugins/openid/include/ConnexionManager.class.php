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

require_once 'account.php';

class Openid_ConnexionManager {

    /** @var Openid_Dao */
    private $dao;

    /** @var Openid_Driver_ConnexionDriver */
    private $connexion_driver;

    public function __construct(Openid_Dao $dao, Openid_Driver_ConnexionDriver $connexion_driver) {
        $this->dao              = $dao;
        $this->connexion_driver = $connexion_driver;
    }

    /**
     * Begin the authentication of a user through openid protocol
     *
     * @param openid_url String The openid_url given by the user
     * @param return_to_url String The url to reach after authentication successful finished
     */
    public function startAuthentication($openid_url, $return_to_url) {
        $this->connexion_driver->connect($openid_url, $return_to_url);
    }

    public function finishAuthentication($return_to_url) {
        $consumer        = $this->connexion_driver->getConsumer();
        $return_to_url   = $this->connexion_driver->getReturnTo($return_to_url);
        $openid_response = $consumer->complete($return_to_url);
        if (! $this->isAuthenticationSuccess($openid_response)) {
            throw new OpenId_AuthenticationFailedException($openid_response->message);
        }

        return $openid_response->identity_url;
    }

    /**
     *
     * @param String $identity_url
     * @return PFUser
     */
    public function authenticateCorrespondingUser($identity_url) {
        $user_manager = UserManager::instance();
        $user_ids     = $this->dao->searchUsersForConnexionString($identity_url);
        $users        = $user_ids->instanciateWith(array($this, "instanciateFromRow"));

        foreach ($users as $user) {
            $user_manager->openSessionForUser($user);
            return $user;
        }
        throw new OpenId_UserNotFoundException($GLOBALS['Language']->getText('plugin_openid', 'error_no_matching_user', Config::get('sys_name')));
    }

    public function instanciateFromRow(array $row) {
        $user_manager = UserManager::instance();
        return $user_manager->getUserById($row['user_id']);
    }

    private function isAuthenticationSuccess(Auth_OpenID_ConsumerResponse $response) {
        return $response->status === Auth_OpenID_SUCCESS;
    }
}
