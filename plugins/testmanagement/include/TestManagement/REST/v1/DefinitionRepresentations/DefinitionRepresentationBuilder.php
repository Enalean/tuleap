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

namespace Tuleap\TestManagement\REST\v1\DefinitionRepresentations;

use PFUser;
use Tracker_Artifact_Changeset;
use Tracker_Artifact_ChangesetValue_Text;
use Tracker_FormElementFactory;
use Tuleap\Markdown\ContentInterpretor;
use Tuleap\TestManagement\ConfigConformanceValidator;
use Tuleap\TestManagement\REST\v1\DefinitionRepresentations\StepDefinitionRepresentations\StepDefinitionFormatNotFoundException;
use Tuleap\TestManagement\REST\v1\RequirementRetriever;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\REST\Artifact\ArtifactRepresentation;
use Tuleap\Tracker\REST\Artifact\ArtifactRepresentationBuilder;
use Tuleap\Tracker\REST\Artifact\StatusValueRepresentation;
use Tuleap\Tracker\REST\MinimalTrackerRepresentation;

class DefinitionRepresentationBuilder
{
    /**
     * @var Tracker_FormElementFactory
     */
    private $tracker_form_element_factory;

    /**
     * @var ConfigConformanceValidator
     */
    private $conformance_validator;

    /**
     * @var RequirementRetriever
     */
    private $requirement_retriever;
    /**
     * @var \Codendi_HTMLPurifier
     */
    private $purifier;
    /**
     * @var ContentInterpretor
     */
    private $interpreter;

    public function __construct(
        Tracker_FormElementFactory $tracker_form_element_factory,
        ConfigConformanceValidator $conformance_validator,
        RequirementRetriever $requirement_retriever,
        \Codendi_HTMLPurifier $purifier,
        ContentInterpretor $interpreter,
        private readonly ArtifactRepresentationBuilder $artifact_representation_builder,
        private readonly \Tracker_Artifact_PriorityManager $artifact_priority_manager,
    ) {
        $this->tracker_form_element_factory = $tracker_form_element_factory;
        $this->conformance_validator        = $conformance_validator;
        $this->requirement_retriever        = $requirement_retriever;
        $this->purifier                     = $purifier;
        $this->interpreter                  = $interpreter;
    }

    /**
     * @throws StepDefinitionFormatNotFoundException
     * @throws DefinitionDescriptionFormatNotFoundException
     */
    public function getDefinitionRepresentation(PFUser $user, Artifact $definition_artifact, ?Tracker_Artifact_Changeset $changeset): DefinitionRepresentation
    {
        $definition_artifact_representation = $this->artifact_representation_builder->getArtifactRepresentation(
            $user,
            $definition_artifact,
            StatusValueRepresentation::buildFromArtifact($definition_artifact, $user),
        );

        return $this->buildDefinitionRepresentation(
            $user,
            $definition_artifact,
            $changeset,
            $definition_artifact_representation,
        );
    }

    /**
     * @throws StepDefinitionFormatNotFoundException
     * @throws DefinitionDescriptionFormatNotFoundException
     */
    public function getDefinitionRepresentationWithFullArtifactDefinition(PFUser $user, Artifact $definition_artifact, ?Tracker_Artifact_Changeset $changeset): DefinitionRepresentation
    {
        $definition_artifact_representation = $this->artifact_representation_builder->getArtifactRepresentationWithFieldValues(
            $user,
            $definition_artifact,
            MinimalTrackerRepresentation::build($definition_artifact->getTracker()),
            StatusValueRepresentation::buildFromArtifact($definition_artifact, $user),
        );

        return $this->buildDefinitionRepresentation(
            $user,
            $definition_artifact,
            $changeset,
            $definition_artifact_representation,
        );
    }

