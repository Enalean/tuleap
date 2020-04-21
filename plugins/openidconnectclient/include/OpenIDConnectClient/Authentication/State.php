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

declare(strict_types=1);

namespace Tuleap\OpenIDConnectClient\Authentication;

use Lcobucci\JWT\Builder;
use Lcobucci\JWT\Parser;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\Signer\Key;
use Tuleap\Cryptography\ConcealedString;

class State
{
    /**
     * @var int
     */
    private $provider_id;
    /**
     * @var string|null
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
    /**
     * @var ConcealedString
     */
    private $pkce_code_verifier;

    public function __construct(int $provider_id, ?string $return_to, string $secret_key, string $nonce, ConcealedString $pkce_code_verifier)
    {
        $this->provider_id        = $provider_id;
        $this->return_to          = $return_to;
        $this->secret_key         = $secret_key;
        $this->nonce              = $nonce;
        $this->pkce_code_verifier = $pkce_code_verifier;
    }

    public static function createFromSignature(string $signed_state, ?string $return_to, string $secret_key, string $nonce, ConcealedString $pkce_code_verifier): self
    {
        $token = (new Parser())->parse($signed_state);
        if (! $token->verify(new Sha256(), $secret_key)) {
            throw new \RuntimeException('Signed state cannot be verifier');
        }
        $provider_id = (int) $token->getClaim('provider_id');
        return new self($provider_id, $return_to, $secret_key, $nonce, $pkce_code_verifier);
    }

    public function getSignedState(): string
    {
        return (string) (new Builder())->withClaim('provider_id', $this->provider_id)->getToken(new Sha256(), new Key($this->secret_key));
    }

    public function getProviderId(): int
    {
        return $this->provider_id;
    }

    public function getReturnTo(): ?string
    {
        return $this->return_to;
    }

    public function getSecretKey(): string
    {
        return $this->secret_key;
    }

    public function getNonce(): string
    {
        return $this->nonce;
    }

    public function getPKCECodeVerifier(): ConcealedString
    {
        return $this->pkce_code_verifier;
    }
}
