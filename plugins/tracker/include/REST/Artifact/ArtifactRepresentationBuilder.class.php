<?php
/**
 * Copyright (c) Enalean, 2013. All Rights Reserved.
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

class Tracker_REST_Artifact_ArtifactRepresentationBuilder {

    /** @var Tracker_FormElementFactory */
    private $formelement_factory;

    public function __construct(Tracker_FormElementFactory $formelement_factory) {
        $this->formelement_factory = $formelement_factory;
    }

    /**
     * Return an artifact snapshot representation
     *
     * @param PFUser $user
     * @param Tracker_Artifact $artifact
     * @return Tuleap\Tracker\REST\Artifact\ArtifactRepresentation
     */
    public function getArtifactRepresentationWithFieldValues(PFUser $user, Tracker_Artifact $artifact) {
        $artifact_representation = new Tuleap\Tracker\REST\Artifact\ArtifactRepresentation();
        $artifact_representation->build(
            $artifact,
            $this->getFieldsValues($user, $artifact),
            null
        );

        return $artifact_representation;
    }

    /**
     * Return an artifact snapshot representation
     *
     * @param PFUser $user
     * @param Tracker_Artifact $artifact
     * @return Tuleap\Tracker\REST\Artifact\ArtifactRepresentation
     */
    public function getArtifactRepresentationWithFieldValuesByFieldValues(PFUser $user, Tracker_Artifact $artifact) {
        $artifact_representation = new Tuleap\Tracker\REST\Artifact\ArtifactRepresentation();
        $artifact_representation->build(
            $artifact,
            null,
            $this->getFieldValuesIndexedByName($user, $artifact)
        );

        return $artifact_representation;
    }

    /**
     * Return an artifact snapshot representation
     *
     * @param PFUser $user
     * @param Tracker_Artifact $artifact
     * @return Tuleap\Tracker\REST\Artifact\ArtifactRepresentation
     */
    public function getArtifactRepresentationWithFieldValuesInBothFormat(PFUser $user, Tracker_Artifact $artifact) {
        $artifact_representation = new Tuleap\Tracker\REST\Artifact\ArtifactRepresentation();
        $artifact_representation->build(
            $artifact,
            $this->getFieldsValues($user, $artifact),
            $this->getFieldValuesIndexedByName($user, $artifact)
        );

        return $artifact_representation;
    }

    /**
     * Return an artifact snapshot representation
     *
     * @param PFUser $user
     * @param Tracker_Artifact $artifact
     * @return Tuleap\Tracker\REST\Artifact\ArtifactRepresentation
     */
    public function getArtifactRepresentation(Tracker_Artifact $artifact) {
        $artifact_representation = new Tuleap\Tracker\REST\Artifact\ArtifactRepresentation();
        $artifact_representation->build(
            $artifact,
            null,
            null
        );

        return $artifact_representation;
    }

    private function getFieldsValues(PFUser $user, Tracker_Artifact $artifact) {
        $changeset = $artifact->getLastChangeset();
        return $this->mapAndFilter(
            $this->formelement_factory->getUsedFieldsForREST($artifact->getTracker()),
            $this->getFieldsValuesFilter($user, $changeset)
        );
    }

    private function getFieldValuesIndexedByName(PFUser $user, Tracker_Artifact $artifact) {
        $changeset = $artifact->getLastChangeset();
        $values    = array();

        foreach ($this->formelement_factory->getUsedFieldsForREST($artifact->getTracker()) as $field) {
            if (! $field->userCanRead($user) || ! $field instanceof Tracker_FormElement_Field_Alphanum) {
                continue;
            }
            $field_value = $field->getRESTValue($user, $changeset);
            $values[$field->getName()] = $field_value;
        }

        return $values;
    }

    /**
     * Given a collection and a closure, apply on all elements, filter out the
     * empty results and normalize the array
     *
     * @param array $collection
     * @param Closure $function
     * @return array
     */
    private function mapAndFilter(array $collection, Closure $function) {
        $array = array();
        foreach ($collection as $item) {
            $array[] = $function($item);
        }

        return array_values(
            array_filter(
                $array
            )
        );
    }

    private function mapFilterSlice(array $collection, $offset, $limit, Closure $function) {
        return $this->mapAndFilter(
            array_slice($collection, $offset, $limit),
            $function
        );
    }

    private function getFieldsValuesFilter(PFUser $user, Tracker_Artifact_Changeset $changeset) {
        return function (Tracker_FormElement_Field $field) use ($user, $changeset) {
            if ($field->userCanRead($user)) {
                return $field->getRESTValue($user, $changeset);
            }

            return false;
        };
    }

    /**
     * Returns REST representation of artifact history
     *
     * @param PFUser $user
     * @param Tracker_Artifact $artifact
     * @param string $fields
     * @param int $offset
     * @param int $limit
     *
     * @return Tuleap\Tracker\REST\ChangesetRepresentationCollection
     */
    public function getArtifactChangesetsRepresentation(PFUser $user, Tracker_Artifact $artifact, $fields, $offset, $limit, $reverse_order) {
        $all_changesets = $artifact->getChangesets();

        if ($reverse_order) {
            $all_changesets = array_reverse($all_changesets);
        }

        return new Tuleap\Tracker\REST\ChangesetRepresentationCollection(
            $this->mapFilterSlice(
                $all_changesets,
                $offset,
                $limit,
                function (Tracker_Artifact_Changeset $changeset) use ($user, $fields) {
                    return $changeset->getRESTValue($user, $fields);
                }
            ),
            count($all_changesets)
        );
    }
}
