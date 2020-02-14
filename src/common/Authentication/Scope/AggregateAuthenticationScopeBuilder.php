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

use Psr\EventDispatcher\EventDispatcherInterface;

/**
 * @psalm-immutable
 */
final class AggregateAuthenticationScopeBuilder implements AuthenticationScopeBuilder
{
    /**
     * @var AuthenticationScopeBuilder[]
     */
    private $builders;

    private function __construct(AuthenticationScopeBuilder ...$builders)
    {
        $this->builders = $builders;
    }

    public static function fromBuildersList(AuthenticationScopeBuilder ...$builders): self
    {
        return new self(...$builders);
    }

    public static function fromEventDispatcher(EventDispatcherInterface $event_dispatcher, AuthenticationScopeBuilderCollectorEvent $event): self
    {
        $event_dispatcher->dispatch($event);

        return new self(...$event->getAuthenticationKeyScopeBuilders());
    }

    /**
     * @psalm-pure
     */
    public function buildAuthenticationScopeFromScopeIdentifier(AuthenticationScopeIdentifier $scope_identifier): ?AuthenticationScope
    {
        foreach ($this->builders as $builder) {
            $key_scope = $builder->buildAuthenticationScopeFromScopeIdentifier($scope_identifier);
            if ($key_scope !== null) {
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
        $key_scope_sets = [];

        foreach ($this->builders as $builder) {
            $key_scope_sets[] = $builder->buildAllAvailableAuthenticationScopes();
        }

        return array_merge([], ...$key_scope_sets);
    }
}
