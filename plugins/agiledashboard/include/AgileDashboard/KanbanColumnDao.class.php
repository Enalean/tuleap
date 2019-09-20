<?php
/**
 * Copyright (c) Enalean, 2015. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

class AgileDashboard_KanbanColumnDao extends DataAccessObject
{

    public function getColumnWipLimit($kanban_id, $column_id)
    {
        $kanban_id = $this->da->escapeInt($kanban_id);
        $column_id = $this->da->escapeInt($column_id);

        $sql = "SELECT wip_limit
                FROM plugin_agiledashboard_kanban_configuration_column
                WHERE kanban_id = $kanban_id
                AND value_id = $column_id";

        return $this->retrieve($sql);
    }

    public function setColumnWipLimit($kanban_id, $column_id, $wip_limit)
    {
        $kanban_id = $this->da->escapeInt($kanban_id);
        $column_id = $this->da->escapeInt($column_id);
        $wip_limit = $this->da->escapeInt($wip_limit);

        $sql = "REPLACE INTO plugin_agiledashboard_kanban_configuration_column (kanban_id, value_id, wip_limit)
                VALUES ($kanban_id, $column_id, $wip_limit)";

        return $this->update($sql);
    }

    public function deleteColumn($kanban_id, $column_id)
    {
        $kanban_id = $this->da->escapeInt($kanban_id);
        $column_id = $this->da->escapeInt($column_id);

        $sql = "DELETE FROM plugin_agiledashboard_kanban_configuration_column
                WHERE kanban_id = $kanban_id
                AND value_id = $column_id";

        return $this->update($sql);
    }
}
