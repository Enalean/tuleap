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

use PFUser;
use Planning_Milestone;
use Tracker_Artifact;
use Tracker_ArtifactFactory;
use Tracker_FormElementFactory;
use Tuleap\AgileDashboard\Semantic\SemanticDone;
use Tuleap\Velocity\Semantic\SemanticVelocity;

class VelocityRepresentationBuilder
{
    const START_DATE_FIELD_NAME = 'start_date';
    const DURATION_FIELD_NAME   = 'duration';

    /**
     * @var VelocityDao
     */
    private $velocity_dao;
    /**
     * @var Tracker_ArtifactFactory
     */
    private $artifact_factory;
    /**
     * @var Tracker_FormElementFactory
     */
    private $form_element_factory;

    public function __construct(
        VelocityDao $velocity_dao,
        Tracker_ArtifactFactory $artifact_factory,
        Tracker_FormElementFactory $form_element_factory
    ) {
        $this->velocity_dao         = $velocity_dao;
        $this->artifact_factory     = $artifact_factory;
        $this->form_element_factory = $form_element_factory;
    }

    public function buildRepresentations(Planning_Milestone $milestone, PFUser $user)
    {
        $representations = [];

        $backlog_artifacts = $milestone->getLinkedArtifacts($user);
        foreach ($backlog_artifacts as $artifact) {
            $velocity      = SemanticVelocity::load($artifact->getTracker());
            $done_semantic = SemanticDone::load($artifact->getTracker());

            if ($velocity->getVelocityField() && $done_semantic->isDone($artifact->getLastChangeset())) {
                $computed_velocity = $artifact->getLastChangeset()->getValue($velocity->getVelocityField());

                $start_date = $this->getArtifactStartDate($artifact, $user);
                $representations[] = new VelocityRepresentation(
                    $artifact->getTitle(),
                    $start_date,
                    $this->getArtifactDuration($artifact, $user),
                    ($computed_velocity) ? $computed_velocity->getNumeric() : 0
                );
            }
        }

        return $representations;
    }

    private function getArtifactStartDate(Tracker_Artifact $artifact, PFUser $user)
    {
        $field = $this->form_element_factory->getDateFieldByNameForUser(
            $artifact->getTracker(),
            $user,
            self::START_DATE_FIELD_NAME
        );

        if (! $field) {
            return;
        }

        $value = $field->getLastChangesetValue($artifact);
        if (! $value) {
            return;
        }

        return $value->getTimestamp();
    }

    private function getArtifactDuration(Tracker_Artifact $artifact, PFUser $user)
    {
        $field = $this->form_element_factory->getComputableFieldByNameForUser(
            $artifact->getTracker()->getId(),
            self::DURATION_FIELD_NAME,
            $user
        );
        if ($field) {
            return $field->getComputedValue($user, $artifact);
        }
        return 0;
    }
}
