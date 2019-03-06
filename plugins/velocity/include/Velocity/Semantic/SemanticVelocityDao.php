<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

namespace Tuleap\Velocity\Semantic;

use Tuleap\DB\DataAccessObject;

class SemanticVelocityDao extends DataAccessObject
{
    public function addField($tracker_id, $field_id)
    {
        $sql = 'REPLACE INTO plugin_velocity_semantic_field(tracker_id, field_id) VALUES (?, ?)';
        $this->getDB()->run($sql, $tracker_id, $field_id);
    }

    public function removeField($tracker_id)
    {
        $sql = 'DELETE
                FROM plugin_velocity_semantic_field
                WHERE tracker_id = ?';

        return $this->getDB()->run($sql, $tracker_id);
    }

    public function searchUsedVelocityField($tracker_id)
    {
        $sql = 'SELECT *
                FROM plugin_velocity_semantic_field
                WHERE tracker_id = ?';

        return $this->getDB()->row($sql, $tracker_id);
    }
}
