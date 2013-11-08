<?php
/**
 * Copyright (c) Enalean, 2013. All rights reserved
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

require_once dirname(__FILE__).'/../autoload.php';
require_once 'common/autoload.php';
require_once dirname(__FILE__).'/../../../vendor/autoload.php';

use Guzzle\Http\Client;
use Guzzle\Http\Exception\BadResponseException;

class RestBase extends PHPUnit_Framework_TestCase {

    const API_BASE = 'api/v1';

    protected $base_url  = 'http://localhost:8089';

    private $user_manager;

    /**
     * @var Client
     */
    protected $client;

    public function __construct() {
        parent::__construct();

        $this->setDbConnection();
        $this->user_manager = UserManager::instance();
    }

    public function setUp() {
        parent::setUp();

        $this->client = new Client($this->base_url);
    }

    protected function getResponseByName($name, $request) {
        return $this->getResponseByToken(
            $this->generateToken(
                $this->getUserByName($name)
            ),
            $request
        );
    }

    protected function getResponseByToken(Rest_Token $token, $request) {
        $request->setHeader('X-Auth-Token', $token->getTokenValue())
                ->setHeader('Content-Type', 'application/json')
                ->setHeader('X-Auth-UserId', $token->getUserId());
        return $request->send();
    }

    protected function getTokenForUserName($user_name) {
        return $this->generateToken(
            $this->getUserByName($user_name)
        );
    }

    /**
     * @param PFUser $user
     * @return Rest_Token
     */
    protected function generateToken($user) {
        $dao             = new Rest_TokenDao();
        $generated_hash = 'gbgfb5gfb6bfdb6db5dbdbd6b5rd'.  rand(0, 152125415);

        $dao->addTokenForUserId($user->getId(), $generated_hash, mktime());

        return new Rest_Token(
            $user->getId(),
            $generated_hash
        );
    }

    /**
     * @param string $user_name
     * @return PFUser
     */
    protected function getUserByName($user_name) {
        return $this->user_manager->getUserByUserName($user_name);
    }

    private function setDbConnection() {
        $this->db = new DBTestAccess();
        db_connect();
    }
}
?>