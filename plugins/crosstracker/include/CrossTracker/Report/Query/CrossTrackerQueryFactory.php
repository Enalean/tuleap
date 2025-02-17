<?php
/**
 * Copyright (c) Enalean, 2017 - Present. All Rights Reserved.
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

namespace Tuleap\CrossTracker\Report\Query;

use Tuleap\CrossTracker\CrossTrackerQuery;
use Tuleap\CrossTracker\REST\v1\CrossTrackerQueryNotFoundException;

final readonly class CrossTrackerQueryFactory
{
    public function __construct(
        private RetrieveQueries $query_retriever,
    ) {
    }

    /**
     * @throws CrossTrackerQueryNotFoundException
     */
    public function getById(string $uuid): CrossTrackerQuery
    {
        $query_row = $this->query_retriever->searchQueryByUuid($uuid);
        if ($query_row === null) {
            throw new CrossTrackerQueryNotFoundException();
        }

        return new CrossTrackerQuery($query_row['id'], $query_row['query'], $query_row['title'], $query_row['description'], $query_row['widget_id']);
    }

    /**
     * @return CrossTrackerQuery[]
     */
    public function getByWidgetId(int $id): array
    {
        $rows = $this->query_retriever->searchQueriesByWidgetId($id);

        $result = [];
        foreach ($rows as $row) {
            $result[] = new CrossTrackerQuery($row['id'], $row['query'], $row['title'], $row['description'], $row['widget_id']);
        }

        return $result;
    }
}
