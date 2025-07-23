<?php
/**
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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

namespace Tuleap\Test\Stubs;

final class EventDispatcherStub implements \Psr\EventDispatcher\EventDispatcherInterface
{
    private int $call_count = 0;

    private function __construct(private \Closure $callback)
    {
    }

    public static function withCallback(\Closure $callback): self
    {
        return new self($callback);
    }

    public static function withIdentityCallback(): self
    {
        return new self(static fn(object $event) => $event);
    }

    #[\Override]
    public function dispatch(object $event): object
    {
        $this->call_count++;
        return ($this->callback)($event);
    }

    public function getCallCount(): int
    {
        return $this->call_count;
    }
}
