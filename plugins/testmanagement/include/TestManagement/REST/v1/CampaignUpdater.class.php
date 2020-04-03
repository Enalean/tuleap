<?php
/**
 * Copyright (c) Enalean, 2017 - 2018. All Rights Reserved.
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
use Tracker;
use Tracker_FormElementFactory;
use Tracker_REST_Artifact_ArtifactUpdater;
use Tuleap\TestManagement\Campaign\Campaign;
use Tuleap\TestManagement\Campaign\CampaignSaver;
use Tuleap\TestManagement\LabelFieldNotFoundException;
use Tuleap\Tracker\REST\v1\ArtifactValuesRepresentation;

class CampaignUpdater
{

    /** @var Tracker_FormElementFactory */
    private $formelement_factory;

    /** @var Tracker_REST_Artifact_ArtifactUpdater */
    private $artifact_updater;
    /** @var CampaignSaver */
    private $campaign_saver;

    public function __construct(
        Tracker_FormElementFactory $formelement_factory,
        Tracker_REST_Artifact_ArtifactUpdater $artifact_updater,
        CampaignSaver $campaign_saver
    ) {
        $this->formelement_factory = $formelement_factory;
        $this->artifact_updater    = $artifact_updater;
        $this->campaign_saver      = $campaign_saver;
    }

    /**
     * @param PFUser   $user     The user trying to update the campaign
     * @param Campaign $campaign Campaign to update
     *
     * @return void
     *
     * @throws LabelFieldNotFoundException
     * @throws \Luracast\Restler\RestException
     * @throws \Tracker_AfterSaveException
     * @throws \Tracker_ChangesetCommitException
     * @throws \Tracker_ChangesetNotCreatedException
     * @throws \Tracker_CommentNotStoredException
     * @throws \Tracker_Exception
     * @throws \Tracker_FormElement_InvalidFieldException
     * @throws \Tracker_FormElement_InvalidFieldValueException
     */
    public function updateCampaign(
        PFUser $user,
        Campaign $campaign
    ) {
        $this->campaign_saver->save($campaign);

        $artifact = $campaign->getArtifact();
        $tracker  = $artifact->getTracker();
        $values   = $this->getFieldValuesForCampaignArtifactUpdate($tracker, $user, $campaign->getLabel());

        $this->artifact_updater->update($user, $artifact, $values);
    }

    /**
     * @throws LabelFieldNotFoundException
     *
     * @return ArtifactValuesRepresentation[]
     *
     * @psalm-return array{0: ArtifactValuesRepresentation}
     */
    private function getFieldValuesForCampaignArtifactUpdate(
        Tracker $tracker,
        PFUser $user,
        string $label
    ): array {
        $label_field = $this->getLabelField($tracker, $user);

        $label_value           = new ArtifactValuesRepresentation();
        $label_value->field_id = (int) $label_field->getId();
        $label_value->value    = $label;

        return [$label_value];
    }

    /**
     * @throws LabelFieldNotFoundException
     *
     */
    private function getLabelField(Tracker $tracker, PFUser $user): \Tracker_FormElement_Field
    {
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