    /**
     * @throws StepDefinitionFormatNotFoundException
     * @throws DefinitionDescriptionFormatNotFoundException
     */
    private function buildDefinitionRepresentation(
        PFUser $user,
        Artifact $definition_artifact,
        ?Tracker_Artifact_Changeset $changeset,
        ArtifactRepresentation $definition_artifact_representation,
    ): DefinitionRepresentation {
        $all_requirements = array_map(
            static fn(Artifact $requirement): ArtifactRepresentation => ArtifactRepresentation::build(
                $user,
                $requirement,
                [],
                [],
                MinimalTrackerRepresentation::build($requirement->getTracker()),
                StatusValueRepresentation::buildFromArtifact($definition_artifact, $user)
            ),
            $this->requirement_retriever->getAllRequirementsForDefinition($definition_artifact, $user)
        );

        $changeset = $changeset ?: $definition_artifact->getLastChangeset();

        $description_text_field = self::getTextField(
            $this->tracker_form_element_factory,
            $definition_artifact->getTrackerId(),
            $user,
            $definition_artifact,
            $changeset,
            DefinitionRepresentation::FIELD_DESCRIPTION
        );

        if (! $description_text_field) {
            return new DefinitionTextOrHTMLRepresentation(
                $this->purifier,
                $this->interpreter,
                $definition_artifact,
                $definition_artifact_representation,
                $this->tracker_form_element_factory,
                $this->artifact_priority_manager,
                $user,
                Tracker_Artifact_ChangesetValue_Text::TEXT_CONTENT,
                $all_requirements,
                $changeset,
            );
        }

        switch ($description_text_field->getFormat()) {
            case Tracker_Artifact_ChangesetValue_Text::TEXT_CONTENT:
            case Tracker_Artifact_ChangesetValue_Text::HTML_CONTENT:
                return new DefinitionTextOrHTMLRepresentation(
                    $this->purifier,
                    $this->interpreter,
                    $definition_artifact,
                    $definition_artifact_representation,
                    $this->tracker_form_element_factory,
                    $this->artifact_priority_manager,
                    $user,
                    $description_text_field->getFormat(),
                    $all_requirements,
                    $changeset,
                );
            case Tracker_Artifact_ChangesetValue_Text::COMMONMARK_CONTENT:
                return new DefinitionCommonmarkRepresentation(
                    $this->purifier,
                    $this->interpreter,
                    $definition_artifact,
                    $definition_artifact_representation,
                    $this->tracker_form_element_factory,
                    $this->artifact_priority_manager,
                    $user,
                    $all_requirements,
                    $changeset,
                );
        }
        throw new DefinitionDescriptionFormatNotFoundException($description_text_field->getFormat());
    }

    public function getMinimalRepresentation(PFUser $user, Artifact $artifact): ?MinimalDefinitionRepresentation
    {
        if (! $this->conformance_validator->isArtifactADefinition($artifact)) {
            return null;
        }

        $changeset = null;
        return new MinimalDefinitionRepresentation(
            $artifact,
            $this->tracker_form_element_factory,
            $user,
            $changeset,
        );
    }

    public static function getFieldValue(
        Tracker_FormElementFactory $form_element_factory,
        int $tracker_id,
        PFUser $user,
        Artifact $artifact,
        ?Tracker_Artifact_Changeset $changeset,
        string $field_shortname,
    ): ?\Tracker_Artifact_ChangesetValue {
        $field = $form_element_factory->getUsedFieldByNameForUser(
            $tracker_id,
            $field_shortname,
            $user
        );

        if (! $field) {
            return null;
        }

        return $artifact->getValue($field, $changeset);
    }

    public static function getTextChangesetValue(
        Tracker_FormElementFactory $form_element_factory,
        int $tracker_id,
        PFUser $user,
        Artifact $artifact,
        ?Tracker_Artifact_Changeset $changeset,
        string $field_shortname,
    ): string {
        $field_value = self::getTextField(
            $form_element_factory,
            $tracker_id,
            $user,
            $artifact,
            $changeset,
            $field_shortname
        );
        if (! $field_value) {
            return '';
        }

        return $field_value->getText();
    }

    public static function getTextField(
        Tracker_FormElementFactory $form_element_factory,
        int $tracker_id,
        PFUser $user,
        Artifact $artifact,
        ?Tracker_Artifact_Changeset $changeset,
        string $field_shortname,
    ): ?Tracker_Artifact_ChangesetValue_Text {
        $field_value = self::getFieldValue(
            $form_element_factory,
            $tracker_id,
            $user,
            $artifact,
            $changeset,
            $field_shortname
        );
        if (! $field_value) {
            return null;
        }
        \assert($field_value instanceof Tracker_Artifact_ChangesetValue_Text);

        return $field_value;
    }
}
