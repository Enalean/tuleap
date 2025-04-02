<?php
/**
 * Copyright (c) Enalean, 2017-Present. All Rights Reserved.
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
use PFUser;
use Project;
use Tracker_ArtifactFactory;
use Tuleap\TestManagement\Event\GetItemsFromMilestone;
use Tuleap\TestManagement\Type\TypeCoveredByPresenter;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\ArtifactLinkField;

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
        EventManager $event_manager,
    ) {
        $this->config                   = $config;
        $this->dao                      = $dao;
        $this->tracker_artifact_factory = $tracker_artifact_factory;
        $this->event_manager            = $event_manager;
    }

    public function getCoverTestDefinitionsUserCanViewForMilestone(PFUser $user, Project $project, int $milestone_id): array
    {
        $test_definitions = [];

        $event = new GetItemsFromMilestone($user, $milestone_id);
        $this->event_manager->processEvent($event);

        $this->appendArtifactsByTypes(
            $user,
            $test_definitions,
            $event,
            $project,
            [TypeCoveredByPresenter::TYPE_COVERED_BY, ArtifactLinkField::TYPE_IS_CHILD],
        );

        return $test_definitions;
    }

    /**
     * @param string[] $types
     *
     * @psalm-param non-empty-array<string> $types
     */
    private function appendArtifactsByTypes(
        PFUser $user,
        array &$test_definitions,
        GetItemsFromMilestone $event,
        Project $project,
        array $types,
    ): void {
        $artifacts_ids = $event->getItemsIds();
        if (empty($artifacts_ids)) {
            return;
        }

        $results = $this->dao->searchPaginatedLinkedArtifactsByLinkTypeAndTrackerId(
            $artifacts_ids,
            $types,
            $this->config->getTestDefinitionTrackerId($project),
            PHP_INT_MAX,
            0
        );

        $this->appendArtifactsUserCanView($user, $test_definitions, $results);
    }

    private function appendArtifactsUserCanView(PFUser $user, array &$test_definitions, array $results): void
    {
        foreach ($results as $row) {
            $test_def_artifact = $this->tracker_artifact_factory->getInstanceFromRow($row);
            if ($test_def_artifact->userCanView($user)) {
                $test_definitions[] = $test_def_artifact;
            }
        }
    }
}
