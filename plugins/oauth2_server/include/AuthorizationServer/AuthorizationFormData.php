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

/**
 * @psalm-immutable
 */
final class AuthorizationFormData
{
    /**
     * @var OAuth2App
     */
    private $app;
    /**
     * @var \CSRFSynchronizerToken
     */
    private $csrf_token;
    /**
     * @var string | null
     */
    private $state;
    /**
     * @var string
     */
    private $redirect_uri;
    /**
     * @var AuthenticationScope[]
     */
    private $scopes;

    public function __construct(
        OAuth2App $app,
        \CSRFSynchronizerToken $csrf_token,
        string $redirect_uri,
        ?string $state,
        AuthenticationScope ...$scopes
    ) {
        $this->app          = $app;
        $this->csrf_token   = $csrf_token;
        $this->redirect_uri = $redirect_uri;
        $this->state        = $state;
        $this->scopes       = $scopes;
    }

    public function getApp(): OAuth2App
    {
        return $this->app;
    }

    public function getCSRFToken(): \CSRFSynchronizerToken
    {
        return $this->csrf_token;
    }

    public function getState(): ?string
    {
        return $this->state;
    }

    public function getRedirectUri(): string
    {
        return $this->redirect_uri;
    }

    /**
     * @return AuthenticationScope[]
     */
    public function getScopes(): array
    {
        return $this->scopes;
    }
}
