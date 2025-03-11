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
use Tuleap\CrossTracker\REST\v1\Representation\CrossTrackerQueryPostRepresentation;
use Tuleap\DB\DatabaseUUIDV7Factory;
use Tuleap\DB\UUID;

/**
 * @psalm-import-type CrossTrackerQueryRow from CrossTrackerQueryDao
 */
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

        return self::fromRow($query_row);
    }

    /**
     * @return CrossTrackerQuery[]
     */
    public function getByWidgetId(int $id): array
    {
        $rows = $this->query_retriever->searchQueriesByWidgetId($id);

        $result = [];
        foreach ($rows as $row) {
            $result[] = self::fromRow($row);
        }

        return $result;
    }

    public static function fromTqlQueryAndWidgetId(string $tql_query, int $widget_id): CrossTrackerQuery
    {
        $uuid_factory = new DatabaseUUIDV7Factory();
        return new CrossTrackerQuery(
            $uuid_factory->buildUUIDFromBytesData($uuid_factory->buildUUIDBytes()),
            $tql_query,
            '',
            '',
            $widget_id,
            false
        );
    }

    public static function fromQueryPostRepresentation(CrossTrackerQueryPOSTRepresentation $query_post_representation,): CrossTrackerQuery
    {
        $uuid_factory = new DatabaseUUIDV7Factory();
        return new CrossTrackerQuery(
            $uuid_factory->buildUUIDFromBytesData($uuid_factory->buildUUIDBytes()),
            $query_post_representation->tql_query,
            $query_post_representation->title,
            $query_post_representation->description,
            $query_post_representation->widget_id,
            $query_post_representation->is_default,
        );
    }

    public static function fromCreatedQuery(UUID $uuid, CrossTrackerQuery $new_query): CrossTrackerQuery
    {
        return new CrossTrackerQuery(
            $uuid,
            $new_query->getQuery(),
            $new_query->getTitle(),
            $new_query->getDescription(),
            $new_query->getWidgetId(),
            $new_query->isDefault(),
        );
    }

    /** @psalm-param CrossTrackerQueryRow $row  */
    private static function fromRow(array $row): CrossTrackerQuery
    {
        return new CrossTrackerQuery($row['id'], $row['query'], $row['title'], $row['description'], $row['widget_id'], $row['is_default']);
    }
}
