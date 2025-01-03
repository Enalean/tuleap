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

use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Hmac\Sha512;
use Lcobucci\JWT\Signer\Key;
use Tuleap\Cryptography\ConcealedString;
use Tuleap\Project\UGroupLiteralizer;
use Tuleap\REST\Header;
use Tuleap\JWT\REST\JWTRepresentation;
use Tuleap\JWT\Generators\JWTGenerator;
use UserManager;

class JWTResource
{
    private const PRIVATE_KEY_FILE   = '/var/lib/tuleap/tuleap-realtime-key';
    private const PRIVATE_KEY_SUFFIX = 'PRIVATE_KEY=';

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
            Configuration::forSymmetricSigner(
                new Sha512(),
                Key\InMemory::plainText($this->getPrivateKey()->getString())
            ),
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

    private function getPrivateKey(): ConcealedString
    {
        $private_key_file_content = \Psl\File\read(self::PRIVATE_KEY_FILE);
        $private_key              = \Psl\Str\after($private_key_file_content, self::PRIVATE_KEY_SUFFIX);
        sodium_memzero($private_key_file_content);
        if ($private_key === null) {
            throw new \RuntimeException(self::PRIVATE_KEY_FILE . ' content does not have the expected format');
        }
        return new ConcealedString($private_key);
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
