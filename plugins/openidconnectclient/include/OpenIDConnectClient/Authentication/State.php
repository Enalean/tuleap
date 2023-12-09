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

use Lcobucci\JWT\Encoding\ChainedFormatter;
use Lcobucci\JWT\Encoding\JoseEncoder;
use Lcobucci\JWT\Token\Parser;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\Signer\Key;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\UnencryptedToken;
use Lcobucci\JWT\Validation\Constraint\SignedWith;
use Lcobucci\JWT\Validation\Validator;
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
    private $nonce;
    /**
     * @var ConcealedString
     */
    private $pkce_code_verifier;

    /**
     * @psalm-param non-empty-string $secret_key
     */
    public function __construct(int $provider_id, ?string $return_to, private string $secret_key, string $nonce, ConcealedString $pkce_code_verifier)
    {
        $this->provider_id        = $provider_id;
        $this->return_to          = $return_to;
        $this->nonce              = $nonce;
        $this->pkce_code_verifier = $pkce_code_verifier;
    }

    /**
     * @psalm-param non-empty-string $signed_state
     * @psalm-param non-empty-string $secret_key
     */
    public static function createFromSignature(string $signed_state, ?string $return_to, string $secret_key, string $nonce, ConcealedString $pkce_code_verifier): self
    {
        $token = (new Parser(new JoseEncoder()))->parse($signed_state);
        assert($token instanceof UnencryptedToken);
        if (! (new Validator())->validate($token, new SignedWith(new Sha256(), Key\InMemory::plainText($secret_key)))) {
            throw new \RuntimeException('Signed state cannot be verified');
        }
        $provider_id = (int) $token->claims()->get('provider_id');
        return new self($provider_id, $return_to, $secret_key, $nonce, $pkce_code_verifier);
    }

    /**
     * @psalm-return non-empty-string
     */
    public function getSignedState(): string
    {
        return (new \Lcobucci\JWT\Token\Builder(new JoseEncoder(), ChainedFormatter::default()))->withClaim('provider_id', $this->provider_id)->getToken(new Sha256(), InMemory::plaintext($this->secret_key))->toString();
    }

    public function getProviderId(): int
    {
        return $this->provider_id;
    }

    public function getReturnTo(): ?string
    {
        return $this->return_to;
    }

    /**
     * @psalm-return non-empty-string
     */
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
