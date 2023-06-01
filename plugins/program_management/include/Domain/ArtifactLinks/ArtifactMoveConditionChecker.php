<?php
/**
 * Copyright (c) Enalean, 2023 - present. All Rights Reserved.
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

namespace Tuleap\ProgramManagement\Domain\ArtifactLinks;

use Tuleap\ProgramManagement\Domain\Program\Backlog\Iteration\VerifyIsIteration;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\VerifyIsProgramIncrement;
use Tuleap\ProgramManagement\Domain\Workspace\Tracker\Artifact\ArtifactIdentifier;

final class ArtifactMoveConditionChecker
{
    public function __construct(
        private readonly SearchLinkedArtifacts $search_linked_artifacts,
        private readonly VerifyIsProgramIncrement $verify_is_program_increment,
        private readonly VerifyIsIteration $verify_is_iteration,
        private readonly ForbidArtifactMoveAction $forbid_artifact_move_action,
    ) {
    }

    /**
     * @param int[] $artifact_links_ids
     */
    public function checkArtifactCanBeMoved(ArtifactIdentifier $artifact_id, array $artifact_links_ids): void
    {
        if ($this->doesArtifactLinksMirroredMilestones($artifact_id, $artifact_links_ids)) {
            $this->forbid_artifact_move_action->forbidArtifactMove(
                dgettext('tuleap-program_management', 'This artifact cannot be moved because it is part of a program plan.')
            );
        }

        if ($this->verify_is_program_increment->isProgramIncrement($artifact_id->getId())) {
            $this->forbid_artifact_move_action->forbidArtifactMove(
                dgettext('tuleap-program_management', 'This artifact cannot be moved because it is a program increment.')
            );
        }

        if ($this->verify_is_iteration->isIteration($artifact_id->getId())) {
            $this->forbid_artifact_move_action->forbidArtifactMove(
                dgettext('tuleap-program_management', 'This artifact cannot be moved because it is a program iteration.')
            );
        }
    }

    private function doesArtifactLinksMirroredMilestones(ArtifactIdentifier $artifact_id, array $artifact_links_ids): bool
    {
        if (empty($artifact_links_ids)) {
            return false;
        }

        return $this->search_linked_artifacts->doesArtifactHaveMirroredMilestonesInProvidedLinks(
            $artifact_id->getId(),
            $artifact_links_ids,
        );
    }
}
