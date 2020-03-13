<?php
/**
 * Copyright (c) Enalean, 2012 - 2018. All Rights Reserved.
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

use Tuleap\AgileDashboard\Milestone\Pane\PanePresenterData;

/**
 * This presenter build the top view of a milestone (milestone title + switch on another milestone).
 * It delegates the display to an AgileDashboardPane for the content
 * @see AgileDashboard_Pane
 */
class AgileDashboard_MilestonePresenter
{
    /**
     * @var Planning_Milestone
     */
    private $milestone;

    /**
     * @var PanePresenterData
     */
    private $presenter_data;

    public function __construct(
        Planning_Milestone $milestone,
        PanePresenterData $presenter_data
    ) {
        $this->milestone      = $milestone;
        $this->presenter_data = $presenter_data;
    }

    public function milestoneTitle()
    {
        return $this->milestone->getArtifactTitle();
    }

    public function milestoneId()
    {
        return $this->milestone->getArtifactId();
    }

    public function artifact()
    {
        return $GLOBALS['Language']->getText('plugin_tracker_include_artifact', 'artifact');
    }

    public function editArtifact()
    {
        return $GLOBALS['Language']->getText('plugin_agiledashboard', 'edit_item_dropdown', array($this->milestoneTitle()));
    }

    public function editArtifactUrl()
    {
        return '/plugins/tracker/?aid=' . $this->milestone->getArtifactId();
    }

    public function getActivePane()
    {
        return $this->presenter_data->getActivePane();
    }

    /**
     * @return array
     */
    public function getPaneInfoList()
    {
        return $this->presenter_data->getListOfPaneInfo();
    }

    public function startDate()
    {
        $start_date = $this->milestone->getStartDate();
        if (! $start_date) {
            return null;
        }
        return $this->formatDate($start_date);
    }

    public function endDate()
    {
        $end_date = $this->milestone->getEndDate();
        if (! $end_date) {
            return null;
        }
        return $this->formatDate($end_date);
    }

    public function displayMilestoneDates()
    {
        $start_date = $this->startDate();
        $end_date   = $this->endDate();

        return $start_date && $end_date;
    }

    private function formatDate($date)
    {
        return date($GLOBALS['Language']->getText('system', 'datefmt_day_and_month'), $date);
    }
}
