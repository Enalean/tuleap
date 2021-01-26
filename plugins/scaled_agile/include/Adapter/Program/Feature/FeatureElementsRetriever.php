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

namespace Tuleap\ScaledAgile\Adapter\Program\Feature;

use Tuleap\ScaledAgile\Program\Backlog\Feature\RetrieveFeatures;
use Tuleap\ScaledAgile\Program\Backlog\Feature\FeaturesStore;
use Tuleap\ScaledAgile\Program\Plan\BuildProgram;
use Tuleap\ScaledAgile\REST\v1\FeatureRepresentation;
use Tuleap\Tracker\REST\MinimalTrackerRepresentation;

final class FeatureElementsRetriever implements RetrieveFeatures
{
    /**
     * @var FeaturesStore
     */
    private $features_store;
    /**
     * @var BuildProgram
     */
    private $build_program;
    /**
     * @var \Tracker_ArtifactFactory
     */
    private $artifact_factory;
    /**
     * @var \Tracker_FormElementFactory
     */
    private $form_element_factory;
    /**
     * @var BackgroundColorRetriever
     */
    private $retrieve_background_color;

    public function __construct(
        BuildProgram $build_program,
        FeaturesStore $features_store,
        \Tracker_ArtifactFactory $artifact_factory,
        \Tracker_FormElementFactory $form_element_factory,
        BackgroundColorRetriever $retrieve_background_color
    ) {
        $this->features_store            = $features_store;
        $this->build_program             = $build_program;
        $this->artifact_factory          = $artifact_factory;
        $this->form_element_factory      = $form_element_factory;
        $this->retrieve_background_color = $retrieve_background_color;
    }

    /**
     * @return FeatureRepresentation[]
     *
     * @throws \Tuleap\ScaledAgile\Adapter\Program\Plan\ProgramAccessException
     * @throws \Tuleap\ScaledAgile\Adapter\Program\Plan\ProjectIsNotAProgramException
     */
    public function retrieveFeaturesToBePlanned(int $id, \PFUser $user): array
    {
        $program = $this->build_program->buildExistingProgramProject($id, $user);

        $to_be_planned_artifacts = $this->features_store->searchPlannableFeatures($program);

        $elements = [];
        foreach ($to_be_planned_artifacts as $artifact) {
            $full_artifact = $this->artifact_factory->getArtifactById((int) $artifact['artifact_id']);

            if (! $full_artifact || ! $full_artifact->userCanView($user)) {
                continue;
            }

            $title = $this->form_element_factory->getFieldById($artifact['field_title_id']);
            if (! $title || ! $title->userCanRead($user)) {
                continue;
            }

            $elements[] = new FeatureRepresentation(
                (int) $artifact['artifact_id'],
                $artifact['artifact_title'],
                $full_artifact->getXRef(),
                MinimalTrackerRepresentation::build($full_artifact->getTracker()),
                $this->retrieve_background_color->retrieveBackgroundColor($full_artifact, $user)
            );
        }

        return $elements;
    }
}
