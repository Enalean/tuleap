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

use Codendi_Request;

class WidgetKanbanCreator
{
    /**
     * @var WidgetKanbanDao
     */
    private $widget_kanban_dao;

    public function __construct(WidgetKanbanDao $widget_kanban_dao)
    {
        $this->widget_kanban_dao = $widget_kanban_dao;
    }

    /**
     * @param Codendi_Request $request
     * @param $owner_id
     * @param $owner_type
     * @return int
     * @throws \RuntimeException
     */
    public function create(Codendi_Request $request, $owner_id, $owner_type)
    {
        $kanban         = $request->get('kanban');
        if ($kanban === false) {
            throw new \RuntimeException('Missing parameter: kanban');
        }
        if (! isset($kanban['id'])) {
            throw new \RuntimeException('Missing parameter: kanban[id]');
        }
        if (! isset($kanban['title'])) {
            throw new \RuntimeException('Missing parameter: kanban[title]');
        }
        $kanban_id      = $kanban['id'];
        $kanban_title   = $kanban['title'];

        return $this->widget_kanban_dao->create($owner_id, $owner_type, $kanban_id, $kanban_title);
    }
}
