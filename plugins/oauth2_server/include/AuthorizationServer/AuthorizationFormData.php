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

namespace Tuleap\OAuth2Server\AuthorizationServer;

use Tuleap\Authentication\Scope\AuthenticationScope;
use Tuleap\OAuth2Server\App\OAuth2App;

final class AuthorizationFormData
{
    /**
     * @var OAuth2App
     * @psalm-readonly
     */
    private $app;
    /**
     * @var \CSRFSynchronizerToken
     * @psalm-readonly
     */
    private $csrf_token;
    /**
     * @var string | null
     * @psalm-readonly
     */
    private $state;
    /**
     * @var string
     * @psalm-readonly
     */
    private $redirect_uri;
    /**
     * @var string|null
     * @psalm-readonly
     */
    private $pkce_code_challenge;
    /**
     * @var string|null
     * @psalm-readonly
     */
    private $oidc_nonce;
    /**
     * @var AuthenticationScope[]
     * @psalm-readonly
     */
    private $scopes;

    public function __construct(
        OAuth2App $app,
        \CSRFSynchronizerToken $csrf_token,
        string $redirect_uri,
        ?string $state,
        ?string $pkce_code_challenge,
        ?string $oidc_nonce,
        AuthenticationScope ...$scopes
    ) {
        $this->app                 = $app;
        $this->csrf_token          = $csrf_token;
        $this->redirect_uri        = $redirect_uri;
        $this->state               = $state;
        $this->pkce_code_challenge = $pkce_code_challenge;
        $this->oidc_nonce          = $oidc_nonce;
        $this->scopes              = $scopes;
    }

    /**
     * @psalm-mutation-free
     */
    public function getApp(): OAuth2App
    {
        return $this->app;
    }

    /**
     * @psalm-mutation-free
     */
    public function getCSRFToken(): \CSRFSynchronizerToken
    {
        return $this->csrf_token;
    }

    /**
     * @psalm-mutation-free
     */
    public function getState(): ?string
    {
        return $this->state;
    }

    /**
     * @psalm-mutation-free
     */
    public function getRedirectUri(): string
    {
        return $this->redirect_uri;
    }

    /**
     * @psalm-mutation-free
     */
    public function getPKCECodeChallenge(): ?string
    {
        return $this->pkce_code_challenge;
    }

    /**
     * @psalm-mutation-free
     */
    public function getOIDCNonce(): ?string
    {
        return $this->oidc_nonce;
    }

    /**
     * @return AuthenticationScope[]
     * @psalm-mutation-free
     */
    public function getScopes(): array
    {
        return $this->scopes;
    }
}
