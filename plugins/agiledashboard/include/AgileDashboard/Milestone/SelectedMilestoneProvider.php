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

/**
 * I retrieve a milestone given a criteria
 */
class AgileDashboard_Milestone_SelectedMilestoneProvider
{
    /** @var Planning_MilestoneFactory */
    private $milestone_factory;

    /** @var Planning_Milestone|null */
    private $milestone;

    /** @var PFUser */
    private $user;

    /** @var Project */
    private $project;

    /** @var array */
    private $additional_criteria;

    /** @var bool */
    private $milestone_has_been_loaded = false;

    public function __construct(array $additional_criteria, Planning_MilestoneFactory $milestone_factory, PFUser $user, Project $project)
    {
        $this->user                = $user;
        $this->project             = $project;
        $this->additional_criteria = $additional_criteria;
        $this->milestone_factory   = $milestone_factory;
    }

    public function getMilestone(): ?Planning_Milestone
    {
        if (! $this->milestone_has_been_loaded) {
            $this->loadMilestone();
        }
        return $this->milestone;
    }

    private function loadMilestone(): void
    {
        if (! isset($this->additional_criteria[AgileDashboard_Milestone_MilestoneReportCriterionProvider::FIELD_NAME])) {
            return;
        }

        if ($this->additional_criteria[AgileDashboard_Milestone_MilestoneReportCriterionProvider::FIELD_NAME]->getValue() == AgileDashboard_Milestone_MilestoneReportCriterionOptionsProvider::TOP_BACKLOG_IDENTIFIER) {
            $this->milestone = $this->milestone_factory->getVirtualTopMilestone($this->user, $this->project);
            return;
        }

        $this->milestone = $this->milestone_factory->getBareMilestoneByArtifactId(
            $this->user,
            $this->additional_criteria[AgileDashboard_Milestone_MilestoneReportCriterionProvider::FIELD_NAME]->getValue()
        );

        $this->milestone_has_been_loaded = true;
    }

    public function getMilestoneId(): int|string
    {
        if (! $this->milestone_has_been_loaded) {
            $this->loadMilestone();
        }

        if (! $this->milestone) {
            return AgileDashboard_Milestone_MilestoneReportCriterionProvider::ANY;
        }

        $artifact_id = $this->milestone->getArtifactId();
        if ($artifact_id === null) {
            return AgileDashboard_Milestone_MilestoneReportCriterionOptionsProvider::TOP_BACKLOG_IDENTIFIER;
        }

        return $artifact_id;
    }
}
