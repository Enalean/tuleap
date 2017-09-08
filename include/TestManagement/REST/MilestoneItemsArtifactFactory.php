<?php
/**
 * Copyright (c) Enalean, 2017. All Rights Reserved.
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

namespace Tuleap\TestManagement;

use EventManager;
use Project;
use PFUser;
use Tracker_ArtifactFactory;
use Tuleap\TestManagement\Nature\NatureCoveredByPresenter;

class MilestoneItemsArtifactFactory
{
    /**
     * @var Config
     */
    private $config;

    /** @var Tracker_ArtifactFactory */
    private $tracker_artifact_factory;

    /** @var ArtifactDao */
    private $dao;

    /** @var EventManager */
    private $event_manager;

    public function __construct(
        Config $config,
        ArtifactDao $dao,
        Tracker_ArtifactFactory $tracker_artifact_factory,
        EventManager $event_manager
    ) {
        $this->config                   = $config;
        $this->dao                      = $dao;
        $this->tracker_artifact_factory = $tracker_artifact_factory;
        $this->event_manager            = $event_manager;
    }

    public function getCoverTestDefinitionsUserCanViewForMilestone(PFUser $user, Project $project, $milestone_id)
    {
        $test_def_tracker_id = $this->config->getTestDefinitionTrackerId($project);
        $test_definitions    = array();

        $event = new \Tuleap\TestManagement\Event\GetItemsFromMilestone($user, $milestone_id);
        $this->event_manager->processEvent($event);

        $results = $this->dao->searchPaginatedLinkedArtifactsByLinkNatureAndTrackerId(
            $event->getItemsIds(),
            NatureCoveredByPresenter::NATURE_COVERED_BY,
            $test_def_tracker_id,
            PHP_INT_MAX,
            0
        );

        foreach ($results as $row) {
            $test_def_artifact = $this->tracker_artifact_factory->getInstanceFromRow($row);
            if ($test_def_artifact->userCanView($user)) {
                $test_definitions[] = $test_def_artifact;
            }
        }
        return $test_definitions;
    }
}
