<?php
/**
 * Copyright (c) Enalean, 2016-Present. All Rights Reserved.
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

use Lcobucci\JWT\Builder;
use Lcobucci\JWT\Parser;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\Signer\Key;

class State
{
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

    public static function createFromSignature($signed_state, $return_to, $secret_key, $nonce): self
    {
        $token = (new Parser())->parse($signed_state);
        if (! $token->verify(new Sha256(), $secret_key)) {
            throw new \RuntimeException('Signed state cannot be verifier');
        }
        $provider_id = (int) $token->getClaim('provider_id');
        return new self($provider_id, $return_to, $secret_key, $nonce);
    }

    public function getSignedState(): string
    {
        return (string) (new Builder())->withClaim('provider_id', $this->provider_id)->getToken(new Sha256(), new Key($this->secret_key));
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
