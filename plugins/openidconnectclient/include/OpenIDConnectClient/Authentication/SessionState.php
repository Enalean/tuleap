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

use Tuleap\Cryptography\ConcealedString;

class SessionState
{
    /**
     * @var string
     */
    private $secret_key;
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

    public function __construct(string $secret_key, ?string $return_to, string $nonce, ConcealedString $pkce_code_verifier)
    {
        $this->secret_key         = $secret_key;
        $this->return_to          = $return_to;
        $this->nonce              = $nonce;
        $this->pkce_code_verifier = $pkce_code_verifier;
    }

    public function getSecretKey(): string
    {
        return $this->secret_key;
    }

    public function getReturnTo(): ?string
    {
        return $this->return_to;
    }

    public function getNonce(): string
    {
        return $this->nonce;
    }

    public function getPKCECodeVerifier(): ConcealedString
    {
        return $this->pkce_code_verifier;
    }

    public function convertToMinimalRepresentation(): \stdClass
    {
        $representation = new \stdClass();
        $representation->secret_key         = $this->secret_key;
        $representation->return_to          = $this->return_to;
        $representation->nonce              = $this->nonce;
        $representation->pkce_code_verifier = $this->pkce_code_verifier->getString();
        return $representation;
    }

    public static function buildFromMinimalRepresentation(\stdClass $representation): self
    {
        if (! isset($representation->secret_key, $representation->return_to, $representation->nonce, $representation->pkce_code_verifier)) {
            throw new \InvalidArgumentException('Given $representation is incorrectly formatted');
        }
        $pkce_code_verifier = new ConcealedString($representation->pkce_code_verifier);
        \sodium_memzero($representation->pkce_code_verifier);
        return new self(
            $representation->secret_key,
            $representation->return_to,
            $representation->nonce,
            $pkce_code_verifier
        );
    }
}
