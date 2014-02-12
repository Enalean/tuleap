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

class Planning_Presenter_HomePresenter {

    /** @var Planning_Presenter_MilestoneAccessPresenter[] */
    public $milestone_presenters;

    /** @var int */
    public $group_id;

    /** @var string */
    public $last_milestone_type;

    /** @var Planning_Presenter_MilestoneSummaryPresenterAbstract[] */
    public $milestone_summary_presenters;

    /** @var string */
    private $period;

    /** @var string */
    private $project_name;

    public function __construct(
        $milestone_access_presenters,
        $group_id,
        $last_milestone_type,
        $milestone_summary_presenters,
        $period,
        $project_name
    ) {
        $this->milestone_presenters         = $milestone_access_presenters;
        $this->group_id                     = $group_id;
        $this->last_milestone_type          = $last_milestone_type;
        $this->milestone_summary_presenters = $milestone_summary_presenters;
        $this->period                       = $period;
        $this->project_name                 = $project_name;
    }

    public function past() {
        return $GLOBALS['Language']->getText('plugin_agiledashboard','past');
    }

    public function now() {
        return $GLOBALS['Language']->getText('plugin_agiledashboard','now');
    }

    public function future() {
        return $GLOBALS['Language']->getText('plugin_agiledashboard','future');
    }

    public function past_active() {
        if ($this->period == Planning_Controller::PAST_PERIOD) {
            return 'active';
        }

        return '';
    }

    public function now_active() {
        if (! $this->past_active() && !$this->future_active()) {
            return 'active';
        }

        return '';
    }

    public function future_active() {
        if ($this->period == Planning_Controller::FUTURE_PERIOD) {
            return 'active';
        }

        return '';
    }

    public function project_backlog() {
        return $GLOBALS['Language']->getText('plugin_agiledashboard','project_backlog', array($this->project_name));
    }
}
?>
