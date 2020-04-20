<?php
/**
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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

namespace Tuleap\OAuth2Server\E2E\RelyingPartyOIDC;

/**
 * @psalm-immutable
 */
final class OAuth2TestFlowSecretGenerator
{
    /**
     * @var string
     */
    private $state;
    /**
     * @var string
     */
    private $nonce;
    /**
     * @var string
     */
    private $pkce_challenge;

    public function __construct()
    {
        $this->state          = bin2hex(random_bytes(32));
        $this->nonce          = bin2hex(random_bytes(32));
        $this->pkce_challenge = bin2hex(random_bytes(32));
    }

    public function getState(): string
    {
        return $this->state;
    }

    public function getNonce(): string
    {
        return $this->nonce;
    }

    public function getPKCEChallenge(): string
    {
        return $this->pkce_challenge;
    }
}
