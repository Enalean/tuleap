<?php
/**
 * Copyright Enalean (c) 2017. All rights reserved.
 *
 * Tuleap and Enalean names and logos are registrated trademarks owned by
 * Enalean SAS. All other trademarks or names are properties of their respective
 * owners.
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

namespace Tuleap\AgileDashboard\Semantic\Dao;

use DataAccessObject;

class SemanticDoneDao extends DataAccessObject
{

    public function getSelectedValues($tracker_id)
    {
        $tracker_id = $this->da->escapeInt($tracker_id);

        $sql = "SELECT value_id
                FROM plugin_agiledashboard_semantic_done
                WHERE tracker_id = $tracker_id";

        return $this->retrieve($sql);
    }

    public function getSemanticStatement($field_id, $tracker_id)
    {
        $field_id   = $this->da->escapeInt($field_id);
        $tracker_id = $this->da->escapeInt($tracker_id);

        return "SELECT IF(static_value.original_value_id, static_value.original_value_id, static_value.id) AS id
                FROM tracker_field_list_bind_static_value AS static_value
                    INNER JOIN plugin_agiledashboard_semantic_done AS semantic_done
                    ON (semantic_done.value_id = static_value.id OR semantic_done.value_id = static_value.original_value_id)
                WHERE semantic_done.tracker_id = $tracker_id
                    AND static_value.field_id = $field_id";
    }
}
