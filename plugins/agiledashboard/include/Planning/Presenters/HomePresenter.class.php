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
        $is_user_admin,
        $is_mono_milestone_enabled,
        bool $is_planning_management_delegated,
        public readonly string $create_kanban_url,
        public readonly \Tuleap\CSRFSynchronizerTokenPresenter $csrf_token,
        bool $is_using_kanban_service,
        public readonly ?string $kanban_service_url,
        private readonly bool $is_split_feature_flag_enabled,
    ) {
        parent::__construct($group_id, $is_user_admin, $is_mono_milestone_enabled, $is_planning_management_delegated, $is_using_kanban_service);
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
        $this->is_mono_milestone_enabled       = $is_mono_milestone_enabled;
    }

    public function kanban_empty_message_must_be_displayed()
    {
        return count($this->kanban_summary_presenters) === 0 && ! $this->is_user_admin;
    }

    public function scrum_nothing_set_up()
    {
        if ($this->is_user_admin) {
            return sprintf(dgettext('tuleap-agiledashboard', 'No Scrum has been yet configured by a project administrator. <a href="%1$s">Get Started!</a>'), '/plugins/agiledashboard/?group_id=' . $this->group_id . '&action=admin');
        }

        return dgettext('tuleap-agiledashboard', 'No Scrum has been yet configured by a project administrator.');
    }

    public function kanban_nothing_set_up()
    {
        return dgettext('tuleap-agiledashboard', 'No Kanban has been yet configured by a project administrator.');
    }

    public function come_back_later()
    {
        return dgettext('tuleap-agiledashboard', 'Please come back later.');
    }

    public function past()
    {
        return dgettext('tuleap-agiledashboard', 'Done');
    }

    public function now()
    {
        return dgettext('tuleap-agiledashboard', 'What\'s hot');
    }

    public function future()
    {
        return dgettext('tuleap-agiledashboard', 'What\'s next');
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
        if (! $this->past_active() && ! $this->future_active()) {
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
        return sprintf(dgettext('tuleap-agiledashboard', '%1$s backlog'), $this->project_name);
    }

    public function has_milestone_presenters()
    {
        return ! empty($this->milestone_presenters);
    }

    public function user_helper()
    {
        if ($this->past_active() !== '') {
            return dgettext('tuleap-agiledashboard', 'Milestones are "Done" when they have their status set to \'closed\' as defined in their tracker\'s semantic.');
        }

        return dgettext('tuleap-agiledashboard', 'All milestones that have a status set to \'open\' (as defined in their tracker\'s semantic) are either "Current" or "Next". Their timeframe (as defined in tracker semantic) decides in which tab(s) they appear.');
    }

    public function add_kanban()
    {
        return dgettext('tuleap-agiledashboard', 'Add a kanban');
    }

    public function user_can_see_kanban()
    {
        return $this->kanban_activated;
    }

    public function add_kanban_modal_title()
    {
        return dgettext('tuleap-agiledashboard', 'Create a kanban');
    }

    public function btn_close_label()
    {
        return dgettext('tuleap-agiledashboard', 'Close');
    }

    public function btn_add_modal_label()
    {
        return dgettext('tuleap-agiledashboard', 'Create');
    }

    public function kanban_name_label()
    {
        return dgettext('tuleap-agiledashboard', 'Kanban name:');
    }

    public function tracker_kanban_label()
    {
        return dgettext('tuleap-agiledashboard', 'Select the source tracker:');
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
        return dgettext('tuleap-agiledashboard', 'No tracker is available.');
    }

    public function top_backlog_planning()
    {
        return $this->is_split_feature_flag_enabled ? dgettext('tuleap-agiledashboard', 'Backlog') : dgettext('tuleap-agiledashboard', 'Top Backlog Planning');
    }

    public function content()
    {
        return dgettext('tuleap-agiledashboard', 'Overview');
    }
}
