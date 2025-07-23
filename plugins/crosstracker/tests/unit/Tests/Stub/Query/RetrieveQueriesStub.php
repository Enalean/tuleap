<?php
/**
 * Copyright (c) Enalean, 2024-Present. All Rights Reserved.
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

use Tuleap\CrossTracker\Query\CrossTrackerQueryDao;

/**
 * @psalm-import-type CrossTrackerQueryRow from CrossTrackerQueryDao
 */
final readonly class RetrieveQueriesStub implements \Tuleap\CrossTracker\Query\RetrieveQueries
{
    /**
     * @param list<CrossTrackerQueryRow> $queries
     */
    private function __construct(private array $queries)
    {
    }

    #[\Override]
    public function searchQueryByUuid(string $uuid_hex): ?array
    {
        foreach ($this->queries as $row) {
            if ($row['id']->toString() === $uuid_hex) {
                return $row;
            }
        }
        return null;
    }

    #[\Override]
    public function searchQueriesByWidgetId(int $widget_id): array
    {
        $result = [];
        foreach ($this->queries as $row) {
            if ($row['widget_id'] === $widget_id) {
                $result[] = $row;
            }
        }
        return $result;
    }

    /**
     * @psalm-param CrossTrackerQueryRow $first_query
     * @psalm-param  CrossTrackerQueryRow ...$other_queries
     * @no-named-arguments
     */
    public static function withQueries(array $first_query, array ...$other_queries): self
    {
        $queries = [$first_query, ...$other_queries];
        return new self($queries);
    }
}
