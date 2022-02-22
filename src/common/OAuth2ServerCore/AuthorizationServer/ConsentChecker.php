<?php
/**
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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

namespace Tuleap\OAuth2ServerCore\AuthorizationServer;

use Tuleap\Authentication\Scope\AuthenticationScope;
use Tuleap\OAuth2ServerCore\App\OAuth2App;

interface ConsentChecker
{
    /**
     * @param string[] $prompt_values
     * @param AuthenticationScope[] $scopes
     * @psalm-param non-empty-list<AuthenticationScope<\Tuleap\User\OAuth2\Scope\OAuth2ScopeIdentifier>> $scopes
     */
    public function isConsentRequired(array $prompt_values, \PFUser $user, OAuth2App $client_app, array $scopes): bool;
}
