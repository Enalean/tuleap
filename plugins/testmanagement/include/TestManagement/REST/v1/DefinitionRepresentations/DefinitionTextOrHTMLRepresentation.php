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
final class DefinitionTextOrHTMLRepresentation extends MinimalDefinitionRepresentation implements DefinitionRepresentation
{
    /**
     * @var string Description in HTML of the test definition
     */
    public $description;
    /**
     * @var string Format of the test definition description. It can be 'text' or 'html'
     */
    public $description_format;
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

    public readonly ArtifactRepresentation $artifact;

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
        string $description_format,
        array $all_requirements,
        ?Tracker_Artifact_Changeset $changeset = null,
    ) {
        parent::__construct($artifact, $form_element_factory, $user, $changeset);

        $this->artifact = $artifact_representation;

        $this->description = self::getTextFieldValueWithCrossReferences(
            $purifier,
            $form_element_factory,
            $user,
            $changeset,
            $artifact,
            self::FIELD_DESCRIPTION
        );

        $this->description_format = $description_format;

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

    private static function getTextFieldValueWithCrossReferences(\Codendi_HTMLPurifier $html_purifier, Tracker_FormElementFactory $form_element_factory, PFUser $user, ?Tracker_Artifact_Changeset $changeset, Artifact $artifact, string $field_shortname): string
    {
        $field_value_text = DefinitionRepresentationBuilder::getTextChangesetValue(
            $form_element_factory,
            $artifact->getTrackerId(),
            $user,
            $artifact,
            $changeset,
            $field_shortname
        );
        if (! $field_value_text) {
            return '';
        }

        return $html_purifier->purifyHTMLWithReferences(
            $field_value_text,
            (int) $artifact->getTracker()->getGroupId()
        );
    }

    private static function getDefinitionRank(\Tracker_Artifact_PriorityManager $artifact_priority_manager, Artifact $artifact): int
    {
        return (int) $artifact_priority_manager->getGlobalRank($artifact->getId());
    }
}
