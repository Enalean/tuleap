<?php
/**
 * Copyright (c) Enalean, 2017. All Rights Reserved.
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

namespace Tuleap\AgileDashboard\Widget;

use DataAccessObject;

class WidgetKanbanDao extends DataAccessObject
{
    public function create($owner_id, $owner_type, $kanban_id, $kanban_title)
    {
        $owner_id     = $this->da->escapeInt($owner_id);
        $owner_type   = $this->da->quoteSmart($owner_type);
        $kanban_id    = $this->da->escapeInt($kanban_id);
        $kanban_title = $this->da->quoteSmart($kanban_title);

        $sql = "INSERT INTO plugin_agiledashboard_kanban_widget(owner_id, owner_type, title, kanban_id)
                VALUES ($owner_id, $owner_type, $kanban_title, $kanban_id)";

        return $this->updateAndGetLastId($sql);
    }

    public function searchWidgetById($id, $owner_id, $owner_type)
    {
        $owner_id   = $this->da->escapeInt($owner_id);
        $owner_type = $this->da->quoteSmart($owner_type);
        $id         = $this->da->escapeInt($id);

        $sql = "SELECT *
                FROM plugin_agiledashboard_kanban_widget
                WHERE owner_id = $owner_id
                  AND owner_type = $owner_type
                  AND id = $id";

        return $this->retrieveFirstRow($sql);
    }

    public function delete($id, $owner_id, $owner_type)
    {
        $owner_id   = $this->da->escapeInt($owner_id);
        $owner_type = $this->da->quoteSmart($owner_type);
        $id         = $this->da->escapeInt($id);

        $sql = "DELETE FROM plugin_agiledashboard_kanban_widget
                WHERE id = $id
                  AND owner_id = $owner_id
                  AND owner_type = $owner_type";

        return $this->update($sql);
    }
}
