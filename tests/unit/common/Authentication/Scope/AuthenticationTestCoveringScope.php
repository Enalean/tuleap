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
 * @implements AuthenticationScope<AuthenticationScopeIdentifier>
 */
final class AuthenticationTestCoveringScope implements AuthenticationScope
{
    /**
     * @var AuthenticationScopeIdentifier
     */
    private $identifier;

    private function __construct(AuthenticationScopeIdentifier $identifier)
    {
        $this->identifier = $identifier;
    }

    #[\Override]
    public static function fromItself(): AuthenticationScope
    {
        self::throwUnexpectedCall();
    }

    /**
     * @psalm-pure
     */
    #[\Override]
    public static function fromIdentifier(AuthenticationScopeIdentifier $identifier): AuthenticationScope
    {
        return new self($identifier);
    }

    #[\Override]
    public function getIdentifier(): AuthenticationScopeIdentifier
    {
        return $this->identifier;
    }

    #[\Override]
    public function getDefinition(): AuthenticationScopeDefinition
    {
        self::throwUnexpectedCall();
    }

    #[\Override]
    public function covers(AuthenticationScope $scope): bool
    {
        return $scope->getIdentifier()->toString() === $this->identifier->toString();
    }

    /**
     * @psalm-return never-return
     *
     * @throws \LogicException
     */
    private static function throwUnexpectedCall(): void
    {
        throw new \LogicException('This method is not supposed to be called in the test');
    }
}
