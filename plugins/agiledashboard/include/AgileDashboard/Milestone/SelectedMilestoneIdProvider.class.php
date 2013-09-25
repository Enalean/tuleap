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
 * I retrieve a milestone id given a criteria
 */
class AgileDashboard_Milestone_SelectedMilestoneIdProvider {

    const FIELD_NAME = AgileDashboard_Milestone_MilestoneReportCriterionProvider::FIELD_NAME;
    const ANY        = AgileDashboard_Milestone_MilestoneReportCriterionProvider::ANY;

    /** @var int */
    private $milestone_id;

    public function __construct(array $additional_criteria) {
        $this->milestone_id = self::ANY;
        if (isset($additional_criteria[self::FIELD_NAME])) {
            $this->milestone_id = $additional_criteria[self::FIELD_NAME]->getValue();
        }
    }

    public function getMilestoneId() {
        return $this->milestone_id;
    }
}