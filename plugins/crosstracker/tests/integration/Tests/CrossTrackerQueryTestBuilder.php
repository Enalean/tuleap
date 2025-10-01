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

namespace Tuleap\CrossTracker\Tests;

use Tuleap\CrossTracker\Query\CrossTrackerQuery;
use Tuleap\DB\DatabaseUUIDV7Factory;
use Tuleap\DB\UUID;
use Tuleap\Option\Option;

final class CrossTrackerQueryTestBuilder
{
    private UUID $uuid;
    private string $query       = '';
    private string $title       = '';
    private string $description = '';
    private int $widget_id      = 1;
    private bool $is_default    = false;

    private function __construct()
    {
        $uuid_factory = new DatabaseUUIDV7Factory();
        $this->uuid   = $uuid_factory->buildUUIDFromBytesData($uuid_factory->buildUUIDBytes());
    }

    public static function aQuery(): self
    {
        return new self();
    }

    public function withTitle(string $title): self
    {
        $this->title = $title;
        return $this;
    }

    public function withTqlQuery(string $query): self
    {
        $this->query = $query;
        return $this;
    }

    public function withUUID(UUID $uuid): self
    {
        $this->uuid = $uuid;
        return $this;
    }

    public function inWidget(int $widget_id): self
    {
        $this->widget_id = $widget_id;
        return $this;
    }

    public function isDefault(): self
    {
        $this->is_default = true;
        return $this;
    }

    public function build(): CrossTrackerQuery
    {
        return new CrossTrackerQuery(
            $this->uuid,
            $this->query,
            $this->title,
            $this->description,
            Option::fromValue($this->widget_id),
            $this->is_default
        );
    }
}
