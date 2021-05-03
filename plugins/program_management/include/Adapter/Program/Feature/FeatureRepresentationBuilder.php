<?php
/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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

namespace Tuleap\ProgramManagement\Adapter\Program\Feature;

use PFUser;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\Content\Links\VerifyLinkedUserStoryIsNotPlanned;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\FeatureIdentifier;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\VerifyIsVisibleFeature;
use Tuleap\ProgramManagement\Domain\Program\ProgramIdentifier;
use Tuleap\ProgramManagement\REST\v1\FeatureRepresentation;
use Tuleap\Tracker\REST\MinimalTrackerRepresentation;

class FeatureRepresentationBuilder
{
    /**
     * @var BackgroundColorRetriever
     */
    private $retrieve_background_color;
    /**
     * @var \Tracker_FormElementFactory
     */
    private $form_element_factory;
    /**
     * @var \Tracker_ArtifactFactory
     */
    private $artifact_factory;
    /**
     * @var VerifyIsVisibleFeature
     */
    private $feature_verifier;
    /**
     * @var VerifyLinkedUserStoryIsNotPlanned
     */
    private $user_story_checker;

    public function __construct(
        \Tracker_ArtifactFactory $artifact_factory,
        \Tracker_FormElementFactory $form_element_factory,
        BackgroundColorRetriever $retrieve_background_color,
        VerifyIsVisibleFeature $feature_verifier,
        VerifyLinkedUserStoryIsNotPlanned $user_story_checker
    ) {
        $this->artifact_factory          = $artifact_factory;
        $this->form_element_factory      = $form_element_factory;
        $this->retrieve_background_color = $retrieve_background_color;
        $this->feature_verifier          = $feature_verifier;
        $this->user_story_checker        = $user_story_checker;
    }

    public function buildFeatureRepresentation(
        PFUser $user,
        ProgramIdentifier $program,
        int $artifact_id,
        int $title_field_id,
        string $artifact_title
    ): ?FeatureRepresentation {
        $feature = FeatureIdentifier::fromId($this->feature_verifier, $artifact_id, $user, $program);
        if (! $feature) {
            return null;
        }
        $full_artifact = $this->artifact_factory->getArtifactById($artifact_id);
        if (! $full_artifact) {
            return null;
        }

        $title = $this->form_element_factory->getFieldById($title_field_id);
        if (! $title || ! $title->userCanRead($user)) {
            return null;
        }

        return new FeatureRepresentation(
            $feature->id,
            $artifact_title,
            $full_artifact->getXRef(),
            $full_artifact->getUri(),
            MinimalTrackerRepresentation::build($full_artifact->getTracker()),
            $this->retrieve_background_color->retrieveBackgroundColor($full_artifact, $user),
            $this->user_story_checker->isLinkedToAtLeastOnePlannedUserStory($user, $feature),
            $this->user_story_checker->hasStoryLinked($user, $feature)
        );
    }
}
