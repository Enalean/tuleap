<?php
/**
 * Copyright (c) Enalean, 2012. All Rights Reserved.
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
class Cardwall_PaneContentPresenter extends Cardwall_BoardPresenter {

    /**
    * @var string
    */
    public $switch_display_username_url;

    /**
    * @var boolean
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
     * @var AgileDashboard_Milestone_Backlog_BacklogItemCollection
     */
    public $milestone_content;

    const SATURDAY          = 5;
    const SUNDAY            = 6;
    const ONE_DAY_IN_SECOND = 86400;

    /**
     * @param Cardwall_Board     $board              The board
     * @param Cardwall_QrCode    $qrcode             QrCode to display. false if no qrcode (thus no typehinting)
     * @param string             $redirect_parameter the redirect paramter to add to various url
     * @param Planning           $planning           The concerned planning
     * @param Planning_Milestone $milestone          The milestone
     */
    public function __construct(
        Cardwall_Board $board,
        $qrcode, $redirect_parameter,
        $switch_display_username_url,
        $is_display_avatar_selected,
        Planning $planning,
        Planning_Milestone $milestone,
        AgileDashboard_Milestone_Backlog_BacklogItemCollection $milestone_content
    ) {
        parent::__construct($board, $qrcode, $redirect_parameter);
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
        $this->milestone_content            = $milestone_content;
    }

    public function isDisplayAvatarSelected() {
        return $this->is_display_avatar_selected;
    }

    public function isUserLoggedIn() {
        return $this->switch_display_username_url;
    }

    public function milestone_title() {
        return $this->milestone->getArtifactTitle();
    }

    public function milestone_edit_url() {
        return '/plugins/tracker/?aid='.$this->milestone->getArtifactId().'&func=edit';
    }

    public function go_to_fullscreen() {
        return $GLOBALS['Language']->getText('plugin_cardwall', 'milestone_go_to_fullscreen');
    }

    public function milestone_has_dates_info() {
        return ($this->milestone->getStartDate() != null && $this->milestone->getEndDate() != null);
    }

    public function milestone_no_date_info() {
        return $GLOBALS['Language']->getText('plugin_cardwall', 'milestone_no_date_info');
    }

    public function milestone_no_initial_effort_info() {
        return $GLOBALS['Language']->getText('plugin_cardwall', 'milestone_no_initial_effort_info');
    }

    public function milestone_days_to_go() {
        if ($this->milestone_days_remaining() <= 1) {
            return $GLOBALS['Language']->getText('plugin_cardwall', 'milestone_day_to_go');
        }

        return $GLOBALS['Language']->getText('plugin_cardwall', 'milestone_days_to_go');
    }

    public function milestone_start_date() {
        return date('d M', $this->milestone->getStartDate());
    }

    public function milestone_end_date() {
        return date('d M', $this->milestone->getEndDate());
    }

    public function milestone_capacity_label() {
        return $GLOBALS['Language']->getText('plugin_cardwall', 'milestone_capacity');
    }

    public function milestone_capacity() {
        return floatval($this->milestone->getCapacity());
    }

    public function milestone_initial_effort_label() {
        return $GLOBALS['Language']->getText('plugin_cardwall', 'milestone_initial_effort');
    }

    public function milestone_initial_effort() {
        $milestone_initial_effort = 0;

        try {
            foreach ($this->milestone_content as $content) {
                $milestone_initial_effort = $this->addInitialEffort($milestone_initial_effort, $content->getInitialEffort());
            }

            return $milestone_initial_effort;
        } catch (InitialEffortNotDefinedException $exception) {
            return null;
        }
    }

    public function milestone_has_initial_effort() {
        return $this->milestone_initial_effort() != 0;
    }

    /**
     * This method ensures that initial effort is correctly defined
     * for all the milestone's backlog items
     *
     * @param type $milestone_initial_effort
     * @param type $backlog_item_initial_effort
     * @return float
     *
     * @throws InitialEffortNotDefinedException
     */
    private function addInitialEffort($milestone_initial_effort, $backlog_item_initial_effort) {
        if (! is_null($backlog_item_initial_effort) && $backlog_item_initial_effort !== '' && $backlog_item_initial_effort >= 0) {
            return $milestone_initial_effort + floatval($backlog_item_initial_effort);
        }

        throw new InitialEffortNotDefinedException();
    }

    public function milestone_points_to_go() {
        if ($this->milestone_remaining_effort() <= 1 ) {
            return $GLOBALS['Language']->getText('plugin_cardwall', 'milestone_point_to_go');
        }

        return $GLOBALS['Language']->getText('plugin_cardwall', 'milestone_points_to_go');
    }

    public function milestone_remaining_effort() {
        if ($this->milestone->getRemainingEffort() > 0) {
            return $this->milestone->getRemainingEffort();
        }

        return 0;
    }

    public function initial_time_completion() {
        if ($this->milestone->getEndDate() < time() || $this->cannotBeDivided($this->milestone->getDuration())) {
            return 100;
        }

        $completion = ceil(
            ($this->milestone->getDuration() - $this->getNumberOfDaysRemainingBetweenMilestoneStartDateAndNowExcludingWeekends())
            / $this->milestone->getDuration() * 100
        );

        return $this->returnRelevantProgressBarValue($completion);
    }

    public function initial_effort_completion() {
        if ($this->cannotBeDivided($this->milestone_initial_effort())) {
            return 100;
        }

        $completion = ceil(
            ( $this->milestone_initial_effort() - $this->milestone->getRemainingEffort() ) / $this->milestone_initial_effort() * 100
        );

        return $this->returnRelevantProgressBarValue($completion);
    }

    private function returnRelevantProgressBarValue($value) {
        if ($value < 0) {
            return 0;
        }

        return $value;
    }

    public function milestone_days_remaining() {
        if ($this->milestone->getEndDate() < time()) {
            return 0;
        }

        return $this->getNumberOfDaysRemainingBetweenMilestoneStartDateAndNowExcludingWeekends();
    }

    private function getNumberOfDaysRemainingBetweenMilestoneStartDateAndNowExcludingWeekends() {
        $current_day = time();

        if (date('w', $current_day) == self::SATURDAY) {
            $current_day = $current_day + 2 * self::ONE_DAY_IN_SECOND - ($current_day % self::ONE_DAY_IN_SECOND);
        } elseif (date('w', $current_day) == self::SUNDAY) {
            $current_day = $current_day + self::ONE_DAY_IN_SECOND - ($current_day % self::ONE_DAY_IN_SECOND);
        }

        $number_of_day_including_week_ends = $current_day - $this->milestone->getStartDate();
        $number_of_day_excluding_week_ends = $number_of_day_including_week_ends - floor($number_of_day_including_week_ends / 7) * 2;

        return floor($this->milestone->getDuration() - ($number_of_day_excluding_week_ends / self::ONE_DAY_IN_SECOND));
    }

    private function cannotBeDivided($number) {
        return $number === 0;
    }
}
?>
