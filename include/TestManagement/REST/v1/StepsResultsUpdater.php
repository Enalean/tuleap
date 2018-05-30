<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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
use Tracker_Artifact;
use Tracker_Exception;
use Tracker_FormElement_InvalidFieldException;
use Tracker_NoChangeException;
use Tracker_REST_Artifact_ArtifactUpdater;

class StepsResultsUpdater
{
    /**
     * @var Tracker_REST_Artifact_ArtifactUpdater
     */
    private $artifact_updater;
    /**
     * @var StepsResultsChangesBuilder
     */
    private $changes_builder;

    public function __construct(
        Tracker_REST_Artifact_ArtifactUpdater $artifact_updater,
        StepsResultsChangesBuilder $changes_builder
    ) {
        $this->artifact_updater = $artifact_updater;
        $this->changes_builder  = $changes_builder;
    }

    /**
     * @param PFUser                     $user
     * @param Tracker_Artifact           $execution_artifact
     * @param Tracker_Artifact           $definition_artifact
     * @param StepResultRepresentation[] $submitted_steps_results
     *
     * @throws RestException
     */
    public function updateStepsResults(
        PFUser $user,
        Tracker_Artifact $execution_artifact,
        Tracker_Artifact $definition_artifact,
        array $submitted_steps_results
    ) {
        try {
            $changes = $this->changes_builder->getStepsChanges(
                $submitted_steps_results,
                $execution_artifact,
                $definition_artifact,
                $user
            );

            $this->artifact_updater->update($user, $execution_artifact, $changes);
        } catch (Tracker_FormElement_InvalidFieldException $exception) {
            throw new RestException(400, $exception->getMessage());
        } catch (Tracker_NoChangeException $exception) {
            // Do nothing
        } catch (Tracker_Exception $exception) {
            if ($GLOBALS['Response']->feedbackHasErrors()) {
                throw new RestException(500, $GLOBALS['Response']->getRawFeedback());
            }
            throw new RestException(500, $exception->getMessage());
        }
    }
}
