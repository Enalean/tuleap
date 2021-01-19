<?php
/**
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
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

namespace Tuleap\ScaledAgile\Adapter\Program\Backlog\ProgramIncrement;

use Tuleap\ScaledAgile\Program\Backlog\ProgramIncrement\ProgramIncrement;
use Tuleap\ScaledAgile\Program\Backlog\ProgramIncrement\RetrieveProgramIncrements;
use Tuleap\ScaledAgile\Program\Program;

final class ProgramIncrementsRetriever implements RetrieveProgramIncrements
{
    /**
     * @var ProgramIncrementsDAO
     */
    private $program_increments_dao;
    /**
     * @var \Tracker_ArtifactFactory
     */
    private $artifact_factory;

    public function __construct(
        ProgramIncrementsDAO $program_increments_dao,
        \Tracker_ArtifactFactory $artifact_factory
    ) {
        $this->program_increments_dao = $program_increments_dao;
        $this->artifact_factory       = $artifact_factory;
    }

    /**
     * @return ProgramIncrement[]
     */
    public function retrieveOpenProgramIncrements(Program $program, \PFUser $user): array
    {
        $program_increment_rows      = $this->program_increments_dao->searchOpenProgramIncrements($program->getId());
        $program_increment_artifacts = [];

        foreach ($program_increment_rows as $program_increment_row) {
            $artifact = $this->artifact_factory->getArtifactByIdUserCanView($user, $program_increment_row['id']);
            if ($artifact !== null) {
                $program_increment_artifacts[] = $artifact;
            }
        }

        $program_increments = [];
        foreach ($program_increment_artifacts as $program_increment_artifact) {
            $title = $program_increment_artifact->getTitle();
            if ($title === null) {
                continue;
            }
            $status_field = $program_increment_artifact->getTracker()->getStatusField();
            if ($status_field === null || ! $status_field->userCanRead($user)) {
                continue;
            }

            $program_increments[] = new ProgramIncrement($title);
        }

        return $program_increments;
    }
}
