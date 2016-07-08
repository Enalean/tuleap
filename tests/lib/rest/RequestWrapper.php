<?php
/**
 * Copyright (c) Enalean, 2014. All rights reserved
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/
 */

namespace Test\Rest;

use \UserManager;
use \Rest_TokenDao;
use \Rest_Token;
use \DBTestAccess;
use \PFUser;

class RequestWrapper {

    private $user_manager;
    private $db;

    public function __construct() {
        $this->setDbConnection();
        $this->user_manager = UserManager::instance();
    }

    public function getResponseWithoutAuth($request) {
        return $request->send();
    }

    public function getResponseByName($name, $request) {
        return $this->getResponseByToken(
            $this->generateToken(
                $this->getUserByName($name)
            ),
            $request
        );
    }

    public function getResponseByBasicAuth($username, $password, $request) {
        $request->setAuth($username, $password);
        return $request->send();
    }

    public function getResponseByToken(Rest_Token $token, $request) {
        $request->setHeader('X-Auth-Token', $token->getTokenValue())
                ->setHeader('X-Auth-UserId', $token->getUserId());
        return $request->send();
    }

    public function getTokenForUserName($user_name) {
        return $this->generateToken(
            $this->getUserByName($user_name)
        );
    }

    /**
     * @param PFUser $user
     * @return Rest_Token
     */
    private function generateToken(PFUser $user) {
        $dao             = new Rest_TokenDao();
        $generated_hash = 'hash_for_rest_tests';

        $dao->addTokenForUserId($user->getId(), $generated_hash, time());

        return new Rest_Token(
            $user->getId(),
            $generated_hash
        );
    }

    /**
     * @param string $user_name
     * @return PFUser
     */
    private function getUserByName($user_name) {
        return $this->user_manager->getUserByUserName($user_name);
    }

    private function setDbConnection() {
        $this->db = new DBTestAccess();
        db_connect();
    }
}
