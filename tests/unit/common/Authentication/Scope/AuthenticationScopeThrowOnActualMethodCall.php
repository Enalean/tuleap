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
 * @template-covariant TScopeIdentifier of \Tuleap\Authentication\Scope\AuthenticationScopeIdentifier
 */
trait AuthenticationScopeThrowOnActualMethodCall
{
    /**
     * @psalm-pure
     * @psalm-return TScopeIdentifier
     */
    final public function getIdentifier(): AuthenticationScopeIdentifier
    {
        self::throwUnexpectedCall();
    }

    /**
     * @psalm-pure
     */
    final public function getDefinition(): AuthenticationScopeDefinition
    {
        self::throwUnexpectedCall();
    }

    /**
     * @psalm-pure
     */
    final public function covers(AuthenticationScope $scope): bool
    {
        self::throwUnexpectedCall();
    }

    /**
     * @psalm-pure
     * @psalm-return never-return
     *
     * @throws \LogicException
     */
    private static function throwUnexpectedCall(): void
    {
        throw new \LogicException('This method is not supposed to be called in the test');
    }
}
