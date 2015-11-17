<?php
/**
 * Copyright (c) Enalean, 2015. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */
namespace Tuleap\JWT\Generators;

use UserManager;
use Firebase\JWT\JWT;
use ForgeConfig;

class JWTGenerator {

    /** @var UserManager */
    private $user_manager;

    public function __construct($user_manager) {
        $this->user_manager = $user_manager;
    }

    /**
     * Generate a json web token
     *
     * Generate a json web token for the current user
     *
     * @return string
     */
    public function getToken() {
        $current_user = $this->user_manager->getCurrentUser();
        $data = array(
            'user_id' => intval($current_user->getId())
        );

        $issuedAt  = new \DateTime();
        $notBefore = $issuedAt;
        $expire = $notBefore->modify('+30 minutes')->getTimestamp();
        $token = array(
            'exp' => $expire,
            'data' => $data
        );

        $jwt = JWT::encode($token, ForgeConfig::get('nodejs_server_jwt_private_key'), 'HS512');

        return $jwt;
    }
}
