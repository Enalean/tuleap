<?php
/**
 * Copyright (c) Enalean, 2016-2018. All Rights Reserved.
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

namespace Tuleap\OpenIDConnectClient\Authentication;

use Firebase\JWT\JWT;

class State
{
    public const SIGNATURE_ALGORITHM = 'HS256';

    /**
     * @var int
     */
    private $provider_id;
    /**
     * @var string
     */
    private $return_to;
    /**
     * @var string
     */
    private $secret_key;
    /**
     * @var string
     */
    private $nonce;

    public function __construct($provider_id, $return_to, $secret_key, $nonce)
    {
        $this->provider_id = $provider_id;
        $this->return_to   = $return_to;
        $this->secret_key  = $secret_key;
        $this->nonce       = $nonce;
    }

    /**
     * @return State
     */
    public static function createFromSignature($signed_state, $return_to, $secret_key, $nonce)
    {
        $provider_id = JWT::decode($signed_state, $secret_key, array(self::SIGNATURE_ALGORITHM));
        return new State($provider_id, $return_to, $secret_key, $nonce);
    }

    /**
     * @return string
     */
    public function getSignedState()
    {
        return JWT::encode($this->provider_id, $this->secret_key, self::SIGNATURE_ALGORITHM);
    }

    public function getProviderId()
    {
        return $this->provider_id;
    }

    public function getReturnTo()
    {
        return $this->return_to;
    }

    public function getSecretKey()
    {
        return $this->secret_key;
    }

    /**
     * @return string
     */
    public function getNonce()
    {
        return $this->nonce;
    }
}
