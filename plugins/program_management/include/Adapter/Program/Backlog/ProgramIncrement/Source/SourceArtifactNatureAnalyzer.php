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
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\AnalyzeNatureOfSourceArtifact;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\NatureAnalyzerException;
use Tuleap\ProgramManagement\Domain\Team\MirroredTimebox\RetrieveTimeboxFromMirroredTimebox;
use Tuleap\ProgramManagement\Domain\Team\MirroredTimebox\TimeboxOfMirroredTimeboxNotFoundException;
use Tuleap\Tracker\Artifact\Artifact;

final class SourceArtifactNatureAnalyzer implements AnalyzeNatureOfSourceArtifact
{
    public function __construct(
        private RetrieveTimeboxFromMirroredTimebox $timebox_retriever,
        private \Tracker_ArtifactFactory $artifact_factory
    ) {
    }

    /**
     * @throws NatureAnalyzerException
     */
    public function retrieveProjectOfMirroredArtifact(Artifact $artifact, PFUser $user): \Project
    {
        $program_increment_id = $this->timebox_retriever->getTimeboxFromMirroredTimeboxId($artifact->getId());

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
