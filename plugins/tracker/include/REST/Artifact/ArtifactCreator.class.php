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

class Tracker_REST_Artifact_ArtifactCreator
{

    /** @var Tracker_REST_Artifact_ArtifactValidator */
    private $artifact_validator;

    /** @var Tracker_ArtifactFactory */
    private $artifact_factory;

    /** @var TrackerFactory */
    private $tracker_factory;

    public function __construct(Tracker_REST_Artifact_ArtifactValidator $artifact_validator, Tracker_ArtifactFactory $artifact_factory, TrackerFactory $tracker_factory)
    {
        $this->artifact_validator  = $artifact_validator;
        $this->artifact_factory    = $artifact_factory;
        $this->tracker_factory     = $tracker_factory;
    }

    /**
     *
     * @param array $values
     * @return Tuleap\Tracker\REST\Artifact\ArtifactReference
     * @throws \Luracast\Restler\RestException
     */
    public function create(PFUser $user, Tuleap\Tracker\REST\TrackerReference $tracker_reference, array $values)
    {
        $tracker     = $this->getTracker($tracker_reference);
        $fields_data = $this->artifact_validator->getFieldsDataOnCreate($values, $tracker);
        $fields_data = $this->artifact_validator->getUsedFieldsWithDefaultValue($tracker, $fields_data, $user);
        $this->checkUserCanSubmit($user, $tracker);

        return $this->returnReferenceOrError(
            $this->artifact_factory->createArtifact($tracker, $fields_data, $user, ''),
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
        $fields_data = $this->artifact_validator->getFieldsDataOnCreateFromValuesByField($values, $tracker);
        $fields_data = $this->artifact_validator->getUsedFieldsWithDefaultValue($tracker, $fields_data, $user);
        $this->checkUserCanSubmit($user, $tracker);

        return $this->returnReferenceOrError(
            $this->artifact_factory->createArtifact($tracker, $fields_data, $user, ''),
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
            $reference = new Tuleap\Tracker\REST\Artifact\ArtifactReference();
            $reference->build($artifact, $format);
            return $reference;
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
            throw new \Luracast\Restler\RestException(403, $GLOBALS['Language']->getText('plugin_tracker', 'submit_at_least_one_field'));
        }
    }
}
