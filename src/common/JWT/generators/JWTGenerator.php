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
use UGroupLiteralizer;

class JWTGenerator
{

    /** @var UserManager */
    private $user_manager;

    /** @var UGroupLiteralizer */
    private $ugroup_literalizer;

    /** @var string */
    private $private_key;

    public function __construct($private_key, $user_manager, $ugroup_literalizer)
    {
        $this->private_key        = $private_key;
        $this->user_manager       = $user_manager;
        $this->ugroup_literalizer = $ugroup_literalizer;
    }

    /**
     * Generate a json web token
     * for the current user
     *
     * @return string
     */
    public function getToken()
    {
        $current_user = $this->user_manager->getCurrentUser();
        $data = array(
            'user_id'     => intval($current_user->getId()),
            'user_rights' => $this->ugroup_literalizer->getUserGroupsForUserWithArobase($current_user)
        );

        $token = array(
            'exp' => $this->getExpireDate(),
            'data'=> $data
        );

        $encoded = JWT::encode($token, $this->private_key, 'HS512');
        return $encoded;
    }

    private function getExpireDate()
    {
        $issuedAt  = new \DateTime();
        $notBefore = $issuedAt;
        return $notBefore->modify('+30 minutes')->getTimestamp();
    }
}
