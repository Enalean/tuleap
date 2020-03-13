<?php
/**
 * Copyright (c) Enalean, 2013 - Present. All Rights Reserved.
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

use Tuleap\AgileDashboard\ExplicitBacklog\UnplannedCriterionOptionsProvider;
use Tuleap\AgileDashboard\ExplicitBacklog\UnplannedReportCriterionChecker;

/**
 * I am a helper to provide a selectbox as a criterion in the tracker report to choose a milestone for a given tracker
 */
class AgileDashboard_Milestone_MilestoneReportCriterionProvider
{
    public const FIELD_NAME = 'agiledashboard_milestone';
    public const ANY        = '';
    public const UNPLANNED  = '-1';

    /** @var AgileDashboard_Milestone_MilestoneReportCriterionOptionsProvider */
    private $options_provider;

    /** @var AgileDashboard_Milestone_SelectedMilestoneProvider */
    private $milestone_provider;

    /**
     * @var UnplannedCriterionOptionsProvider
     */
    private $unplanned_criterion_options_provider;

    /**
     * @var UnplannedReportCriterionChecker
     */
    private $unplanned_report_criterion_checker;

    public function __construct(
        AgileDashboard_Milestone_SelectedMilestoneProvider $milestone_provider,
        AgileDashboard_Milestone_MilestoneReportCriterionOptionsProvider $options_provider,
        UnplannedCriterionOptionsProvider $unplanned_criterion_options_provider,
        UnplannedReportCriterionChecker $unplanned_report_criterion_checker
    ) {
        $this->milestone_provider                   = $milestone_provider;
        $this->options_provider                     = $options_provider;
        $this->unplanned_criterion_options_provider = $unplanned_criterion_options_provider;
        $this->unplanned_report_criterion_checker   = $unplanned_report_criterion_checker;
    }

    /**
     * @return string
     */
    public function getCriterion(Tracker $backlog_tracker, PFUser $user)
    {
        $options = $this->options_provider->getSelectboxOptions(
            $backlog_tracker,
            $this->getSelectedOptionId(),
            $user
        );
        if (! $options) {
            return null;
        }

        $criterion  = '';
        $criterion .= '<label for="tracker_report_crit_agiledashboard_milestone">';
        $criterion .= $GLOBALS['Language']->getText('plugin_agiledashboard', 'report_criteria_label');
        $criterion .= '</label>';
        $criterion .= '<select name="additional_criteria[' . self::FIELD_NAME . ']" id="tracker_report_crit_agiledashboard_milestone">';
        $criterion .= '<option value="" >' . $GLOBALS['Language']->getText('global', 'any') . '</option>';
        $criterion .= $this->unplanned_criterion_options_provider->formatUnplannedAsSelectboxOption(
            $backlog_tracker->getProject(),
            $this->getSelectedOptionId()
        );
        $criterion .= implode('', $options);
        $criterion .= '</select>';

        return $criterion;
    }

    private function getSelectedOptionId()
    {
        if ($this->unplanned_report_criterion_checker->isUnplannedValueSelected()) {
            return AgileDashboard_Milestone_MilestoneReportCriterionProvider::UNPLANNED;
        }

        return $this->milestone_provider->getMilestoneId();
    }
}
