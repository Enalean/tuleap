<?php
/**
 * Copyright (c) Enalean, 2013. All Rights Reserved.
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
 * I am a helper to provide a selectbox as a criterion in the tracker report to choose a milestone for a given tracker
 */
class AgileDashboard_Milestone_MilestoneReportCriterionProvider extends DataAccessObject {

    const FIELD_NAME = 'agiledashboard_milestone';

    /** @var AgileDashboard_Milestone_MilestoneReportCriterionOptionsProvider */
    private $options_provider;

    /** @var Codendi_Request */
    private $request;

    public function __construct(
        Codendi_Request $request,
        AgileDashboard_Milestone_MilestoneReportCriterionOptionsProvider $options_provider
    ) {
        $this->request          = $request;
        $this->options_provider = $options_provider;
    }

    /**
     * @return string
     */
    public function getCriterion(Tracker $backlog_tracker) {
        $options = $this->options_provider->getSelectboxOptions($backlog_tracker, $this->request->get(self::FIELD_NAME));
        if (! $options) {
            return null;
        }

        $criterion  = '';
        $criterion .= '<label for="tracker_report_crit_agiledashboard_milestone">';
        $criterion .= $GLOBALS['Language']->getText('plugin_agiledashboard', 'report_criteria_label');
        $criterion .= '</label><br>';
        $criterion .= '<select name="'. self::FIELD_NAME .'" id="tracker_report_crit_agiledashboard_milestone">';
        $criterion .= '<option value="" >'. $GLOBALS['Language']->getText('global','any') .'</option>';
        $criterion .= implode('', $options);
        $criterion .= '</select>';

        return $criterion;
    }
}
