<?php
/**
 * Copyright (c) Enalean, 2024-present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
 *
 *  Tuleap is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  Tuleap is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace Tuleap\CrossTracker\Query;

use Tuleap\DB\UUID;
use Tuleap\Option\Option;

readonly class CrossTrackerQuery
{
    /**
     * @param Option<int> $widget_id
     */
    public function __construct(
        private UUID $uuid,
        private string $query,
        private string $title,
        private string $description,
        private Option $widget_id,
        private bool $is_default,
    ) {
    }

    public function getUUID(): UUID
    {
        return $this->uuid;
    }

    public function getQuery(): string
    {
        return $this->query;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * @return Option<int>
     */
    public function getWidgetId(): Option
    {
        return $this->widget_id;
    }

    public function isDefault(): bool
    {
        return $this->is_default;
    }
}
