<?php
/**
 * Copyright (c) Enalean, 2025-present. All Rights Reserved.
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
use Tuleap\Tracker\Report\Query\Advanced\Grammar\Query;
use Tuleap\Tracker\Report\Query\Advanced\ParserCacheProxy;

final readonly class ParsedCrossTrackerQuery extends CrossTrackerQuery
{
    private function __construct(
        UUID $uuid,
        string $query,
        public Query $parsed_query,
        string $title,
        string $description,
        int $widget_id,
        bool $is_default,
    ) {
        parent::__construct($uuid, $query, $title, $description, $widget_id, $is_default);
    }

    public static function fromCrossTrackerQuery(CrossTrackerQuery $query, ParserCacheProxy $parser): self
    {
        return new self(
            $query->getUUID(),
            $query->getQuery(),
            $parser->parse($query->getQuery()),
            $query->getTitle(),
            $query->getDescription(),
            $query->getWidgetId(),
            $query->isDefault(),
        );
    }
}
