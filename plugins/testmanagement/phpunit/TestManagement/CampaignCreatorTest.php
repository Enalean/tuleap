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

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use ProjectManager;
use TrackerFactory;
use Tracker_FormElementFactory;
use Tracker_REST_Artifact_ArtifactCreator;

require_once __DIR__ . '/../bootstrap.php';

class CampaignCreatorTest extends TestCase
{
    use MockeryPHPUnitIntegration;

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

    public function setUp(): void
    {
        parent::setUp();

        $globals = array_merge([], $GLOBALS);

        $this->project          = Mockery::spy(\Project::class);
        $this->campaign_tracker = Mockery::spy(\Tracker::class);
        $this->user             = Mockery::spy(\PFUser::class);

        $this->project_manager     = Mockery::spy(\ProjectManager::class);
        $this->tracker_factory     = Mockery::spy(\TrackerFactory::class);
        $this->definition_selector = Mockery::spy(\Tuleap\TestManagement\REST\v1\DefinitionSelector::class);
        $this->formelement_factory = Mockery::spy(\Tracker_FormElementFactory::class);
        $this->artifact_creator    = Mockery::spy(\Tracker_REST_Artifact_ArtifactCreator::class);
        $this->config              = Mockery::spy(\Tuleap\TestManagement\Config::class);
        $this->execution_creator   = Mockery::spy(\Tuleap\TestManagement\REST\v1\ExecutionCreator::class);

        $this->campaign_creator = new CampaignCreator(
            $this->config,
            $this->project_manager,
            $this->formelement_factory,
            $this->tracker_factory,
            $this->definition_selector,
            $this->artifact_creator,
            $this->execution_creator
        );

        $GLOBALS = $globals;
    }

    private function stubCampaignTracker()
    {
        $this->config->shouldReceive('getCampaignTrackerId')->andReturn($this->campaign_tracker_id);
        $this->project_manager->shouldReceive('getProject')->andReturn($this->project);
        $this->tracker_factory->shouldReceive('getTrackerById')->andReturn($this->campaign_tracker);
    }

    private function stubCampaignArtifact()
    {
        $campaign_artifact = Mockery::spy(\Tracker_Artifact::class);
        $artifact_ref      = Mockery::spy(\Tuleap\Tracker\REST\Artifact\ArtifactReference::class);

        $artifact_ref->shouldReceive('getArtifact')->andReturn($campaign_artifact);
        $this->artifact_creator->shouldReceive('create')->andReturn($artifact_ref);

        return $campaign_artifact;
    }

    /**
     * Tests for createCampaign
     *
     */

    public function testItCreatesACampaignWithTheGivenName()
    {
        $this->stubCampaignTracker();
        $this->definition_selector->shouldReceive('selectDefinitions')->andReturn([]);

        $this->stubCampaignArtifact();
        $expected_label    = 'Campaign Name';
        $test_selector     = 'all';
        $no_milestone_id   = 0;
        $no_report_id      = 0;

        $label_field_id = 123;
        $label_field    = Mockery::spy(\Tracker_FormElement_Field::class);
        $label_field->shouldReceive('getId')->andReturn($label_field_id);
        $this->formelement_factory->shouldReceive('getUsedFieldByNameForUser')->andReturn($label_field);

        $status_field_id = 456;
        $status_field    = Mockery::spy(\Tracker_FormElement_Field::class);
        $status_field->shouldReceive('getId')->andReturn($status_field_id);
        $this->formelement_factory->shouldReceive('getUsedFieldByNameForUser')->andReturn($status_field);

        $link_field_id = 789;
        $link_field    = Mockery::spy(\Tracker_FormElement_Field::class);
        $link_field->shouldReceive('getId')->andReturn($link_field_id);
        $this->formelement_factory->shouldReceive('getUsedFieldByNameForUser')->andReturn($link_field);

        $this->artifact_creator->shouldReceive('linkArtifact')->never();

        $this->campaign_creator->createCampaign(
            $this->user,
            $this->project_id,
            $expected_label,
            $test_selector,
            $no_milestone_id,
            $no_report_id
        );
    }

    public function testItCreatesAnArtifactLinkToMilestoneWhenGivenAMilestoneId()
    {
        $this->stubCampaignTracker();
        $campaign_artifact = $this->stubCampaignArtifact();
        $this->definition_selector->shouldReceive('selectDefinitions')->andReturn([]);
        $this->formelement_factory->shouldReceive('getUsedFieldByNameForUser')->andReturn(
            Mockery::spy(\Tracker_FormElement_Field::class)
        );

        $this->stubCampaignArtifact();
        $expected_label    = 'Campaign Name';
        $test_selector     = 'all';
        $milestone_id      = 10;
        $no_report_id      = 0;

        $campaign_artifact->shouldReceive('linkArtifact')->with($milestone_id, Mockery::any())->once();

        $this->campaign_creator->createCampaign(
            $this->user,
            $this->project_id,
            $expected_label,
            $test_selector,
            $milestone_id,
            $no_report_id
        );
    }

    public function testItCreatesTestExecutionsForSelectedDefinitions()
    {
        $definition_1 = Mockery::mock(\Tracker_Artifact::class);
        $definition_1->allows()->getId()->andReturn("1");
        $definition_2 = Mockery::mock(\Tracker_Artifact::class);
        $definition_2->allows()->getId()->andReturn("2");
        $definition_3 = Mockery::mock(\Tracker_Artifact::class);
        $definition_3->allows()->getId()->andReturn("3");
        $test_definitions = array($definition_1, $definition_2, $definition_3);

        $this->stubCampaignTracker();
        $this->stubCampaignArtifact();

        $this->definition_selector->shouldReceive('selectDefinitions')->andReturn($test_definitions);
        $this->formelement_factory->shouldReceive('getUsedFieldByNameForUser')->andReturn(
            Mockery::spy(\Tracker_FormElement_Field::class)
        );

        $expected_label = 'Campaign Name';
        $test_selector  = 'report';
        $milestone_id   = 10;
        $report_id      = 5;

        $this->execution_creator->shouldReceive('createTestExecution')
            ->times(count($test_definitions))
            ->andReturn(Mockery::spy(\Tracker_Artifact::class));


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
