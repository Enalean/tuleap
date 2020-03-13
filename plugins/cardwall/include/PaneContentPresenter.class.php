<?php
/**
 * Copyright (c) Enalean, 2012-Present. All Rights Reserved.
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


/**
 * A board to display in agiledashboard
 */
class Cardwall_PaneContentPresenter extends Cardwall_BoardPresenter //phpcs:ignore
{

    /**
    * @var string
    */
    public $switch_display_username_url;

    /**
    * @var bool
    */
    public $is_display_avatar_selected;

    /**
     * @var string
     */
    public $search_cardwall_placeholder;

    /**
     * @var int
     */
    public $planning_id;

    /**
     * @var Planning_Milestone
     */
    public $milestone;

    /**
     * @var Cardwall_EffortProgressPresenter
     */
    public $progress_presenter;

    /**
     * @param Cardwall_Board                   $board The board
     * @param string                           $redirect_parameter the redirect paramter to add to various url
     * @param string                           $switch_display_username_url
     * @param bool $is_display_avatar_selected
     * @param Planning                         $planning The concerned planning
     * @param Planning_Milestone               $milestone The milestone
     */
    public function __construct(
        Cardwall_Board $board,
        $redirect_parameter,
        $switch_display_username_url,
        $is_display_avatar_selected,
        Planning $planning,
        Planning_Milestone $milestone,
        Cardwall_EffortProgressPresenter $progress_presenter
    ) {
        parent::__construct($board, $redirect_parameter);
        $this->nifty                        = '';
        $this->swimline_title               = $GLOBALS['Language']->getText('plugin_cardwall', 'swimline_title');
        $this->has_swimline_header          = true;
        $this->switch_display_username_url  = $switch_display_username_url;
        $this->is_display_avatar_selected   = $is_display_avatar_selected;
        $this->display_avatar_label         = $GLOBALS['Language']->getText('plugin_cardwall', 'display_avatar_label');
        $this->display_avatar_title         = $GLOBALS['Language']->getText('plugin_cardwall', 'display_avatar_title');
        $this->search_cardwall_placeholder  = $GLOBALS['Language']->getText('plugin_cardwall', 'search_cardwall_placeholder');
        $this->planning_id                  = $planning->getId();
        $this->milestone                    = $milestone;
        $this->progress_presenter           = $progress_presenter;
    }

    public function isDisplayAvatarSelected()
    {
        return $this->is_display_avatar_selected;
    }

    public function isUserLoggedIn()
    {
        return $this->switch_display_username_url;
    }

    public function milestone_title()
    {
        return $this->milestone->getArtifactTitle();
    }

    public function milestone_edit_url()
    {
        return '/plugins/tracker/?aid=' . $this->milestone->getArtifactId();
    }

    public function go_to_fullscreen()
    {
        return $GLOBALS['Language']->getText('plugin_cardwall', 'milestone_go_to_fullscreen');
    }

    public function milestone_has_dates_info()
    {
        return ($this->milestone->getStartDate() != null && $this->milestone->getEndDate() != null);
    }

    public function milestone_no_date_info()
    {
        return $GLOBALS['Language']->getText('plugin_cardwall', 'milestone_no_date_info');
    }

    public function milestone_no_initial_effort_info()
    {
        return $GLOBALS['Language']->getText('plugin_cardwall', 'milestone_no_initial_effort_info');
    }

    public function milestone_days_to_go()
    {
        if ($this->milestone_days_remaining() <= 1) {
            return $GLOBALS['Language']->getText('plugin_cardwall', 'milestone_day_to_go');
        }

        return $GLOBALS['Language']->getText('plugin_cardwall', 'milestone_days_to_go');
    }

    public function milestone_start_date()
    {
        $milestone_start_date = $this->milestone->getStartDate();
        if ($milestone_start_date !== null) {
            return date('d M', $milestone_start_date);
        }

        return '';
    }

    public function milestone_end_date()
    {
        $milestone_end_date = $this->milestone->getEndDate();
        if ($milestone_end_date !== null) {
            return date('d M', $milestone_end_date);
        }

        return '';
    }

    public function initial_time_completion()
    {
        $milestone_duration = $this->milestone->getDuration();

        if ($milestone_duration === null || $milestone_duration === 0) {
            return 0;
        }

        $completion = ceil(
            ($milestone_duration - $this->milestone_days_remaining()) / $milestone_duration * 100
        );

        return $this->returnRelevantProgressBarValue($completion);
    }

    private function returnRelevantProgressBarValue($value)
    {
        if ($value < 0) {
            return 0;
        }

        return $value;
    }

    public function milestone_days_remaining()
    {
        return max($this->milestone->getDaysUntilEnd(), 0);
    }
}
