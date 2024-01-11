<?php
/**
 * Copyright (c) Enalean 2024 - Present. All Rights Reserved.
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

namespace Tuleap\CrossTracker\Report\Query\Advanced\QueryValidation\Field;

use ParagonIE\EasyDB\EasyStatement;
use Tuleap\DB\DataAccessObject;

final class TrackerFieldDao extends DataAccessObject implements SearchFieldTypes
{
    /**
     * @param int[] $tracker_ids
     * @psalm-return array<array{type: string}>
     */
    public function searchTypeByFieldNameAndTrackerList(string $field_name, array $tracker_ids): array
    {
        $tracker_ids_statement = EasyStatement::open()->in('?*', $tracker_ids);

        $sql = "SELECT formElement_type AS type
                FROM tracker_field WHERE name=? AND tracker_id IN ($tracker_ids_statement)";

        $parameters = array_merge([$field_name], $tracker_ids_statement->values());
        return $this->getDB()->safeQuery($sql, $parameters);
    }
}
