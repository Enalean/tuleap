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

    /** @var DefinitionSelector */
    private $definition_selector;

    /** @var Tracker_FormElementFactory */
    private $formelement_factory;

    /** @var Config */
    private $config;

    /** @var Tracker_REST_Artifact_ArtifactCreator */
    private $artifact_creator;

    /** @var ExecutionCreator */
    private $execution_creator;

    /** @var Tracker */
    private $campaign_tracker;

    /** @var PFUser */
    private $user;

    private $project_id          = 101;
    private $campaign_tracker_id = 444;


    /**
     * Setup and general stubs
     *
     */

    public function setUp()
    {
        parent::setUp();

        $this->project          = mock('Project');
        $this->campaign_tracker = mock('Tracker');
        $this->user             = aUser()->build();

        $this->project_manager     = mock('ProjectManager');
        $this->tracker_factory     = mock('TrackerFactory');
        $this->definition_selector = mock('Tuleap\\Trafficlights\\REST\\v1\\DefinitionSelector');
        $this->formelement_factory = mock('Tracker_FormElementFactory');
        $this->artifact_creator    = mock('Tracker_REST_Artifact_ArtifactCreator');
        $this->config              = mock('Tuleap\\Trafficlights\\Config');
        $this->execution_creator   = mock('Tuleap\\Trafficlights\\REST\\v1\\ExecutionCreator');

        $this->campaign_creator = new CampaignCreator(
            $this->config,
            $this->project_manager,
            $this->formelement_factory,
            $this->tracker_factory,
            $this->definition_selector,
            $this->artifact_creator,
            $this->execution_creator
        );
    }

    private function stubCampaignTracker()
    {
        stub($this->config)->getCampaignTrackerId()->returns($this->campaign_tracker_id);
        stub($this->project_manager)->getProject()->returns($this->project);
        stub($this->tracker_factory)->getTrackerById()->returns($this->campaign_tracker);
    }

    private function stubCampaignArtifact()
    {
        $campaign_artifact = aMockArtifact()->build();
        $artifact_ref      = mock('Tuleap\\Tracker\\REST\\Artifact\\ArtifactReference');
        stub($this->artifact_creator)->create()->returns($artifact_ref);
        stub($artifact_ref)->getArtifact()->returns($campaign_artifact);
        return $campaign_artifact;
    }

    private function aMockValue($field_id)
    {
        $field_value           = new ArtifactValuesRepresentation();
        $field_value->field_id = $field_id;
        return $field_value;
    }

    /**
     * Tests for createCampaign
     *
     */

    public function itCreatesACampaignWithTheGivenName()
    {
        $this->stubCampaignTracker();
        stub($this->definition_selector)->selectDefinitionIds()->returns(array());

        $campaign_artifact = $this->stubCampaignArtifact();
        $expected_label    = 'Campaign Name';
        $test_selector     = 'all';
        $no_milestone_id   = 0;
        $no_report_id      = 0;

        $label_field_id     = 123;
        $label_field        = aMockField()->withId($label_field_id)->build();
        $label_value        = $this->aMockValue($label_field_id);
        $label_value->value = $expected_label;
        stub($this->formelement_factory)->getUsedFieldByNameForUser()->returnsAt(0, $label_field);

        $status_field_id              = 456;
        $status_field                 = aMockField()->withId($status_field_id)->build();
        $status_value                 = $this->aMockValue($status_field_id);
        $status_value->bind_value_ids = array(0);
        stub($this->formelement_factory)->getUsedFieldByNameForUser()->returnsAt(1, $status_field);

        $link_field_id     = 789;
        $link_field        = aMockField()->withId($link_field_id)->build();
        $link_value        = $this->aMockValue($link_field_id, array());
        $link_value->links = array();
        stub($this->formelement_factory)->getUsedFieldByNameForUser()->returnsAt(2, $link_field);

        $expected_values = array($label_value, $status_value, $link_value);
        expect($this->artifact_creator)->create('*', '*', $expected_values)->once();

        expect($campaign_artifact)->linkArtifact()->never();

        $this->campaign_creator->createCampaign(
            $this->user,
            $this->project_id,
            $expected_label,
            $test_selector,
            $no_milestone_id,
            $no_report_id
        );
    }

    public function itCreatesAnArtifactLinkToMilestoneWhenGivenAMilestoneId()
    {
        $this->stubCampaignTracker();
        stub($this->definition_selector)->selectDefinitionIds()->returns(array());
        stub($this->formelement_factory)->getUsedFieldByNameForUser()->returns(aMockField()->build());

        $campaign_artifact = $this->stubCampaignArtifact();
        $expected_label    = 'Campaign Name';
        $test_selector     = 'all';
        $milestone_id      = 10;
        $no_report_id      = 0;


        expect($campaign_artifact)->linkArtifact($milestone_id, '*')->once();

        $this->campaign_creator->createCampaign(
            $this->user,
            $this->project_id,
            $expected_label,
            $test_selector,
            $milestone_id,
            $no_report_id
        );
    }

    public function itCreatesTestExecutionsForSelectedDefinitions()
    {
        $test_definitions = array("1", "2", "3");

        $this->stubCampaignTracker();
        $this->stubCampaignArtifact();
        stub($this->definition_selector)->selectDefinitionIds()->returns($test_definitions);
        stub($this->formelement_factory)->getUsedFieldByNameForUser()->returns(aMockField()->build());
        stub($this->execution_creator)->createTestExecution()->returns(aMockArtifact()->build());

        $expected_label = 'Campaign Name';
        $test_selector  = 'report';
        $milestone_id   = 10;
        $report_id      = 5;

        expect($this->execution_creator)->createTestExecution()->count(count($test_definitions));

        $this->campaign_creator->createCampaign(
            $this->user,
            $this->project_id,
            $expected_label,
            $test_selector,
            $milestone_id,
            $report_id
        );
    }
}
