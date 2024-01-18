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
use Tracker;
use Tuleap\NeverThrow\Fault;
use Tuleap\REST\ProjectStatusVerificator;
use Tuleap\Tracker\Admin\MoveArtifacts\MoveActionAllowedChecker;
use Tuleap\Tracker\Artifact\ActionButtons\MoveArtifactActionAllowedByPluginRetriever;
use Tuleap\Tracker\Artifact\Artifact;

final class BeforeMoveChecker implements CheckBeforeMove
{
    public function __construct(
        private readonly \EventManager $event_manager,
        private readonly ProjectStatusVerificator $status_verificator,
        private readonly MoveActionAllowedChecker $move_action_allowed_checker,
    ) {
    }

    /**
     * @throws RestException
     */
    public function check(Tracker $source_tracker, Tracker $target_tracker, \PFUser $user, Artifact $artifact, MoveArtifactActionAllowedByPluginRetriever $event): void
    {
        $this->status_verificator->checkProjectStatusAllowsAllUsersToAccessIt(
            $source_tracker->getProject()
        );

        $this->move_action_allowed_checker->checkMoveActionIsAllowedInTracker($source_tracker)
            ->orElse(
                function (Fault $move_action_forbidden_fault): void {
                    throw new RestException(404, (string) $move_action_forbidden_fault);
                }
            );

        if ($target_tracker->isDeleted()) {
            throw new RestException(404, "Target tracker not found");
        }

        $this->status_verificator->checkProjectStatusAllowsAllUsersToAccessIt(
            $target_tracker->getProject()
        );

        if (! $source_tracker->userIsAdmin($user) || ! $target_tracker->userIsAdmin($user)) {
            throw new RestException(400, "User must be admin of both trackers");
        }

        if ($source_tracker->getId() === $target_tracker->getId()) {
            throw new RestException(400, "An artifact cannot be moved in the same tracker");
        }

        $this->event_manager->processEvent($event);

        if ($event->doesAnExternalPluginForbiddenTheMove()) {
            throw new RestException(400, $event->getErrorMessage());
        }
    }
}
