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

namespace Tuleap\Velocity;

use Feedback;
use PFUser;
use Tracker_FormElement_Field;
use Tracker_Semantic_Status;
use Tuleap\AgileDashboard\Semantic\SemanticDone;
use Tuleap\Tracker\Workflow\BeforeEvent;
use Tuleap\Velocity\Semantic\SemanticVelocity;

class VelocityComputation
{
    /**
     * @var VelocityCalculator
     */
    private $calculator;
    /**
     * @var VelocityComputationChecker
     */
    private $computation_checker;

    public function __construct(VelocityCalculator $calculator, VelocityComputationChecker $computation_checker)
    {
        $this->calculator          = $calculator;
        $this->computation_checker = $computation_checker;
    }


    public function compute(
        BeforeEvent $before_event,
        array &$already_computed_velocity,
        Tracker_Semantic_Status $semantic_status,
        SemanticDone $semantic_done,
        SemanticVelocity $semantic_velocity
    ) {
        if (! $this->computation_checker->shouldComputeCapacity($semantic_status, $semantic_done, $semantic_velocity, $before_event)) {
            return;
        }

        $artifact_id  = $before_event->getArtifact()->getId();
        $changeset    = $before_event->getArtifact()->getLastChangeset();
        $changeset_id = $changeset ? $changeset->getId() : 0;

        if (! isset($already_computed_velocity[$artifact_id][$changeset_id])) {
            $computed_velocity = $this->getComputedVelocity($before_event, $semantic_velocity);

            $already_computed_velocity[$artifact_id][$changeset_id] = $computed_velocity;
        }

        $before_event->forceFieldData($semantic_velocity->getFieldId(), $already_computed_velocity[$artifact_id][$changeset_id]);
    }

    private function getComputedVelocity(BeforeEvent $before_event, SemanticVelocity $semantic_velocity)
    {
        $computed_velocity = $this->calculator->calculate(
            $before_event->getArtifact(),
            $before_event->getUser()
        );

        $this->displayUpdateMessageForUserWhoCanReadField(
            $before_event->getUser(),
            $computed_velocity,
            $semantic_velocity->getVelocityField()
        );

        return $computed_velocity;
    }

    /**
     * @param $field
     * @param $computed_velocity
     */
    private function displayUpdateMessageForUserWhoCanReadField(
        PFUser $user,
        $computed_velocity,
        ?Tracker_FormElement_Field $field = null
    ) {
        if ($field && $field->userCanRead($user)) {
            $GLOBALS['Response']->addFeedback(
                Feedback::INFO,
                sprintf(
                    dgettext(
                        'tuleap-velocity',
                        'The field %s will be automatically set to %s'
                    ),
                    $field->getName(),
                    $computed_velocity
                )
            );
        }
    }
}
