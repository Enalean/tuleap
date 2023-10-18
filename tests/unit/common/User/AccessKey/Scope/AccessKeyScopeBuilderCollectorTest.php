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

namespace Tuleap\User\AccessKey\Scope;

use Tuleap\Authentication\Scope\AuthenticationScope;
use Tuleap\Authentication\Scope\AuthenticationScopeBuilder;
use Tuleap\Authentication\Scope\AuthenticationScopeIdentifier;

final class AccessKeyScopeBuilderCollectorTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testNoBuildersAreCollectedByDefault(): void
    {
        self::assertEmpty((new AccessKeyScopeBuilderCollector())->getAuthenticationKeyScopeBuilders());
    }

    public function testBuildersAddedToTheCollectorCanBeRetrieved(): void
    {
        $builder_1 = $this->buildAccessKeyScopeBuilder();
        $builder_2 = $this->buildAccessKeyScopeBuilder();

        $collector = new AccessKeyScopeBuilderCollector();

        $collector->addAccessKeyScopeBuilder($builder_1);
        $collector->addAccessKeyScopeBuilder($builder_2);

        self::assertSame([$builder_1, $builder_2], $collector->getAuthenticationKeyScopeBuilders());
    }

    private function buildAccessKeyScopeBuilder(): AuthenticationScopeBuilder
    {
        return new class implements AuthenticationScopeBuilder
        {
            public function buildAuthenticationScopeFromScopeIdentifier(AuthenticationScopeIdentifier $scope_identifier): ?AuthenticationScope
            {
                return null;
            }

            public function buildAllAvailableAuthenticationScopes(): array
            {
                return [];
            }
        };
    }
}
