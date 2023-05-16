<?php
/**
 * Copyright (c) Enalean, 2014-Present. All Rights Reserved.
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

namespace Tuleap\TestManagement\REST\v1\DefinitionRepresentations;

use PFUser;
use Tracker_Artifact_Changeset;
use Tracker_FormElementFactory;
use Tuleap\REST\JsonCast;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\REST\Artifact\ArtifactRepresentation;

/**
 * @psalm-immutable
 */
class MinimalDefinitionRepresentation
{
    /**
     * @var int ID of the artifact
     *
     * @psalm-readonly
     */
    public $id;

    /**
     * @var String
     *
     * @psalm-readonly
     */
    public $uri;

    /**
     * @var String
     *
     * @psalm-readonly
     */
    public $summary;

    /**
     * @var string | null
     *
     * @psalm-readonly
     */
    public $category;

    /**
     * @var string
     *
     * @psalm-readonly
     */
    public $automated_tests;

    /**
     * @var ArtifactRepresentation {@type ArtifactRepresentation}
     */
    public readonly ArtifactRepresentation $artifact;

    public function __construct(
        Artifact $artifact,
        ArtifactRepresentation $artifact_representation,
        Tracker_FormElementFactory $form_element_factory,
        PFUser $user,
        ?Tracker_Artifact_Changeset $changeset = null,
    ) {
        $tracker_id = $artifact->getTrackerId();
        $this->id   = JsonCast::toInt($artifact->getId());
        $this->uri  = DefinitionRepresentation::ROUTE . '/' . $this->id;

        $changeset = $changeset ?: self::getArtifactLastChangeset($artifact);

        $this->summary         = DefinitionRepresentationBuilder::getTextChangesetValue(
            $form_element_factory,
            $tracker_id,
            $user,
            $artifact,
            $changeset,
            DefinitionRepresentation::FIELD_SUMMARY
        );
        $this->category        = self::getCategory($form_element_factory, $tracker_id, $user, $changeset);
        $this->automated_tests = DefinitionRepresentationBuilder::getTextChangesetValue(
            $form_element_factory,
            $tracker_id,
            $user,
            $artifact,
            $changeset,
            DefinitionRepresentation::FIELD_AUTOMATED_TESTS
        );

        $this->artifact = $artifact_representation;
    }

    private static function getArtifactLastChangeset(Artifact $artifact): ?Tracker_Artifact_Changeset
    {
        return $artifact->getLastChangeset();
    }

    private static function getCategory(
        Tracker_FormElementFactory $form_element_factory,
        int $tracker_id,
        PFUser $user,
        ?Tracker_Artifact_Changeset $changeset,
    ): ?string {
        $field_status = $form_element_factory->getSelectboxFieldByNameForUser(
            $tracker_id,
            DefinitionRepresentation::FIELD_CATEGORY,
            $user
        );

        if (! $field_status || ! $changeset) {
            return null;
        }
        \assert($field_status instanceof \Tracker_FormElement_Field_List);

        return $field_status->getFirstValueFor($changeset);
    }
}
