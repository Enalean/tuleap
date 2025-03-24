<?php
/**
 * Copyright (c) Enalean, 2025-Present. All Rights Reserved.
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

namespace Tuleap\CrossTracker\Query;

use Tuleap\DB\DataAccessObject;
use Tuleap\DB\UUID;

/**
 * @psalm-type CrossTrackerQueryRow = array{id: UUID, query: string, title: string, description: string, widget_id: int, is_default: bool}
 * @psalm-type CrossTrackerQueryRawRow = array{id: string, query: string, title: string, description: string, widget_id: int, is_default: int}
 */
final class CrossTrackerQueryDao extends DataAccessObject implements RetrieveQueries, InsertNewQuery, ResetIsDefaultColumn, UpdateQuery
{
    public function searchQueryByUuid(string $uuid_hex): ?array
    {
        return $this->uuid_factory->buildUUIDFromHexadecimalString($uuid_hex)->mapOr(
            function (UUID $uuid) {
                $row = $this->getDB()->row('SELECT * FROM plugin_crosstracker_query WHERE id = ?', $uuid->getBytes());
                if ($row === null) {
                    return null;
                }
                $row['id']                                    = $uuid;
                $row['is_default'] === 1 ? $row['is_default'] = true : $row['is_default'] = false;
                return $row;
            },
            null,
        );
    }

    public function searchQueriesByWidgetId(int $widget_id): array
    {
        $sql = 'SELECT * FROM plugin_crosstracker_query WHERE widget_id = ?';

        $rows = $this->getDB()->run($sql, $widget_id);
        return array_values(array_map($this->transformQueryRow(...), $rows));
    }

    /**
     * @psalm-param CrossTrackerQueryRawRow $row
     * @psalm-return CrossTrackerQueryRow
     */
    private function transformQueryRow(array $row): array
    {
        $row['id']                                    = $this->uuid_factory->buildUUIDFromBytesData($row['id']);
        $row['is_default'] === 1 ? $row['is_default'] = true : $row['is_default'] = false;
        return $row;
    }

    public function update(UUID $id, string $query, string $title, string $description, bool $is_default): void
    {
        $this->getDB()->update('plugin_crosstracker_query', [
            'query'       => $query,
            'title'       => $title,
            'description' => $description,
            'is_default' => $is_default,
        ], [
            'id' => $id->getBytes(),
        ]);
    }

    public function delete(UUID $id): void
    {
        $this->getDB()->delete('plugin_crosstracker_query', ['id' => $id->getBytes()]);
    }

    public function resetIsDefaultColumnByWidgetId(int $widget_id): void
    {
        $this->getDB()->update('plugin_crosstracker_query', [
            'is_default' => false,
        ], ['widget_id' => $widget_id, 'is_default' => true]);
    }

    public function create(string $query, string $title, string $description, int $widget_id, bool $is_default): UUID
    {
        $uuid = $this->uuid_factory->buildUUIDBytes();
        $this->getDB()->insert('plugin_crosstracker_query', [
            'id'          => $uuid,
            'widget_id'   => $widget_id,
            'query'       => $query,
            'title'       => $title,
            'description' => $description,
            'is_default' => $is_default,
        ]);

        return $this->uuid_factory->buildUUIDFromBytesData($uuid);
    }
}
