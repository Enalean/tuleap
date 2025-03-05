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
use Tuleap\Authentication\Scope\AuthenticationTestCoveringScope;
use Tuleap\OAuth2ServerCore\App\OAuth2App;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\User\OAuth2\Scope\OAuth2ScopeIdentifier;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class MediawikiStandaloneOAuth2ConsentCheckerTest extends TestCase
{
    public function testDoesNotRequireConsentForAllowedScopes(): void
    {
        $scope           = self::buildScope('foo:bar');
        $consent_checker = new MediawikiStandaloneOAuth2ConsentChecker([$scope]);

        self::assertFalse($consent_checker->isConsentRequired(
            [],
            UserTestBuilder::buildWithDefaults(),
            OAuth2App::fromSiteAdministrationData('1', 'Name', 'https://example.com', true),
            [$scope]
        ));
    }

    public function testRequiresConsentForScopesThatAreNotAllowed(): void
    {
        $allowed_scope   = self::buildScope('foo:bar');
        $consent_checker = new MediawikiStandaloneOAuth2ConsentChecker([$allowed_scope]);

        $requested_scope = self::buildScope('some:scope');

        self::assertTrue($consent_checker->isConsentRequired(
            [],
            UserTestBuilder::buildWithDefaults(),
            OAuth2App::fromSiteAdministrationData('1', 'Name', 'https://example.com', true),
            [$requested_scope]
        ));
    }

    public function testConsentIsRequiredWhenItHasBeenExplicitlyRequestedInThePromptValues(): void
    {
        $scope           = self::buildScope('foo:bar');
        $consent_checker = new MediawikiStandaloneOAuth2ConsentChecker([$scope]);

        self::assertTrue($consent_checker->isConsentRequired(
            ['consent'],
            UserTestBuilder::buildWithDefaults(),
            OAuth2App::fromSiteAdministrationData('1', 'Name', 'https://example.com', true),
            [$scope]
        ));
    }

    /**
     * @return AuthenticationScope<\Tuleap\User\OAuth2\Scope\OAuth2ScopeIdentifier>
     */
    private static function buildScope(string $identifier): AuthenticationScope
    {
        /**
         * @var AuthenticationScope<\Tuleap\User\OAuth2\Scope\OAuth2ScopeIdentifier> $scope
         */
        $scope = AuthenticationTestCoveringScope::fromIdentifier(
            OAuth2ScopeIdentifier::fromIdentifierKey($identifier)
        );
        return $scope;
    }
}
