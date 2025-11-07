<?php
/**
 * Copyright (c) Enalean, 2025 - Present. All Rights Reserved.
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

namespace Tuleap\Docman\Test\Builders;

use ParagonIE\EasyDB\EasyDB;

final readonly class DocmanDatabaseBuilder
{
    public function __construct(private readonly EasyDB $db)
    {
    }

    public function buildItem(int $item_id, int $project_id): int
    {
        return (int) $this->db->insertReturnId(
            'plugin_docman_item',
            [
                'item_id' => $item_id,
                'parent_id' => 0,
                'group_id' => $project_id,
                'title' => 'My item',
                'description' => 'My description',
                'create_date' => new \DateTimeImmutable()->getTimestamp(),
                'update_date' => new \DateTimeImmutable()->getTimestamp(),
                'delete_date' => null,
                'user_id' => 101,
                'status' => '100',
                'obsolescence_date' => 0,
                'rank' => 0,
                'item_type' => 1,
                'other_type' => null,
                'link_url' => null,
                'wiki_page' => null,
                'file_is_embedded' => null,
            ]
        );
    }
}
