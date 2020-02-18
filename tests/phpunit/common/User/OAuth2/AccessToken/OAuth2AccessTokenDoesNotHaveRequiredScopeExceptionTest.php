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

use Laminas\Cache\Exception\LogicException;
use PHPUnit\Framework\TestCase;
use Tuleap\Authentication\Scope\AuthenticationScope;
use Tuleap\Authentication\Scope\AuthenticationScopeDefinition;
use Tuleap\Authentication\Scope\AuthenticationScopeIdentifier;
use Tuleap\User\OAuth2\Scope\OAuth2ScopeIdentifier;

final class OAuth2AccessTokenDoesNotHaveRequiredScopeExceptionTest extends TestCase
{
    public function testRequiredScopeIsKept(): void
    {
        $scope = new class /** @psalm-immutable */ implements AuthenticationScope
        {
            public static function fromItself() : AuthenticationScope
            {
                throw new LogicException('Not Supposed to be called in the test');
            }

            public static function fromIdentifier(AuthenticationScopeIdentifier $identifier) : ?AuthenticationScope
            {
                throw new LogicException('Not Supposed to be called in the test');
            }

            public function getIdentifier(): AuthenticationScopeIdentifier
            {
                return OAuth2ScopeIdentifier::fromIdentifierKey('foo');
            }

            public function getDefinition(): AuthenticationScopeDefinition
            {
                return new class /** @psalm-immutable */ implements AuthenticationScopeDefinition
                {
                    public function getName() : string
                    {
                        return 'Test';
                    }

                    public function getDescription() : string
                    {
                        return 'For test';
                    }
                };
            }

            public function covers(AuthenticationScope $scope) : bool
            {
                throw new LogicException('Not Supposed to be called in the test');
            }
        };

        $exception = new OAuth2AccessTokenDoesNotHaveRequiredScopeException($scope);
        $this->assertSame($scope, $exception->getNeededScope());
    }
}
