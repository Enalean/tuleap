<?php
/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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

namespace Tuleap\Docman\Widget;

use Tuleap\DB\DataAccessObject;

class WidgetEmbeddedDao extends DataAccessObject
{
    /**
     * @psalm-return array{title: string, item_id: int}|null
     */
    public function searchContent(int $owner_id, string $owner_type, int $id): ?array
    {
        $sql = 'SELECT title, item_id
                FROM plugin_docman_widget_embedded
                WHERE id = ?
                  AND owner_id = ?
                  AND owner_type = ?';

        return $this->getDB()->row($sql, $id, $owner_id, $owner_type);
    }

    public function insertContent(int $owner_id, string $owner_type, string $title, int $item_id): int
    {
        return (int) $this->getDB()->insertReturnId(
            'plugin_docman_widget_embedded',
            [
                'owner_id' => $owner_id,
                'owner_type' => $owner_type,
                'title' => $title,
                'item_id' => $item_id,
            ]
        );
    }

    public function cloneContent(int $source_owner_id, string $source_owner_type, int $destination_owner_id, string $destination_owner_type): int
    {
        $sql = 'INSERT INTO plugin_docman_widget_embedded (owner_id, owner_type, title, item_id)
                SELECT  ?, ?, title, item_id
                FROM plugin_docman_widget_embedded
                WHERE owner_id = ?
                  AND owner_type = ?';

        $this->getDB()->run(
            $sql,
            $destination_owner_id,
            $destination_owner_type,
            $source_owner_id,
            $source_owner_type
        );

        return (int) $this->getDB()->lastInsertId();
    }
}
