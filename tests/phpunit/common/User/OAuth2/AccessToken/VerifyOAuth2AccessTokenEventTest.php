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

namespace Tuleap\User\OAuth2\AccessToken;

use PHPUnit\Framework\TestCase;
use Tuleap\Authentication\Scope\AuthenticationScope;
use Tuleap\Authentication\Scope\AuthenticationScopeDefinition;
use Tuleap\Authentication\Scope\AuthenticationScopeIdentifier;
use Tuleap\Authentication\SplitToken\SplitToken;
use Tuleap\Authentication\SplitToken\SplitTokenVerificationString;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\User\OAuth2\ResourceServer\GrantedAuthorization;

final class VerifyOAuth2AccessTokenEventTest extends TestCase
{
    public function testReturnsGivenValues(): void
    {
        $token                 = new SplitToken(
            1,
            SplitTokenVerificationString::generateNewSplitTokenVerificationString()
        );
        $required_scope        = $this->buildRequiredScope();
        $event                 = new VerifyOAuth2AccessTokenEvent($token, $required_scope);
        $granted_authorization = new GrantedAuthorization(UserTestBuilder::aUser()->build(), [$required_scope]);
        $event->setGrantedAuthorization($granted_authorization);

        $this->assertSame($token, $event->getAccessToken());
        $this->assertSame($required_scope, $event->getRequiredScope());
        $this->assertSame($granted_authorization, $event->getGrantedAuthorization());
    }

    public function testThrowsNotFoundExceptionWhenNoGrantedAuthorizationHasBeenSet(): void
    {
        $event = new VerifyOAuth2AccessTokenEvent(
            new SplitToken(1, SplitTokenVerificationString::generateNewSplitTokenVerificationString()),
            $this->buildRequiredScope()
        );

        $this->expectException(OAuth2AccessTokenNotFoundException::class);
        $event->getGrantedAuthorization();
    }

    private function buildRequiredScope(): AuthenticationScope
    {
        return new class /** @psalm-immutable */ implements AuthenticationScope
        {
            public static function fromItself(): AuthenticationScope
            {
                throw new \LogicException('This method is not supposed to be called in the test');
            }

            public static function fromIdentifier(AuthenticationScopeIdentifier $identifier): ?AuthenticationScope
            {
                throw new \LogicException('This method is not supposed to be called in the test');
            }

            public function getIdentifier(): AuthenticationScopeIdentifier
            {
                throw new \LogicException('This method is not supposed to be called in the test');
            }

            public function getDefinition(): AuthenticationScopeDefinition
            {
                throw new \LogicException('This method is not supposed to be called in the test');
            }

            public function covers(AuthenticationScope $scope): bool
            {
                throw new \LogicException('This method is not supposed to be called in the test');
            }
        };
    }
}
