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
use Tuleap\ProgramManagement\Adapter\Team\MirroredTimeboxes\MirroredTimeboxesDao;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\AnalyzeNatureOfSourceArtifact;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\NatureAnalyzerException;
use Tuleap\ProgramManagement\Domain\Program\Backlog\TimeboxArtifactLinkType;
use Tuleap\ProgramManagement\Domain\Team\MirroredTimebox\TimeboxOfMirroredTimeboxNotFoundException;
use Tuleap\Tracker\Artifact\Artifact;

class SourceArtifactNatureAnalyzer implements AnalyzeNatureOfSourceArtifact
{
    private MirroredTimeboxesDao $mirrored_timeboxes_dao;
    private \Tracker_ArtifactFactory $artifact_factory;

    public function __construct(
        MirroredTimeboxesDao $mirrored_timeboxes_dao,
        \Tracker_ArtifactFactory $artifact_factory
    ) {
        $this->mirrored_timeboxes_dao = $mirrored_timeboxes_dao;
        $this->artifact_factory       = $artifact_factory;
    }

    /**
     * @throws NatureAnalyzerException
     */
    public function retrieveProjectOfMirroredArtifact(Artifact $artifact, PFUser $user): \Project
    {
        $program_increment_id = $this->mirrored_timeboxes_dao
            ->getTimeboxFromMirroredTimeboxId($artifact->getId(), TimeboxArtifactLinkType::ART_LINK_SHORT_NAME);

        if (! $program_increment_id) {
            throw new TimeboxOfMirroredTimeboxNotFoundException((int) $artifact->getTracker()->getGroupId());
        }

        $program_increment = $this->artifact_factory->getArtifactById($program_increment_id);

        if (! $program_increment || ! $program_increment->userCanView($user)) {
            throw new TimeboxOfMirroredTimeboxNotFoundException($program_increment_id);
        }

        return $program_increment->getTracker()->getProject();
    }
}
