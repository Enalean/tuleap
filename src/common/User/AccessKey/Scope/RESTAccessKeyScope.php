<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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
final class RESTAccessKeyScope implements AuthenticationScope
{
    public const IDENTIFIER_KEY = 'write:rest';

    /**
     * @var AccessKeyScopeIdentifier
     */
    private $identifier;
    /**
     * @var AuthenticationScopeDefinition
     */
    private $definition;

    private function __construct(AccessKeyScopeIdentifier $identifier)
    {
        $this->identifier = $identifier;
        $this->definition = new /** @psalm-immutable */ class implements AuthenticationScopeDefinition
        {
            public function getName(): string
            {
                return _('REST');
            }

            public function getDescription(): string
            {
                return _('Access to the REST API');
            }
        };
    }

    /**
     * @psalm-pure
     */
    public static function fromItself(): AuthenticationScope
    {
        return new self(
            AccessKeyScopeIdentifier::fromIdentifierKey(self::IDENTIFIER_KEY)
        );
    }

    /**
     * @psalm-pure
     */
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

    public function getIdentifier(): AuthenticationScopeIdentifier
    {
        return $this->identifier;
    }

    public function getDefinition(): AuthenticationScopeDefinition
    {
        return $this->definition;
    }

    public function covers(AuthenticationScope $scope): bool
    {
        return self::IDENTIFIER_KEY === $scope->getIdentifier()->toString();
    }
}
