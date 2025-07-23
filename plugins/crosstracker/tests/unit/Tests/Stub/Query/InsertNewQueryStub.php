<?php
/**
 * Copyright (c) Enalean, 2025-present. All Rights Reserved.
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
namespace Tuleap\CrossTracker\Tests\Stub\Query;

use Tuleap\CrossTracker\Query\InsertNewQuery;
use Tuleap\DB\UUID;

final class InsertNewQueryStub implements InsertNewQuery
{
    private int $call_count = 0;
    private function __construct(private readonly UUID $uuid)
    {
    }

    #[\Override]
    public function create(
        string $query,
        string $title,
        string $description,
        int $widget_id,
        bool $is_default,
    ): UUID {
        $this->call_count++;
        return $this->uuid;
    }

    public static function withUUID(UUID $uuid): self
    {
        return new self($uuid);
    }

    public function getCallCount(): int
    {
        return $this->call_count;
    }
}
