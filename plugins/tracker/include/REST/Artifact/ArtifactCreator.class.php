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

use Tracker\Artifact\Changeset\Value\AddDefaultValuesToFieldsData;
use Tuleap\Tracker\REST\Artifact\Changeset\Value\FieldsDataBuilder;
use Tuleap\Tracker\REST\Artifact\Changeset\Value\FieldsDataFromValuesByFieldBuilder;

class Tracker_REST_Artifact_ArtifactCreator
{
    /** @var FieldsDataBuilder */
    private $fields_data_builder;

    /** @var Tracker_ArtifactFactory */
    private $artifact_factory;

    /** @var TrackerFactory */
    private $tracker_factory;
    private FieldsDataFromValuesByFieldBuilder $values_by_field_builder;
    private AddDefaultValuesToFieldsData $default_values_adder;

    public function __construct(
        FieldsDataBuilder $fields_data_builder,
        Tracker_ArtifactFactory $artifact_factory,
        TrackerFactory $tracker_factory,
        FieldsDataFromValuesByFieldBuilder $values_by_field_builder,
        AddDefaultValuesToFieldsData $default_values_adder,
    ) {
        $this->fields_data_builder     = $fields_data_builder;
        $this->artifact_factory        = $artifact_factory;
        $this->tracker_factory         = $tracker_factory;
        $this->values_by_field_builder = $values_by_field_builder;
        $this->default_values_adder    = $default_values_adder;
    }

    /**
     *
     * @param array $values
     * @return Tuleap\Tracker\REST\Artifact\ArtifactReference
     * @throws \Luracast\Restler\RestException
     */
    public function create(PFUser $user, Tuleap\Tracker\REST\TrackerReference $tracker_reference, array $values, bool $should_visit_be_recorded)
    {
        $tracker     = $this->getTracker($tracker_reference);
        $fields_data = $this->fields_data_builder->getFieldsDataOnCreate($values, $tracker);
        $fields_data = $this->default_values_adder->getUsedFieldsWithDefaultValue($tracker, $fields_data, $user);
        $this->checkUserCanSubmit($user, $tracker);

        return $this->returnReferenceOrError(
            $this->artifact_factory->createArtifact($tracker, $fields_data, $user, '', $should_visit_be_recorded),
            ''
        );
    }

    /**
     *
     * @param array $values
     * @return Tuleap\Tracker\REST\Artifact\ArtifactReference
     * @throws \Luracast\Restler\RestException
     */
    public function createWithValuesIndexedByFieldName(PFUser $user, Tuleap\Tracker\REST\TrackerReference $tracker_reference, array $values)
    {
        $tracker     = $this->getTracker($tracker_reference);
        $fields_data = $this->values_by_field_builder->getFieldsDataOnCreate($values, $tracker);
        $fields_data = $this->default_values_adder->getUsedFieldsWithDefaultValue($tracker, $fields_data, $user);
        $this->checkUserCanSubmit($user, $tracker);

        return $this->returnReferenceOrError(
            $this->artifact_factory->createArtifact($tracker, $fields_data, $user, '', true),
            'by_field'
        );
    }

    private function getTracker(Tuleap\Tracker\REST\TrackerReference $tracker_reference)
    {
        $tracker = $this->tracker_factory->getTrackerById($tracker_reference->id);
        if (! $tracker) {
            throw new \Luracast\Restler\RestException(404, 'Tracker not found');
        }
        return $tracker;
    }

    private function returnReferenceOrError($artifact, $format)
    {
        if ($artifact) {
            return Tuleap\Tracker\REST\Artifact\ArtifactReference::build($artifact, $format);
        } else {
            if ($GLOBALS['Response']->feedbackHasErrors()) {
                throw new \Luracast\Restler\RestException(400, $GLOBALS['Response']->getRawFeedback());
            }
            throw new \Luracast\Restler\RestException(500, 'Unable to create artifact');
        }
    }

    public function checkUserCanSubmit(PFUser $user, Tracker $tracker)
    {
        if (! $tracker->userCanSubmitArtifact($user)) {
            throw new \Luracast\Restler\RestException(403, dgettext('tuleap-tracker', 'You can\'t submit an artifact because you do not have the right to submit all required fields'));
        }
    }
}
