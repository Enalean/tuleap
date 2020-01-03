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

use Psr\EventDispatcher\EventDispatcherInterface;

/**
 * @psalm-immutable
 */
final class AggregateAccessKeyScopeBuilder implements AccessKeyScopeBuilder
{
    /**
     * @var AccessKeyScopeBuilder[]
     */
    private $builders;

    private function __construct(AccessKeyScopeBuilder ...$builders)
    {
        $this->builders = $builders;
    }

    public static function fromBuildersList(AccessKeyScopeBuilder ...$builders): self
    {
        return new self(...$builders);
    }

    public static function fromEventDispatcher(EventDispatcherInterface $event_dispatcher): self
    {
        $event = new AccessKeyScopeBuilderCollector();
        $event_dispatcher->dispatch($event);

        return new self(...$event->getAccessKeyScopeBuilders());
    }

    /**
     * @psalm-pure
     */
    public function buildAccessKeyScopeFromScopeIdentifier(AccessKeyScopeIdentifier $scope_identifier): ?AccessKeyScope
    {
        foreach ($this->builders as $builder) {
            $key_scope = $builder->buildAccessKeyScopeFromScopeIdentifier($scope_identifier);
            if ($key_scope !== null) {
                return $key_scope;
            }
        }

        return null;
    }
}
