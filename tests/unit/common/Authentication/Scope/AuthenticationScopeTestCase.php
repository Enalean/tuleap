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

namespace Tuleap\Authentication\Scope;

use Tuleap\User\AccessKey\Scope\AccessKeyScopeIdentifier;

abstract class AuthenticationScopeTestCase extends \Tuleap\Test\PHPUnit\TestCase
{
    /**
     * @psalm-return class-string<AuthenticationScope>
     */
    abstract public function getAuthenticationScopeClassname(): string;

    final public function testBuildsFromTheExpectedIdentifier(): void
    {
        $identifier = $this->getAuthenticationScopeClassname()::fromItself()->getIdentifier();

        $scope = $this->getAuthenticationScopeClassname()::fromIdentifier($identifier);

        self::assertEquals($identifier, $scope->getIdentifier());
        $definition = $scope->getDefinition();
        self::assertNotEmpty($definition->getName());
        self::assertNotEmpty($definition->getDescription());
    }

    final public function testDoesNotBuildFromUnknownIdentifier(): void
    {
        self::assertNull(
            $this->getAuthenticationScopeClassname()::fromIdentifier(AccessKeyScopeIdentifier::fromIdentifierKey('foo:bar'))
        );
    }

    final public function testScopeCoversItself(): void
    {
        self::assertTrue(
            $this->getAuthenticationScopeClassname()::fromItself()->covers($this->getAuthenticationScopeClassname()::fromItself())
        );
    }
}
