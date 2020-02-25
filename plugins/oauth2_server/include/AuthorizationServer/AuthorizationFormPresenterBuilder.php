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

class AuthorizationFormPresenterBuilder
{
    public function build(OAuth2App $app, AuthenticationScope ...$scopes): AuthorizationFormPresenter
    {
        $scope_presenters = [];
        foreach ($scopes as $scope) {
            $scope_presenters[] = new OAuth2ScopeDefinitionPresenter($scope->getDefinition());
        }
        return new AuthorizationFormPresenter($app, ...$scope_presenters);
    }
}
