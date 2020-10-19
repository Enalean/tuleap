<?php
/**
 * Copyright (c) Enalean, 2014-present. All Rights Reserved.
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

use PFUser;
use Tracker_Artifact_Changeset;
use Tracker_Artifact_ChangesetValue_Text;
use Tracker_FormElementFactory;
use Tuleap\TestManagement\Step\Definition\Field\StepDefinitionChangesetValue;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\REST\Artifact\ArtifactRepresentation;
use Tuleap\Tracker\REST\MinimalTrackerRepresentation;

/**
 * @psalm-immutable
 */
class DefinitionRepresentation extends MinimalDefinitionRepresentation
{
    public const FIELD_DESCRIPTION = 'details';
    public const FIELD_STEPS       = 'steps';

    /**
     * @var String
     */
    public $description;

    /**
     * @var array {@type StepDefinitionRepresentation}
     */
    public $steps;

    /**
     * @var ArtifactRepresentation | null
     */
    public $requirement;

    public function __construct(
        \Codendi_HTMLPurifier $purifier,
        Artifact $artifact,
        Tracker_FormElementFactory $form_element_factory,
        PFUser $user,
        ?Tracker_Artifact_Changeset $changeset = null,
        ?Artifact $requirement = null
    ) {
        parent::__construct($artifact, $form_element_factory, $user, $changeset);

        $this->description = self::getTextFieldValueWithCrossReferences(
            $purifier,
            $form_element_factory,
            $user,
            $changeset,
            $artifact,
            self::FIELD_DESCRIPTION
        );

        $artifact_representation = null;

        if ($requirement) {
            $requirement_tracker_representation = self::getMinimalTrackerRepresentation($requirement);

            $artifact_representation = ArtifactRepresentation::build($user, $requirement, [], [], $requirement_tracker_representation);
        }

        $this->requirement = $artifact_representation;

        $this->steps = [];
        $value = self::getFieldValue(
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
            $representation = StepDefinitionRepresentation::build($step, $purifier, $artifact);

            $this->steps[] = $representation;
        }
    }

    private static function getTextFieldValueWithCrossReferences(\Codendi_HTMLPurifier $html_purifier, Tracker_FormElementFactory $form_element_factory, PFUser $user, ?Tracker_Artifact_Changeset $changeset, Artifact $artifact, string $field_shortname): string
    {
        $field_value = self::getFieldValue(
            $form_element_factory,
            $artifact->getTrackerId(),
            $user,
            $artifact,
            $changeset,
            $field_shortname
        );
        assert($field_value instanceof Tracker_Artifact_ChangesetValue_Text);
        if (! $field_value) {
            return '';
        }

        return $html_purifier->purifyHTMLWithReferences($field_value->getText(), $artifact->getTracker()->getGroupId());
    }

    private static function getMinimalTrackerRepresentation(Artifact $artifact): MinimalTrackerRepresentation
    {
        return MinimalTrackerRepresentation::build($artifact->getTracker());
    }
}
