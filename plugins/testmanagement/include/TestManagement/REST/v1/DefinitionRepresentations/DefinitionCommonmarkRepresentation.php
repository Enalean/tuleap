<?php
/**
 * Copyright (c) Enalean, 2021 - present. All Rights Reserved.
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
 * along with Tuleap. If not, see < http://www.gnu.org/licenses/>.
 *
 */

declare(strict_types=1);

namespace Tuleap\TestManagement\REST\v1\DefinitionRepresentations;

use Codendi_HTMLPurifier;
use PFUser;
use Tracker_Artifact_Changeset;
use Tracker_FormElementFactory;
use Tuleap\Markdown\ContentInterpretor;
use Tuleap\TestManagement\REST\v1\DefinitionRepresentations\StepDefinitionRepresentations\StepDefinitionFormatNotFoundException;
use Tuleap\TestManagement\REST\v1\DefinitionRepresentations\StepDefinitionRepresentations\StepDefinitionRepresentation;
use Tuleap\TestManagement\REST\v1\DefinitionRepresentations\StepDefinitionRepresentations\StepDefinitionRepresentationBuilder;
use Tuleap\TestManagement\Step\Definition\Field\StepDefinitionChangesetValue;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\REST\Artifact\ArtifactRepresentation;

/**
 * @psalm-immutable
 */
final class DefinitionCommonmarkRepresentation extends MinimalDefinitionRepresentation implements DefinitionRepresentation
{
    /**
     * @var string Description in HTML of the test definition
     */
    public $description;
    /**
     * @var string Format of the test definition description. Here it will be 'html' to avoid API breaking change
     */
    public $description_format = 'html';
    /**
     * @var string The original description written in commonmark syntax
     */
    public $commonmark_description;

    /**
     * @var array {@type StepDefinitionRepresentation} The steps of the test definition
     * @psalm-var StepDefinitionRepresentation[]
     */
    public $steps;

    /**
     * @var ArtifactRepresentation | null One of the artifacts linked to the test (deprecated, use all_requirements instead)
     * @deprecated
     */
    public $requirement;

    /**
     * @var array {@type ArtifactRepresentation}
     */
    public $all_requirements;

    public readonly int $rank;

    /**
     * @param ArtifactRepresentation[] $all_requirements
     * @throws StepDefinitionFormatNotFoundException
     */
    public function __construct(
        \Codendi_HTMLPurifier $purifier,
        ContentInterpretor $interpreter,
        Artifact $artifact,
        ArtifactRepresentation $artifact_representation,
        Tracker_FormElementFactory $form_element_factory,
        \Tracker_Artifact_PriorityManager $artifact_priority_manager,
        PFUser $user,
        array $all_requirements,
        ?Tracker_Artifact_Changeset $changeset = null,
    ) {
        parent::__construct($artifact, $artifact_representation, $form_element_factory, $user, $changeset);

        $this->commonmark_description = self::getPurifiedTextFieldValue(
            $purifier,
            $form_element_factory,
            $user,
            $changeset,
            $artifact,
            self::FIELD_DESCRIPTION
        );

        $description_text_field = DefinitionRepresentationBuilder::getTextChangesetValue(
            $form_element_factory,
            $artifact->getTrackerId(),
            $user,
            $artifact,
            $changeset,
            self::FIELD_DESCRIPTION
        );

        $this->description = self::getCommonmarkContentWithReferences(
            $interpreter,
            $description_text_field,
            $artifact
        );

        $this->all_requirements = $all_requirements;
        $this->requirement      = empty($all_requirements) ? null : $all_requirements[0];

        $this->rank = self::getDefinitionRank($artifact_priority_manager, $artifact);

        $this->steps = [];
        $value       = DefinitionRepresentationBuilder::getFieldValue(
            $form_element_factory,
            $artifact->getTrackerId(),
            $user,
            $artifact,
            $changeset,
            self::FIELD_STEPS
        );
        \assert($value instanceof StepDefinitionChangesetValue || $value === null);
        if (! $value) {
            return;
        }

        foreach ($value->getValue() as $step) {
            $representation = StepDefinitionRepresentationBuilder::build($step, $artifact, $purifier, $interpreter);

            $this->steps[] = $representation;
        }
    }

    private static function getPurifiedTextFieldValue(
        Codendi_HTMLPurifier $html_purifier,
        Tracker_FormElementFactory $form_element_factory,
        PFUser $user,
        ?Tracker_Artifact_Changeset $changeset,
        Artifact $artifact,
        string $field_shortname,
    ): string {
        return $html_purifier->purify(
            DefinitionRepresentationBuilder::getTextChangesetValue(
                $form_element_factory,
                $artifact->getTrackerId(),
                $user,
                $artifact,
                $changeset,
                $field_shortname
            )
        );
    }

    private static function getCommonmarkContentWithReferences(
        ContentInterpretor $interpreter,
        string $commonmark_description,
        Artifact $artifact,
    ): string {
        return $interpreter->getInterpretedContentWithReferences(
            $commonmark_description,
            (int) $artifact->getTracker()->getGroupId()
        );
    }

    private static function getDefinitionRank(\Tracker_Artifact_PriorityManager $artifact_priority_manager, Artifact $artifact): int
    {
        return (int) $artifact_priority_manager->getGlobalRank($artifact->getId());
    }
}
