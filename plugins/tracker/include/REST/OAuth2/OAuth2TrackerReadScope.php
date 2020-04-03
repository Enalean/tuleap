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

namespace Tuleap\Tracker\REST\OAuth2;

use Tuleap\Authentication\Scope\AuthenticationScope;
use Tuleap\Authentication\Scope\AuthenticationScopeDefinition;
use Tuleap\Authentication\Scope\AuthenticationScopeIdentifier;
use Tuleap\User\OAuth2\Scope\OAuth2ScopeIdentifier;

/**
 * @psalm-immutable
 *
 * @implements AuthenticationScope<OAuth2ScopeIdentifier>
 */
final class OAuth2TrackerReadScope implements AuthenticationScope
{
    private const IDENTIFIER_KEY = 'read:tracker';

    /**
     * @var OAuth2ScopeIdentifier
     */
    private $identifier;
    /**
     * @var AuthenticationScopeDefinition
     */
    private $definition;

    private function __construct(OAuth2ScopeIdentifier $identifier)
    {
        $this->identifier = $identifier;
        $this->definition = new /** @psalm-immutable */ class implements AuthenticationScopeDefinition
        {
            public function getName(): string
            {
                return dgettext('tuleap-tracker', 'Tracker (read only)');
            }

            public function getDescription(): string
            {
                return dgettext('tuleap-tracker', 'Read only access to information about trackers');
            }
        };
    }

    /**
     * @psalm-pure
     */
    public static function fromItself(): AuthenticationScope
    {
        return new self(OAuth2ScopeIdentifier::fromIdentifierKey(self::IDENTIFIER_KEY));
    }

    /**
     * @psalm-pure
     */
    public static function fromIdentifier(AuthenticationScopeIdentifier $identifier): ?AuthenticationScope
    {
        if ($identifier instanceof OAuth2ScopeIdentifier && $identifier->toString() === self::IDENTIFIER_KEY) {
            return new self($identifier);
        }

        return null;
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
