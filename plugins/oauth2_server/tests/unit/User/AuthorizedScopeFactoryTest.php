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

namespace Tuleap\OAuth2Server\User;

use Tuleap\Authentication\Scope\AuthenticationScope;
use Tuleap\Authentication\Scope\AuthenticationScopeBuilder;
use Tuleap\Authentication\Scope\AuthenticationScopeIdentifier;
use Tuleap\Authentication\Scope\AuthenticationTestCoveringScope;
use Tuleap\OAuth2ServerCore\App\OAuth2App;
use Tuleap\Test\Builders\UserTestBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class AuthorizedScopeFactoryTest extends \Tuleap\Test\PHPUnit\TestCase
{
    /** @var AuthorizedScopeFactory */
    private $factory;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&AuthorizationDao
     */
    private $authorization_dao;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&AuthorizationScopeDao
     */
    private $scope_dao;

    protected function setUp(): void
    {
        $scope_builder           = new /** @psalm-immutable */ class implements AuthenticationScopeBuilder {
            /**
             * @psalm-suppress ImplementedReturnTypeMismatch Looks like there is a confusion caused by the anonymous class and the templated function
             * @psalm-return AuthenticationScope<AuthenticationScopeIdentifier>|null
             */
            public function buildAuthenticationScopeFromScopeIdentifier(
                AuthenticationScopeIdentifier $scope_identifier,
            ): ?AuthenticationScope {
                if ($scope_identifier->toString() === 'foo:bar' || $scope_identifier->toString() === 'type:value') {
                    return AuthenticationTestCoveringScope::fromIdentifier($scope_identifier);
                }
                return null;
            }

            public function buildAllAvailableAuthenticationScopes(): array
            {
                throw new \LogicException('This method is not supposed to be called in the test');
            }
        };
        $this->authorization_dao = $this->createMock(AuthorizationDao::class);
        $this->scope_dao         = $this->createMock(AuthorizationScopeDao::class);
        $this->factory           = new AuthorizedScopeFactory(
            $this->authorization_dao,
            $this->scope_dao,
            $scope_builder
        );
    }

    public function testGetAuthorizedScopesReturnsEmptyWhenNoAuthorizationCanBeFound(): void
    {
        $user = UserTestBuilder::anAnonymousUser()->build();
        $app  = new OAuth2App(17, 'Jenkins', 'https://example.com', true, new \Project(['group_id' => 102]));
        $this->authorization_dao->expects($this->once())->method('searchAuthorization')
            ->with($user, 17)
            ->willReturn(null);

        $this->assertEmpty(
            $this->factory->getAuthorizedScopes($user, $app)
        );
    }

    public function testGetAuthorizedScopesReturnsScopes(): void
    {
        $user = UserTestBuilder::anAnonymousUser()->build();
        $app  = new OAuth2App(17, 'Jenkins', 'https://example.com', true, new \Project(['group_id' => 102]));
        $this->authorization_dao->expects($this->once())->method('searchAuthorization')
            ->willReturn(12);
        $this->scope_dao->expects($this->once())->method('searchScopes')
            ->with(12)
            ->willReturn(['foo:bar', 'type:value']);

        $saved_scopes = $this->factory->getAuthorizedScopes($user, $app);
        self::assertSame(2, count($saved_scopes));
        self::assertSame('foo:bar', $saved_scopes[0]->getIdentifier()->toString());
        self::assertSame('type:value', $saved_scopes[1]->getIdentifier()->toString());
    }

    public function testGetAuthorizedScopesSkipsInvalidSavedScopes(): void
    {
        $user = UserTestBuilder::anAnonymousUser()->build();
        $app  = new OAuth2App(17, 'Jenkins', 'https://example.com', true, new \Project(['group_id' => 102]));
        $this->authorization_dao->expects($this->once())->method('searchAuthorization')
            ->willReturn(12);
        $this->scope_dao->expects($this->once())->method('searchScopes')
            ->with(12)
            ->willReturn(['flob:wobble', 'type:value']);

        $saved_scopes = $this->factory->getAuthorizedScopes($user, $app);
        self::assertSame(1, count($saved_scopes));
        self::assertSame('type:value', $saved_scopes[0]->getIdentifier()->toString());
    }
}
