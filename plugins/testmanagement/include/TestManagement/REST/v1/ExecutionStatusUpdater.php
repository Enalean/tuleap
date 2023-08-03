<?php
/**
 * Copyright (c) Enalean, 2018-present. All Rights Reserved.
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
use Tracker_Exception;
use Tracker_FormElement_InvalidFieldException;
use Tracker_NoChangeException;
use Tracker_Workflow_GlobalRulesViolationException;
use Tuleap\TestManagement\ArtifactFactory;
use Tuleap\TestManagement\RealTime\RealTimeMessageSender;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\REST\Artifact\ArtifactUpdater;
use Tuleap\User\REST\UserRepresentation;
use UserManager;

class ExecutionStatusUpdater
{
    private ArtifactUpdater $artifact_updater;
    /**
     * @var ArtifactFactory
     */
    private $testmanagement_artifact_factory;
    /**
     * @var RealTimeMessageSender
     */
    private $realtime_message_sender;
    /**
     * @var UserManager
     */
    private $user_manager;

    public function __construct(
        ArtifactUpdater $artifact_updater,
        ArtifactFactory $testmanagement_artifact_factory,
        RealTimeMessageSender $realtime_message_sender,
        UserManager $user_manager,
    ) {
        $this->artifact_updater                = $artifact_updater;
        $this->testmanagement_artifact_factory = $testmanagement_artifact_factory;
        $this->realtime_message_sender         = $realtime_message_sender;
        $this->user_manager                    = $user_manager;
    }

    /**
     * @param array            $changes
     *
     * @throws RestException
     *
     */
    public function update(
        Artifact $execution_artifact,
        array $changes,
        PFUser $user,
    ): void {
        try {
            $previous_status = $this->getCurrentStatus($execution_artifact);
            $previous_user   = $this->getCurrentSubmittedBy($execution_artifact);

            $this->artifact_updater->update($user, $execution_artifact, $changes);

            $new_status = $this->getCurrentStatus($execution_artifact);
            $campaign   = $this->testmanagement_artifact_factory->getCampaignForExecution($execution_artifact);
            if ($campaign) {
                $this->realtime_message_sender->sendExecutionUpdated(
                    $user,
                    $campaign,
                    $execution_artifact,
                    $new_status,
                    $previous_status,
                    $previous_user,
                );
            }
        } catch (Tracker_FormElement_InvalidFieldException $exception) {
            throw new RestException(400, $exception->getMessage());
        } catch (Tracker_NoChangeException $exception) {
            // Do nothing
        } catch (Tracker_Workflow_GlobalRulesViolationException $exception) {
            throw new RestException(400, $GLOBALS['Response']->getRawFeedback());
        } catch (Tracker_Exception $exception) {
            if ($GLOBALS['Response']->feedbackHasErrors()) {
                throw new RestException(500, $GLOBALS['Response']->getRawFeedback());
            }
            throw new RestException(500, $exception->getMessage());
        }
    }

    /** @return string|null */
    private function getCurrentStatus(Artifact $artifact)
    {
        $last_changeset = $artifact->getLastChangeset();
        if (! $last_changeset) {
            return null;
        }

        return $artifact->getStatusForChangeset($last_changeset);
    }

    /** @return UserRepresentation | null */
    private function getCurrentSubmittedBy(Artifact $artifact)
    {
        $last_changeset = $artifact->getLastChangeset();
        if (! $last_changeset) {
            return null;
        }

        $submitted_by = $this->user_manager->getUserById($last_changeset->getSubmittedBy());
        if (! $submitted_by) {
            return null;
        }
        return UserRepresentation::build($submitted_by);
    }
}
