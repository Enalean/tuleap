<?php
/**
 * Copyright (c) Enalean, 2015-Present. All Rights Reserved.
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

namespace Tuleap\JWT\REST\v1;

use Lcobucci\JWT\Builder;
use Lcobucci\JWT\Signer\Hmac\Sha512;
use Lcobucci\JWT\Signer\Key;
use Tuleap\REST\Header;
use Tuleap\JWT\REST\JWTRepresentation;
use Tuleap\JWT\Generators\JWTGenerator;
use UserManager;
use UGroupLiteralizer;
use ForgeConfig;

class JWTResource
{
    /**
     * To have a json web token
     *
     * @url GET
     *
     * @return Tuleap\JWT\REST\JWTRepresentation
     */
    public function get()
    {
        $jwt_generator = new JWTGenerator(
            new Key(ForgeConfig::get('nodejs_server_jwt_private_key')),
            new Builder(),
            new Sha512(),
            UserManager::instance(),
            new UGroupLiteralizer()
        );
        $encoded       = $jwt_generator->getToken();
        $token         = new JWTRepresentation();
        $token->build(
            $encoded
        );
        $this->sendAllowHeader();
        return $token;
    }

    /**
     * @url OPTIONS
     *
     */
    public function options()
    {
        $this->sendAllowHeader();
    }

    private function sendAllowHeader()
    {
        Header::allowOptionsGet();
    }
}
