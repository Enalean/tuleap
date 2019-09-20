<?php
/**
 * Copyright (c) Enalean, 2014. All Rights Reserved.
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

class Planning_Presenter_MilestoneSummaryPresenter extends Planning_Presenter_MilestoneSummaryPresenterAbstract
{

    /**
     * A status array. E.g.
     *  array(
     *      Tracker_Artifact::STATUS_OPEN   => no_of_opne,
     *      Tracker_Artifact::STATUS_CLOSED => no_of_closed,
     *  )
     *
     * @var array
     */
    private $status_count;

    public function __construct(Planning_Milestone $milestone, $plugin_path, $has_cardwall, $status_count)
    {
        parent::__construct($milestone, $plugin_path, $has_cardwall);

        $this->status_count = $status_count;
    }

    public function has_burndown()
    {
        return false;
    }

    public function count_closed_backlog_items()
    {
        return $this->status_count[Tracker_Artifact::STATUS_CLOSED];
    }

    public function count_open_backlog_items()
    {
        return $this->status_count[Tracker_Artifact::STATUS_OPEN];
    }

    public function open()
    {
        return $GLOBALS['Language']->getText('plugin_agiledashboard', 'open');
    }

    public function closed()
    {
        return $GLOBALS['Language']->getText('plugin_agiledashboard', 'closed');
    }
}
