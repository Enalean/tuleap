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

namespace Tuleap\OAuth2Server\AuthorizationServer;

use Tuleap\Authentication\Scope\AuthenticationScope;
use Tuleap\OAuth2Server\User\AuthorizationComparator;
use Tuleap\OAuth2ServerCore\App\OAuth2App;
use Tuleap\OAuth2ServerCore\AuthorizationServer\ConsentChecker;
use Tuleap\OAuth2ServerCore\AuthorizationServer\PromptParameterValuesExtractor;
use Tuleap\OAuth2ServerCore\RefreshToken\OAuth2OfflineAccessScope;

final class OAuth2ConsentChecker implements ConsentChecker
{
    public function __construct(
        private AuthorizationComparator $authorization_comparator,
        private OAuth2OfflineAccessScope $offline_access_scope,
    ) {
    }

    /**
     * @param string[] $prompt_values
     * @param AuthenticationScope[] $scopes
     * @psalm-param non-empty-list<AuthenticationScope<\Tuleap\User\OAuth2\Scope\OAuth2ScopeIdentifier>> $scopes
     */
    #[\Override]
    public function isConsentRequired(array $prompt_values, \PFUser $user, OAuth2App $client_app, array $scopes): bool
    {
        $require_consent = in_array(PromptParameterValuesExtractor::PROMPT_CONSENT, $prompt_values, true);
        if ($require_consent || ! $this->authorization_comparator->areRequestedScopesAlreadyGranted($user, $client_app, $scopes)) {
            return true;
        }

        foreach ($scopes as $scope) {
            if ($this->offline_access_scope->covers($scope)) {
                return true;
            }
        }

        return false;
    }
}
