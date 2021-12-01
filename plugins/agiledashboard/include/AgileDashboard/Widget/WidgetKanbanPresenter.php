<?php
/**
 * Copyright (c) Enalean, 2017 - Present. All Rights Reserved.
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

use KanbanPresenter;

class WidgetKanbanPresenter
{
    /**
     * @var KanbanPresenter|null
     */
    public $kanban_presenter;
    public $is_empty;
    public $error_message;
    /**
     * @var string
     */
    public $empty_state;

    public function __construct(
        $is_empty,
        $error_message,
        ?KanbanPresenter $kanban_presenter = null,
    ) {
        $this->kanban_presenter = $kanban_presenter;
        $this->is_empty         = $is_empty;
        $this->error_message    = $error_message;
        $this->there_is_error   = ! empty($this->error_message);

        $this->empty_state = dgettext('tuleap-agiledashboard', "There is no content you can see");
    }
}
