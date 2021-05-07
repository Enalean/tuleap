<?php
/**
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

declare(strict_types=1);

namespace Tuleap\Git\Reference;

use Tuleap\DB\DataAccessObject;

class ReferenceDao extends DataAccessObject
{
    /**
     * @psalm-return null|array{id: int, keyword: string, description: string, link: string, scope: string, service_short_name: string, nature: string}
     */
    public function searchExistingProjectReference(string $keyword, int $project_id): ?array
    {
        $sql = "SELECT *
                FROM reference
                    INNER JOIN reference_group ON (reference.id = reference_group.reference_id)
                WHERE reference.keyword = ?
                    AND reference_group.group_id = ?
                    AND reference.scope = 'P'
                    AND reference_group.is_active = 1
                LIMIT 1";

        return $this->getDB()->row($sql, $keyword, $project_id);
    }
}
