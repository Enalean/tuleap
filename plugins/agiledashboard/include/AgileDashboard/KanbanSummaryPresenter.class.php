<?php
/**
 * Copyright (c) Enalean, 2014 - Present. All Rights Reserved.
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

use Tuleap\Kanban\Kanban;
use Tuleap\Kanban\KanbanItemDao;

class AgileDashboard_Presenter_KanbanSummaryPresenter
{
    /** @var Kanban */
    private $kanban;

    /** @var KanbanItemDao */
    private $kanban_item_dao;

    public function __construct(
        Kanban $kanban,
        KanbanItemDao $kanban_item_dao,
    ) {
        $this->kanban          = $kanban;
        $this->kanban_item_dao = $kanban_item_dao;
    }

    public function name()
    {
        return $this->kanban->getName();
    }

    public function id()
    {
        return $this->kanban->getId();
    }

    public function count_open_kanban_items()
    {
        return count($this->kanban_item_dao->getOpenItemIds(
            $this->kanban->getTrackerId()
        ));
    }

    public function count_closed_kanban_items()
    {
        return count($this->kanban_item_dao->getKanbanArchiveItemIds(
            $this->kanban->getTrackerId()
        ));
    }

    public function open()
    {
        return dgettext('tuleap-agiledashboard', 'open');
    }

    public function closed()
    {
        return dgettext('tuleap-agiledashboard', 'closed');
    }
}
