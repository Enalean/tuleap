<?php
/**
 * Copyright (c) Enalean, 2014 - 2018. All Rights Reserved.
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

class Planning_Presenter_HomePresenter extends Planning_Presenter_BaseHomePresenter
{

    /** @var array */
    public $kanban_summary_presenters;

    public $trackers;

    /** @var Planning_Presenter_MilestoneAccessPresenter[] */
    public $milestone_presenters;

    /** @var Planning_Presenter_LastLevelMilestone[] */
    public $last_level_milestone_presenters;

    /** @var bool */
    public $is_mono_milestone_enabled;

    /** @var string */
    private $period;

    /** @var string */
    private $project_name;

    /** @var bool */
    private $kanban_activated;

    /** @var bool */
    public $scrum_activated;

    /** @var bool */
    public $scrum_is_configured;

    /** @var string */
    public $scrum_title;

    /** @var string */
    public $kanban_title;

    public function __construct(
        $milestone_access_presenters,
        $group_id,
        $last_level_milestone_presenters,
        $period,
        $project_name,
        $kanban_activated,
        $trackers,
        array $kanban_summary_presenters,
        $scrum_activated,
        $scrum_is_configured,
        $scrum_title,
        $kanban_title,
        $is_user_admin,
        $is_mono_milestone_enabled
    ) {
        parent::__construct($group_id, $is_user_admin, $is_mono_milestone_enabled);
        $this->milestone_presenters            = $milestone_access_presenters;
        $this->last_level_milestone_presenters = $last_level_milestone_presenters;
        $this->period                          = $period;
        $this->project_name                    = $project_name;
        $this->kanban_activated                = $kanban_activated;
        $this->trackers                        = $trackers;
        $this->kanban_summary_presenters       = $kanban_summary_presenters;
        $this->scrum_activated                 = $scrum_activated;
        $this->scrum_is_configured             = $scrum_is_configured;
        $this->scrum_title                     = $scrum_title;
        $this->kanban_title                    = $kanban_title;
    }

    public function kanban_empty_message_must_be_displayed()
    {
        return count($this->kanban_summary_presenters) === 0 && ! $this->is_user_admin;
    }

    public function scrum_nothing_set_up()
    {
        if ($this->is_user_admin) {
            return $GLOBALS['Language']->getText(
                'plugin_agiledashboard',
                'nothing_set_up_scrum_admin',
                array('/plugins/agiledashboard/?group_id=' . $this->group_id . '&action=admin')
            );
        }

        return $GLOBALS['Language']->getText('plugin_agiledashboard', 'nothing_set_up_scrum_generic');
    }

    public function kanban_nothing_set_up()
    {
        return $GLOBALS['Language']->getText('plugin_agiledashboard', 'nothing_set_up_kanban_generic');
    }

    public function come_back_later()
    {
        return $GLOBALS['Language']->getText('plugin_agiledashboard', 'nothing_set_up_come_back');
    }

    public function past()
    {
        return $GLOBALS['Language']->getText('plugin_agiledashboard', 'past');
    }

    public function now()
    {
        return $GLOBALS['Language']->getText('plugin_agiledashboard', 'now');
    }

    public function future()
    {
        return $GLOBALS['Language']->getText('plugin_agiledashboard', 'future');
    }

    public function past_active()
    {
        if ($this->period == Planning_Controller::PAST_PERIOD) {
            return 'active';
        }

        return '';
    }

    public function now_active()
    {
        if (! $this->past_active() && !$this->future_active()) {
            return 'active';
        }

        return '';
    }

    public function future_active()
    {
        if ($this->period == Planning_Controller::FUTURE_PERIOD) {
            return 'active';
        }

        return '';
    }

    public function project_backlog()
    {
        return $GLOBALS['Language']->getText(
            'plugin_agiledashboard',
            'project_backlog',
            $this->project_name
        );
    }

    public function has_milestone_presenters()
    {
        return ! empty($this->milestone_presenters);
    }

    public function user_helper()
    {
        if ($this->past_active() !== '') {
            return $GLOBALS['Language']->getText('plugin_agiledashboard', 'home_user_helper_done');
        }

        return $GLOBALS['Language']->getText('plugin_agiledashboard', 'home_user_helper_others');
    }

    public function add_kanban()
    {
        return $GLOBALS['Language']->getText('plugin_agiledashboard', 'add_kanban');
    }

    public function user_can_see_kanban()
    {
        return $this->kanban_activated;
    }

    public function add_kanban_modal_title()
    {
        return $GLOBALS['Language']->getText('plugin_agiledashboard', 'add_kanban_modal_title');
    }

    public function btn_close_label()
    {
        return $GLOBALS['Language']->getText('plugin_agiledashboard', 'btn_close_label');
    }

    public function btn_add_modal_label()
    {
        return $GLOBALS['Language']->getText('plugin_agiledashboard', 'btn_add_modal_label');
    }

    public function kanban_name_label()
    {
        return $GLOBALS['Language']->getText('plugin_agiledashboard', 'kanban_name_label');
    }

    public function tracker_kanban_label()
    {
        return $GLOBALS['Language']->getText('plugin_agiledashboard', 'tracker_kanban_label');
    }

    public function are_trackers_available()
    {
        foreach ($this->trackers as $tracker) {
            if ($tracker['used'] === false) {
                return true;
            }
        }

        return false;
    }

    public function no_tracker_available()
    {
        return $GLOBALS['Language']->getText('plugin_agiledashboard', 'no_tracker_available');
    }

    public function top_backlog_planning()
    {
        return $GLOBALS['Language']->getText('plugin_agiledashboard', 'top_planning_link');
    }

    public function content()
    {
        return $GLOBALS['Language']->getText('plugin_agiledashboard', 'details_pane_title');
    }
}
