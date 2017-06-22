<?php
/**
 * Copyright (c) Enalean, 2017. All Rights Reserved.
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

namespace Tuleap\Trafficlights\REST\v1;

use Luracast\Restler\RestException;
use PFUser;
use Tracker;
use Tracker_FormElementFactory;
use Tracker_Artifact;
use Tracker_Artifact_Changeset;
use Tracker_REST_Artifact_ArtifactUpdater;
use Tuleap\Tracker\REST\v1\ArtifactValuesRepresentation;
use Tuleap\Trafficlights\LabelFieldNotFoundException;

class CampaignUpdater
{

    /** @var Tracker_FormElementFactory */
    private $formelement_factory;

    /** @var Tracker_REST_Artifact_ArtifactUpdater */
    private $artifact_updater;

    public function __construct(
        Tracker_FormElementFactory $formelement_factory,
        Tracker_REST_Artifact_ArtifactUpdater $artifact_updater
    ) {
        $this->formelement_factory = $formelement_factory;
        $this->artifact_updater    = $artifact_updater;
    }

    /**
     * @param PFUser            $user     The user trying to update the campaign
     * @param Tracker_Artifact  $campaign Campaign to update
     * @param string            $label    New label of the campaign
     *
     * @return Tracker_Artifact_Changeset
     *
     * @throws Tracker_Exception
     * @throws Tracker_NoChangeException
     * @throws Tracker_FormElement_InvalidFieldException
     * @throws Tracker_FormElement_InvalidFieldValueException
     * @throws Tracker_ChangesetNotCreatedException
     * @throws Tracker_CommentNotStoredException
     * @throws Tracker_AfterSaveException
     * @throws Tracker_ChangesetCommitException
     * @throws LabelFieldNotFoundException
     */
    public function updateCampaign(PFUser $user, Tracker_Artifact $campaign, $label)
    {
        $tracker             = $campaign->getTracker();
        $values              = $this->getFieldValuesForCampaignArtifactUpdate($tracker, $user, $label);
        $artifact_changeset  = $this->artifact_updater->update($user, $campaign, $values);

        return $artifact_changeset;
    }

    /**
     * @throws LabelFieldNotFoundException
     */
    private function getFieldValuesForCampaignArtifactUpdate(
        Tracker $tracker,
        PFUser $user,
        $label
    ) {
        $label_field  = $this->getLabelField($tracker, $user);

        $label_value           = new ArtifactValuesRepresentation();
        $label_value->field_id = (int)$label_field->getId();
        $label_value->value    = $label;

        return array($label_value);
    }

    /**
     * @throws LabelFieldNotFoundException
     */
    private function getLabelField(Tracker $tracker, PFUser $user) {
        $field = $this->formelement_factory->getUsedFieldByNameForUser(
            $tracker->getId(),
            CampaignRepresentation::FIELD_NAME,
            $user
        );
        if (! $field) {
            throw new LabelFieldNotFoundException($tracker);
        }

        return $field;
    }
}
