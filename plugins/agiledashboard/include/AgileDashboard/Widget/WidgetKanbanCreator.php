<?php
/**
 * Copyright (c) Enalean, 2017 - 2018. All Rights Reserved.
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
     * @param $owner_id
     * @param $owner_type
     */
    public function create(Codendi_Request $request, $owner_id, $owner_type)
    {
        $kanban            = $request->get('kanban');
        $kanban_id         = $kanban['id'];
        $kanban_title      = $kanban['title'];
        $tracker_report_id = (int) $request->get('tracker_report_id');

        return $this->createKanbanWidget($owner_id, $owner_type, $kanban_id, $kanban_title, $tracker_report_id);
    }

    public function createKanbanWidget(
        $owner_id,
        $owner_type,
        $kanban_id,
        $kanban_title,
        $tracker_report_id
    ) {
        return $this->widget_kanban_dao->createKanbanWidget(
            $owner_id,
            $owner_type,
            $kanban_id,
            $kanban_title,
            $tracker_report_id
        );
    }
}
