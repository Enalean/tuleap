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

/**
 * @psalm-immutable
 */
final class AuthenticationScopeBuilderFromClassNames implements AuthenticationScopeBuilder
{
    /**
     * @var string[]
     *
     * @psalm-var class-string<AuthenticationScope>[]
     */
    private $classnames;

    /**
     * @psalm-param class-string<AuthenticationScope>[] $classnames
     */
    public function __construct(string ...$classnames)
    {
        $this->classnames = $classnames;
    }

    /**
     * @psalm-pure
     *
     * @psalm-suppress InvalidReturnType It's not possible to express templating inside a class-string
     */
    public function buildAuthenticationScopeFromScopeIdentifier(AuthenticationScopeIdentifier $scope_identifier): ?AuthenticationScope
    {
        foreach ($this->classnames as $classname) {
            $key_scope = $classname::fromIdentifier($scope_identifier);
            if ($key_scope !== null) {
                /** @psalm-suppress InvalidReturnStatement It's not possible to express templating inside a class-string */
                return $key_scope;
            }
        }

        return null;
    }

    /**
     * @psalm-pure
     *
     * @return AuthenticationScope[]
     */
    public function buildAllAvailableAuthenticationScopes(): array
    {
        $scopes = [];

        foreach ($this->classnames as $classname) {
            $scopes[] = $classname::fromItself();
        }

        return $scopes;
    }
}
