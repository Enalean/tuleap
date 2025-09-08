<?php
/**
 * Copyright (c) Enalean, 2023-Present. All Rights Reserved.
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
use Tuleap\Authentication\Scope\AuthenticationScopeDefinition;
use Tuleap\Authentication\Scope\AuthenticationScopeIdentifier;

/**
 * @psalm-immutable
 *
 * @implements AuthenticationScope<AccessKeyScopeIdentifier>
 */
final class SVNAccessKeyScope implements AuthenticationScope
{
    public const IDENTIFIER_KEY = 'write:svn';

    private AuthenticationScopeDefinition $definition;

    private function __construct(private AccessKeyScopeIdentifier $identifier)
    {
        $this->definition = new /** @psalm-immutable */ class implements AuthenticationScopeDefinition
        {
            #[\Override]
            public function getName(): string
            {
                return _('SVN');
            }

            #[\Override]
            public function getDescription(): string
            {
                return _('Access to SVN repositories');
            }
        };
    }

    /**
     * @psalm-pure
     */
    #[\Override]
    public static function fromItself(): AuthenticationScope
    {
        return new self(
            AccessKeyScopeIdentifier::fromIdentifierKey(self::IDENTIFIER_KEY)
        );
    }

    /**
     * @psalm-pure
     */
    #[\Override]
    public static function fromIdentifier(AuthenticationScopeIdentifier $identifier): ?AuthenticationScope
    {
        if (self::isScopeIdentifier($identifier)) {
            return new self($identifier);
        }

        return null;
    }

    /**
     * @psalm-pure
     *
     * @psalm-assert-if-true AccessKeyScopeIdentifier $identifier
     */
    private static function isScopeIdentifier(AuthenticationScopeIdentifier $identifier): bool
    {
        return $identifier->toString() === self::IDENTIFIER_KEY;
    }

    #[\Override]
    public function getIdentifier(): AuthenticationScopeIdentifier
    {
        return $this->identifier;
    }

    #[\Override]
    public function getDefinition(): AuthenticationScopeDefinition
    {
        return $this->definition;
    }

    #[\Override]
    public function covers(AuthenticationScope $scope): bool
    {
        return self::IDENTIFIER_KEY === $scope->getIdentifier()->toString();
    }
}
