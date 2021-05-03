<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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

declare(strict_types=1);
namespace Tuleap\ProgramManagement\Adapter\Program\Backlog\ProgramIncrement\Source;

use PFUser;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\ProgramIncrementArtifactLinkType;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\AnalyzeNatureOfSourceArtifact;
use Tuleap\ProgramManagement\Domain\Team\Creation\TeamStore;
use Tuleap\Tracker\Artifact\Artifact;

class SourceArtifactNatureAnalyzer implements AnalyzeNatureOfSourceArtifact
{
    /**
     * @var TeamStore
     */
    private $team_store;
    /**
     * @var \ProjectManager
     */
    private $project_manager;
    /**
     * @var \Tracker_ArtifactFactory
     */
    private $artifact_factory;

    public function __construct(
        TeamStore $team_store,
        \ProjectManager $project_manager,
        \Tracker_ArtifactFactory $artifact_factory
    ) {
        $this->team_store       = $team_store;
        $this->project_manager  = $project_manager;
        $this->artifact_factory = $artifact_factory;
    }

    /**
     * @throws NatureAnalyzerException
     */
    public function retrieveProjectOfMirroredArtifact(Artifact $artifact, PFUser $user): ?\Project
    {
        $artifact_link_field = $artifact->getAnArtifactLinkField($user);
        if (! $artifact_link_field) {
            throw new ArtifactLinkFieldNotFoundException((int) $artifact->getId());
        }

        $changeset_value = $artifact->getValue($artifact_link_field);
        if (! $changeset_value) {
            throw new ChangesetValueNotFoundException((int) $artifact->getId());
        }

        $program_increment = $this->team_store->getProgramIncrementOfTeam((int) $artifact->getTracker()->getGroupId());
        if (! $program_increment) {
            throw new ProgramNotFoundException((int) $artifact->getTracker()->getGroupId());
        }

        $project = $this->project_manager->getProject($program_increment);

        foreach ($changeset_value->getValue() as $artifact_link_value) {
            $original_artifact = $this->artifact_factory->getArtifactById($artifact_link_value->getArtifactId());

            if (! $original_artifact || ! $original_artifact->userCanView($user)) {
                return null;
            }
            if ($artifact_link_value->getNature() === ProgramIncrementArtifactLinkType::ART_LINK_SHORT_NAME) {
                return $project;
            }
        }

        return null;
    }
}
