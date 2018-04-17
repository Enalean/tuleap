<?php
/**
 * Copyright (c) Enalean, 2017 - 2018. All Rights Reserved.
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

namespace Tuleap\TestManagement\REST\v1;

use Luracast\Restler\RestException;
use PFUser;
use Project;
use Tracker_ArtifactFactory;
use Tracker_ReportFactory;
use Tracker_URLVerification;
use Tuleap\REST\ProjectAuthorization;
use Tuleap\TestManagement\ArtifactFactory;
use Tuleap\TestManagement\Config;
use Tuleap\TestManagement\MilestoneItemsArtifactFactory;

class DefinitionSelector
{
    const ALL = 'all';

    const MILESTONE = 'milestone';

    const NONE = 'none';

    const REPORT = 'report';

    /** @var Config */
    private $config;

    /** @var ArtifactFactory */
    private $artifact_factory;

    /** @var ProjectAuthorization */
    private $project_authorization;

    /** @var Tracker_ArtifactFactory */
    private $tracker_artifact_factory;

    /** @var MilestoneItemsArtifactFactory */
    private $milestone_items_artifact_factory;

    /** @var Tracker_ReportFactory */
    private $tracker_report_factory;

    public function __construct(
        Config $config,
        ArtifactFactory $artifact_factory,
        ProjectAuthorization $project_authorization,
        Tracker_ArtifactFactory $tracker_artifact_factory,
        MilestoneItemsArtifactFactory $milestone_items_artifact_factory,
        Tracker_ReportFactory $tracker_report_factory
    ) {
        $this->config                           = $config;
        $this->artifact_factory                 = $artifact_factory;
        $this->project_authorization            = $project_authorization;
        $this->tracker_artifact_factory         = $tracker_artifact_factory;
        $this->milestone_items_artifact_factory = $milestone_items_artifact_factory;
        $this->tracker_report_factory           = $tracker_report_factory;
    }

    public function selectDefinitions(PFUser $user, Project $project, $selector, $milestone_id = 0, $report_id = 0)
    {
        switch ($selector) {
            case self::ALL:
                $definitions = $this->selectAllDefinitions($user, $project);
                break;

            case self::MILESTONE:
                $definitions = $this->selectDefinitionsFromMilestone($user, $project, $milestone_id);
                break;

            case self::REPORT:
                $definitions = $this->selectDefinitionsFromReport($user, $report_id);
                break;

            default:
                $definitions = $this->selectNoDefinitions();
                break;
        }
        return $definitions;
    }

    public function selectNoDefinitions()
    {
        return [];
    }

    public function selectAllDefinitions(PFUser $user, Project $project)
    {
        $tracker_id = $this->config->getTestDefinitionTrackerId($project);

        return $this->tracker_artifact_factory->getArtifactsByTrackerIdUserCanView(
            $user,
            $tracker_id
        );
    }

    public function selectDefinitionsFromMilestone(PFUser $user, Project $project, $milestone_id)
    {
        return $this->milestone_items_artifact_factory->getCoverTestDefinitionsUserCanViewForMilestone(
            $user,
            $project,
            $milestone_id
        );
    }

    public function selectDefinitionsFromReport(PFUser $user, $report_id)
    {
        $report       = $this->getReportById($user, $report_id);
        $matching_ids = $report->getMatchingIds();

        if (! $matching_ids['id']) {
            return [];
        }

        $artifacts = [];
        foreach (explode(',', $matching_ids['id']) as $artifact_id) {
            $artifact = $this->artifact_factory->getArtifactById($artifact_id);
            if ($artifact) {
                $artifacts[] = $artifact;
            }
        }

        return $artifacts;
    }

    private function getReportById(PFUser $user, $id)
    {
        $store_in_session = false;
        $report = $this->tracker_report_factory->getReportById(
            $id,
            $user->getId(),
            $store_in_session
        );

        if (! $report) {
            throw new RestException(404);
        }

        $tracker = $report->getTracker();
        if (! $tracker->userCanView($user)) {
            throw new RestException(403);
        }

        $this->project_authorization->userCanAccessProject(
            $user,
            $tracker->getProject(),
            new Tracker_URLVerification()
        );

        return $report;
    }
}
