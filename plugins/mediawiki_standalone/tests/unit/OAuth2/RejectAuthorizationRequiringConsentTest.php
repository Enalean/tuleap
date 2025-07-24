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

use Psr\Log\NullLogger;
use Tuleap\Authentication\Scope\AuthenticationScope;
use Tuleap\Authentication\Scope\AuthenticationTestCoveringScope;
use Tuleap\Http\HTTPFactoryBuilder;
use Tuleap\Http\Server\NullServerRequest;
use Tuleap\OAuth2ServerCore\App\OAuth2App;
use Tuleap\OAuth2ServerCore\AuthorizationServer\AuthorizationCodeResponseFactory;
use Tuleap\OAuth2ServerCore\AuthorizationServer\RedirectURIBuilder;
use Tuleap\OAuth2ServerCore\Grant\AuthorizationCode\OAuth2AuthorizationCodeCreator;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\User\OAuth2\Scope\OAuth2ScopeIdentifier;
use URLRedirect;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class RejectAuthorizationRequiringConsentTest extends TestCase
{
    private RejectAuthorizationRequiringConsent $response_builder;

    #[\Override]
    protected function setUp(): void
    {
        $this->response_builder = new RejectAuthorizationRequiringConsent(
            new AuthorizationCodeResponseFactory(
                HTTPFactoryBuilder::responseFactory(),
                $this->createStub(OAuth2AuthorizationCodeCreator::class),
                new RedirectURIBuilder(HTTPFactoryBuilder::URIFactory()),
                $this->createStub(URLRedirect::class),
                HTTPFactoryBuilder::URIFactory()
            ),
            new NullLogger(),
        );
    }

    public function testAlwaysRejectAuthorizationRequestRequiringConsent(): void
    {
        $response = $this->response_builder->buildConsentRequiredResponse(
            new NullServerRequest(),
            OAuth2App::fromSiteAdministrationData('1', 'Name', 'https://example.com', true),
            'https://example.com',
            'state',
            'code_challenge',
            'oidc_nonce',
            [self::buildScope()]
        );

        self::assertEquals(302, $response->getStatusCode());
        self::assertEquals('https://example.com?state=state&error=invalid_request', $response->getHeaderLine('Location'));
    }

    /**
     * @return AuthenticationScope<\Tuleap\User\OAuth2\Scope\OAuth2ScopeIdentifier>
     */
    private static function buildScope(): AuthenticationScope
    {
        /**
         * @var AuthenticationScope<\Tuleap\User\OAuth2\Scope\OAuth2ScopeIdentifier> $scope
         */
        $scope = AuthenticationTestCoveringScope::fromIdentifier(
            OAuth2ScopeIdentifier::fromIdentifierKey('requested:scope')
        );
        return $scope;
    }
}
