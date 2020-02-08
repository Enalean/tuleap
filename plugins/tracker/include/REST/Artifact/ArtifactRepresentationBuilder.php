<?php
/**
 * Copyright (c) Enalean, 2013-Present. All Rights Reserved.
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

namespace Tuleap\Tracker\REST\Artifact;

use Closure;
use PFUser;
use Tracker_Artifact;
use Tracker_Artifact_Changeset;
use Tracker_Artifact_PaginatedArtifacts;
use Tracker_ArtifactFactory;
use Tracker_FormElement_Field;
use Tracker_FormElement_Field_Alphanum;
use Tracker_FormElementFactory;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Nature\NatureDao;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Nature\NaturePresenter;
use Tuleap\Tracker\REST\ChangesetRepresentationCollection;
use Tuleap\Tracker\REST\MinimalTrackerRepresentation;
use Tuleap\Tracker\REST\TrackerRepresentation;

class ArtifactRepresentationBuilder
{
    /**
     * @var Tracker_ArtifactFactory
     */
    private $artifact_factory;

    /**
     * @var NatureDao
     */
    private $nature_dao;

    /** @var Tracker_FormElementFactory */
    private $formelement_factory;

    public function __construct(
        Tracker_FormElementFactory $formelement_factory,
        Tracker_ArtifactFactory $artifact_factory,
        NatureDao $nature_dao
    ) {
        $this->formelement_factory = $formelement_factory;
        $this->artifact_factory    = $artifact_factory;
        $this->nature_dao          = $nature_dao;
    }

    /**
     * Return an artifact snapshot representation
     *
     * @return ArtifactRepresentation
     */
    public function getArtifactRepresentationWithFieldValues(PFUser $user, Tracker_Artifact $artifact, TrackerRepresentation $tracker_representation)
    {
        $artifact_representation = new ArtifactRepresentation();
        $artifact_representation->build(
            $user,
            $artifact,
            $this->getFieldsValues($user, $artifact),
            null,
            $tracker_representation
        );

        return $artifact_representation;
    }

    /**
     * Return an artifact snapshot representation
     *
     * @return ArtifactRepresentation
     */
    public function getArtifactRepresentationWithFieldValuesByFieldValues(PFUser $user, Tracker_Artifact $artifact, TrackerRepresentation $tracker_representation)
    {
        $artifact_representation = new ArtifactRepresentation();
        $artifact_representation->build(
            $user,
            $artifact,
            null,
            $this->getFieldValuesIndexedByName($user, $artifact),
            $tracker_representation
        );

        return $artifact_representation;
    }

    /**
     * Return an artifact snapshot representation
     *
     * @return ArtifactRepresentation
     */
    public function getArtifactRepresentationWithFieldValuesInBothFormat(PFUser $user, Tracker_Artifact $artifact, TrackerRepresentation $tracker_representation)
    {
        $artifact_representation = new ArtifactRepresentation();
        $artifact_representation->build(
            $user,
            $artifact,
            $this->getFieldsValues($user, $artifact),
            $this->getFieldValuesIndexedByName($user, $artifact),
            $tracker_representation
        );

        return $artifact_representation;
    }

    /**
     * Return an artifact snapshot representation
     *
     * @return ArtifactRepresentation
     */
    public function getArtifactRepresentation(PFUser $user, Tracker_Artifact $artifact)
    {
        $tracker_representation  = new MinimalTrackerRepresentation();
        $tracker_representation->build($artifact->getTracker());

        $artifact_representation = new ArtifactRepresentation();
        $artifact_representation->build(
            $user,
            $artifact,
            null,
            null,
            $tracker_representation
        );

        return $artifact_representation;
    }

    private function getFieldsValues(PFUser $user, Tracker_Artifact $artifact)
    {
        $changeset = $artifact->getLastChangeset();
        return $this->mapAndFilter(
            $this->formelement_factory->getUsedFieldsForREST($artifact->getTracker()),
            $this->getFieldsValuesFilter($user, $changeset)
        );
    }

    private function getFieldValuesIndexedByName(PFUser $user, Tracker_Artifact $artifact)
    {
        $changeset = $artifact->getLastChangeset();
        $values    = [];

        foreach ($this->formelement_factory->getUsedFieldsForREST($artifact->getTracker()) as $field) {
            if (! $field->userCanRead($user) || ! $field instanceof Tracker_FormElement_Field_Alphanum) {
                continue;
            }
            $field_value               = $field->getRESTValue($user, $changeset);
            $values[$field->getName()] = $field_value;
        }

        return $values;
    }

    /**
     * Given a collection and a closure, apply on all elements, filter out the
     * empty results and normalize the array
     *
     * @param array   $collection
     * @return array
     */
    private function mapAndFilter(array $collection, Closure $function)
    {
        $array = [];
        foreach ($collection as $item) {
            $array[] = $function($item);
        }

        return array_values(
            array_filter(
                $array
            )
        );
    }

    private function mapFilterSlice(array $collection, $offset, $limit, Closure $function)
    {
        return $this->mapAndFilter(
            array_slice($collection, $offset, $limit),
            $function
        );
    }

    private function getFieldsValuesFilter(PFUser $user, Tracker_Artifact_Changeset $changeset)
    {
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
     * @param string           $fields
     * @param int              $offset
     * @param int              $limit
     *
     * @return ChangesetRepresentationCollection
     */
    public function getArtifactChangesetsRepresentation(
        PFUser $user,
        Tracker_Artifact $artifact,
        $fields,
        $offset,
        $limit,
        $reverse_order
    ) {
        $all_changesets = $artifact->getChangesets();

        if ($reverse_order) {
            $all_changesets = array_reverse($all_changesets);
        }

        return new ChangesetRepresentationCollection(
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

    public function getArtifactRepresentationCollection(
        PFUser $user,
        Tracker_Artifact $artifact_id,
        $nature,
        $direction,
        $offset,
        $limit
    ) {
        if ($direction === NaturePresenter::REVERSE_LABEL) {
            $linked_artifacts_ids = $this->nature_dao->getReverseLinkedArtifactIds(
                $artifact_id->getId(),
                $nature,
                $limit,
                $offset
            );
        } else {
            $linked_artifacts_ids = $this->nature_dao->getForwardLinkedArtifactIds(
                $artifact_id->getId(),
                $nature,
                $limit,
                $offset
            );
        }

        $total_size = (int) $this->nature_dao->foundRows();
        $artifacts  = [];
        foreach ($linked_artifacts_ids as $linked_artifact_id) {
            $artifact = $this->artifact_factory->getArtifactByIdUserCanView($user, $linked_artifact_id);
            if ($artifact !== null) {
                $artifacts[] = $artifact;
            }
        }

        return new Tracker_Artifact_PaginatedArtifacts(
            $artifacts,
            $total_size
        );
    }
}
