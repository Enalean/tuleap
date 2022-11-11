<?php
/**
 * Copyright (c) Enalean, 2022 - Present. All Rights Reserved.
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

namespace Tuleap\Docman\Log;

interface IRetrieveStoredLog
{
    /**
     * @return array{time: int, group_id: int, user_id: int, type: int, old_value: string|null, new_value: string|null, field: string|null}[]
     */
    public function searchByItemIdOrderByTimestamp(int $item_id): array;

    /**
     * @return array{time: int, group_id: int, user_id: int, type: int, old_value: string|null, new_value: string|null, field: string|null}[]
     */
    public function paginatedSearchByItemIdOrderByTimestamp(int $item_id, int $limit, int $offset): array;

    public function countByItemId(int $item_id): int;
}
