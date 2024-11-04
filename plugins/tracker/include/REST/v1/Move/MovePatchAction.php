<?php
/**
 * Copyright (c) Enalean 2023 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\REST\v1\Move;

use Luracast\Restler\RestException;
use Project_AccessException;
use Project_AccessProjectNotFoundException;
use Psr\Log\LoggerInterface;
use Tuleap\Tracker\Artifact\ActionButtons\MoveArtifactActionAllowedByPluginRetriever;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Exception\MoveArtifactNotDoneException;
use Tuleap\Tracker\Exception\MoveArtifactNoValuesToProcessException;
use Tuleap\Tracker\Exception\MoveArtifactSemanticsException;
use Tuleap\Tracker\Exception\MoveArtifactTargetProjectNotActiveException;
use Tuleap\Tracker\REST\v1\ArtifactPatchRepresentation;
use Tuleap\Tracker\REST\v1\ArtifactPatchResponseRepresentation;
use Tuleap\Tracker\RetrieveTracker;

final class MovePatchAction
{
    public function __construct(
        private readonly RetrieveTracker $retrieve_tracker,
        private readonly MoveDryRun $dry_run_move,
        private readonly MoveRestArtifact $move_rest_artifact,
        private readonly CheckBeforeMove $before_move_checker,
    ) {
    }

    /**
     * @throws RestException
     * @throws Project_AccessProjectNotFoundException
     * @throws Project_AccessException
     */
    public function patchMove(ArtifactPatchRepresentation $patch, \PFUser $user, Artifact $artifact, LoggerInterface $logger): ArtifactPatchResponseRepresentation
    {
        try {
            $source_tracker = $artifact->getTracker();
            $target_tracker = $this->retrieve_tracker->getTrackerById($patch->move->tracker_id);

            if ($target_tracker === null) {
                throw new RestException(404, 'Target tracker not found');
            }

            $event = new MoveArtifactActionAllowedByPluginRetriever($artifact, $user);
            $this->before_move_checker->check($source_tracker, $target_tracker, $user, $artifact, $event);

            if ($patch->move->dry_run) {
                $logger->debug(sprintf('Dry run move of artifact #%d in tracker #%d (#%s)', $artifact->getId(), $target_tracker->getId(), $target_tracker->getName()));
                return $this->dry_run_move->move($source_tracker, $target_tracker, $artifact, $user, $logger);
            }

            $logger->debug(sprintf('Move of artifact #%d in tracker #%d (#%s)', $artifact->getId(), $target_tracker->getId(), $target_tracker->getName()));
            $this->move_rest_artifact
                ->move($source_tracker, $target_tracker, $artifact, $user, $patch->move->should_populate_feedback_on_success, $logger);

            $logger->debug('Move is ok');
            return ArtifactPatchResponseRepresentation::withoutDryRun();
        } catch (MoveArtifactNotDoneException $exception) {
            throw new RestException(500, $exception->getMessage());
        } catch (
            MoveArtifactSemanticsException |
            MoveArtifactTargetProjectNotActiveException |
            MoveArtifactNoValuesToProcessException $exception
        ) {
            throw new RestException(400, $exception->getMessage());
        }
    }
}
