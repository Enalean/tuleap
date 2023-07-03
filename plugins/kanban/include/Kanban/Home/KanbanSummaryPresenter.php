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

namespace Tuleap\Kanban\Home;

use Tuleap\Kanban\Kanban;
use Tuleap\Kanban\KanbanItemDao;

final class KanbanSummaryPresenter
{
    public int $count_open_kanban_items;
    public int $count_closed_kanban_items;

    public function __construct(
        private readonly Kanban $kanban,
        KanbanItemDao $kanban_item_dao,
    ) {
        $this->count_open_kanban_items = count($kanban_item_dao->getOpenItemIds(
            $this->kanban->getTrackerId()
        ));

        $this->count_closed_kanban_items = count($kanban_item_dao->getKanbanArchiveItemIds(
            $this->kanban->getTrackerId()
        ));
    }

    public function name(): string
    {
        return $this->kanban->getName();
    }

    public function id(): int
    {
        return $this->kanban->getId();
    }

    public function open(): string
    {
        return dgettext('tuleap-kanban', 'open');
    }

    public function closed(): string
    {
        return dgettext('tuleap-kanban', 'closed');
    }
}
