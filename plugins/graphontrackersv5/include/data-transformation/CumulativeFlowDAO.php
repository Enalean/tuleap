<?php
/**
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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

namespace Tuleap\GraphOnTrackersV5\DataTransformation;

use Tuleap\DB\DataAccessObject;

class CumulativeFlowDAO extends DataAccessObject
{
    public function getChartColors(int $field_id): array
    {
        $sql = "SELECT val.id, val.label, deco.red, deco.green, deco.blue, deco.tlp_color_name
                FROM  tracker_field_list_bind_static_value AS val
                LEFT JOIN tracker_field_list_bind_decorator AS deco ON (val.id = deco.value_id)
                WHERE val.field_id = ?
                ORDER BY val.rank";

        return $this->getDB()->run($sql, $field_id);
    }

    public function getColorOfNone(int $field_id): ?array
    {
        $sql = "SELECT deco.red, deco.green, deco.blue, deco.tlp_color_name
                FROM  tracker_field_list_bind_decorator AS deco
                WHERE deco.field_id = ? AND deco.value_id = ?";

        return $this->getDB()->row($sql, $field_id, \Tracker_FormElement_Field_List::NONE_VALUE);
    }
}
