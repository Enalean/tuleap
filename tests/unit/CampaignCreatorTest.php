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

use TuleapTestCase;
use ProjectManager;
use TrackerFactory;
use Tracker_FormElementFactory;
use Tracker_REST_Artifact_ArtifactCreator;
use Tracker_REST_Artifact_ArtifactValidator;
use Tuleap\Tracker\REST\v1\ArtifactValuesRepresentation;

require_once dirname(__FILE__) .'/bootstrap.php';

class CampaignCreatorTest extends TuleapTestCase
{
    /** @var CampaignCreator */
    private $campaign_creator;

    /** @var ProjectManager */
    private $project_manager;

    /** @var TrackerFactory */
    private $tracker_factory;

    /** @var Tracker_FormElementFactory */
    private $formelement_factory;

    /** @var Config */
    private $config;

    /** @var Tracker_REST_Artifact_ArtifactCreator */
    private $artifact_creator;

    /** @var Tracker */
    private $campaign_tracker;

    /** @var PFUser */
    private $user;

    private $project_id          = 101;
    private $campaign_tracker_id = 444;

    public function setUp()
    {
        parent::setUp();

        $this->project          = mock('Project');
        $this->campaign_tracker = mock('Tracker');
        $this->user             = aUser()->build();

        $this->project_manager     = mock('ProjectManager');
        $this->tracker_factory     = mock('TrackerFactory');
        $this->formelement_factory = mock('Tracker_FormElementFactory');
        $this->artifact_creator    = mock('Tracker_REST_Artifact_ArtifactCreator');
        $this->config              = mock('Tuleap\\Trafficlights\\Config');
        $execution_creator         = mock('Tuleap\\Trafficlights\\REST\\v1\\ExecutionCreator');

        $this->campaign_creator = new CampaignCreator(
            $this->formelement_factory,
            $this->config,
            $this->project_manager,
            $this->tracker_factory,
            $this->artifact_creator,
            $execution_creator
        );
    }

    /**
     * Tests for createCampaign
     *
     */
    public function itCreatesACampaignWithTheGivenName()
    {
        stub($this->config)->getCampaignTrackerId()->returns($this->campaign_tracker_id);
        stub($this->project_manager)->getProject()->returns($this->project);
        stub($this->tracker_factory)->getTrackerById()->returns($this->campaign_tracker);

        $expected_label  = 'Campaign Name';
        $no_milestone_id = 0;

        $label_field_id        = 123;
        $label_value           = new ArtifactValuesRepresentation();
        $label_value->field_id = $label_field_id;
        $label_value->value    = $expected_label;
        $label_field           = aMockField()->withId($label_field_id)->build();
        stub($this->formelement_factory)->getUsedFieldByNameForUser()->returnsAt(0, $label_field);

        $status_field_id              = 456;
        $status_value                 = new ArtifactValuesRepresentation();
        $status_value->field_id       = $status_field_id;
        $status_value->bind_value_ids = array(0);
        $status_field                 = aMockField()->withId($status_field_id)->build();
        stub($this->formelement_factory)->getUsedFieldByNameForUser()->returnsAt(1, $status_field);

        $expected_values = array($label_value, $status_value);
        expect($this->artifact_creator)->create('*', '*', $expected_values)->once();

        $artifact_ref      = mock('Tuleap\\Tracker\\REST\\Artifact\\ArtifactReference');
        $campaign_artifact = mock('Tracker_Artifact');
        stub($this->artifact_creator)->create()->returns($artifact_ref);
        stub($artifact_ref)->getArtifact()->returns($campaign_artifact);

        expect($campaign_artifact)->linkArtifact()->never();

        $this->campaign_creator->createCampaign($this->user, $this->project_id, $expected_label, $no_milestone_id);
    }

    public function itCreatesAnArtifactLinkToMilestoneWhenGivenAMilestoneId()
    {
        stub($this->config)->getCampaignTrackerId()->returns($this->campaign_tracker_id);
        stub($this->project_manager)->getProject()->returns($this->project);
        stub($this->tracker_factory)->getTrackerById()->returns($this->campaign_tracker);
        stub($this->formelement_factory)->getUsedFieldByNameForUser()->returns(aMockField()->build());

        $expected_label = 'Campaign Name';
        $milestone_id   = 10;

        $artifact_ref      = mock('Tuleap\\Tracker\\REST\\Artifact\\ArtifactReference');
        $campaign_artifact = mock('Tracker_Artifact');
        stub($this->artifact_creator)->create()->returns($artifact_ref);
        stub($artifact_ref)->getArtifact()->returns($campaign_artifact);

        expect($campaign_artifact)->linkArtifact($milestone_id, '*')->once();

        $this->campaign_creator->createCampaign($this->user, $this->project_id, $expected_label, $milestone_id);
    }
}


