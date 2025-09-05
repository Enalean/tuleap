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

namespace Tuleap\MediawikiStandalone\OAuth2;

use Tuleap\Authentication\Scope\AuthenticationScope;
use Tuleap\OAuth2ServerCore\App\OAuth2App;
use Tuleap\OAuth2ServerCore\AuthorizationServer\ConsentChecker;
use Tuleap\OAuth2ServerCore\AuthorizationServer\PromptParameterValuesExtractor;

final class MediawikiStandaloneOAuth2ConsentChecker implements ConsentChecker
{
    /**
     * @param AuthenticationScope[] $allowed_scopes
     * @psalm-param non-empty-list<AuthenticationScope<\Tuleap\User\OAuth2\Scope\OAuth2ScopeIdentifier>> $allowed_scopes
     */
    public function __construct(private array $allowed_scopes)
    {
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
        if ($require_consent) {
            return true;
        }

        foreach ($scopes as $scope) {
            if (! $this->doesSomeAllowedScopeCoverRequestedScope($scope)) {
                return true;
            }
        }

        return false;
    }

    private function doesSomeAllowedScopeCoverRequestedScope(AuthenticationScope $requested_scope): bool
    {
        return array_reduce(
            $this->allowed_scopes,
            static function (bool $accumulator, AuthenticationScope $allowed_scope) use ($requested_scope) {
                return $accumulator || $allowed_scope->covers($requested_scope);
            },
            false
        );
    }
}
