<?php
/**
 * Copyright (c) Enalean, 2017-Present. All Rights Reserved.
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

namespace Tuleap\TestManagement;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Project;
use Tracker;
use TrackerFactory;
use Tuleap\TestManagement\Administration\TrackerChecker;
use Tuleap\TestManagement\Administration\TrackerHasAtLeastOneFrozenFieldsPostActionException;

class FirstConfigCreatorTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;

    /** @var Config */
    private $config;

    /** @var Project */
    private $template;

    /** @var Project */
    private $project;

    /** @var TrackerFactory */
    private $tracker_factory;

    /** @var TrackerChecker */
    private $tracker_checker;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|TestmanagementTrackersCreator
     */
    private $testmanagement_trackers_creator;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|TestmanagementTrackersConfigurator
     */
    private $testmanagement_trackers_configurator;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|Tracker
     */
    private $new_campaign_tracker;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|Tracker
     */
    private $new_definition_tracker;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|Tracker
     */
    private $new_execution_tracker;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|Tracker
     */
    private $new_issue_tracker;
    /**
     * @var FirstConfigCreator
     */
    private $config_creator;

    private $template_id           = 101;
    private $campaign_tracker_id   = 333;
    private $definition_tracker_id = 444;
    private $execution_tracker_id  = 555;
    private $issue_tracker_id      = 666;

    private $project_id                = 102;
    private $new_campaign_tracker_id   = 334;
    private $new_definition_tracker_id = 445;
    private $new_execution_tracker_id  = 556;
    private $new_issue_tracker_id      = 667;
    private $tracker_mapping;

    protected function setUp(): void
    {
        parent::setUp();

        $this->template = Mockery::spy(\Project::class);
        $this->template->shouldReceive('getID')->andReturn($this->template_id);

        $this->project = Mockery::spy(\Project::class);
        $this->project->shouldReceive('getID')->andReturn($this->project_id);

        $this->tracker_mapping = [
            $this->campaign_tracker_id   => $this->new_campaign_tracker_id,
            $this->definition_tracker_id => $this->new_definition_tracker_id,
            $this->execution_tracker_id  => $this->new_execution_tracker_id,
            $this->issue_tracker_id      => $this->new_issue_tracker_id,
        ];

        $this->config          = Mockery::spy(Config::class);
        $this->tracker_factory = Mockery::spy(TrackerFactory::class);

        $this->new_campaign_tracker = Mockery::spy(Tracker::class);
        $this->new_campaign_tracker->shouldReceive('getId')->andReturn($this->new_campaign_tracker_id);

        $this->new_definition_tracker = Mockery::spy(Tracker::class);
        $this->new_definition_tracker->shouldReceive('getId')->andReturn($this->new_definition_tracker_id);

        $this->new_execution_tracker = Mockery::spy(Tracker::class);
        $this->new_execution_tracker->shouldReceive('getId')->andReturn($this->new_execution_tracker_id);

        $this->new_issue_tracker = Mockery::spy(Tracker::class);
        $this->new_issue_tracker->shouldReceive('getId')->andReturn($this->new_issue_tracker_id);

        $this->tracker_checker                      = Mockery::mock(TrackerChecker::class);
        $this->testmanagement_trackers_creator      = Mockery::mock(TestmanagementTrackersCreator::class);
        $this->testmanagement_trackers_configurator = Mockery::mock(TestmanagementTrackersConfigurator::class);

        $this->config_creator = new FirstConfigCreator(
            $this->config,
            $this->tracker_factory,
            $this->tracker_checker,
            $this->testmanagement_trackers_configurator,
            $this->testmanagement_trackers_creator
        );
    }

    public function testItSetsTheProjectTTMTrackerIdsInConfig()
    {
        $this->config->shouldReceive('isConfigNeeded')
            ->with($this->project)
            ->andReturn(true);

        $trackers_from_template = $this->getTrackers();

        $this->config->shouldReceive('getTrackersFromTemplate')->andReturn(
            $trackers_from_template
        );
        $this->testmanagement_trackers_configurator->shouldReceive('configureTestmanagementTracker')->times(4);

        $this->testmanagement_trackers_configurator->shouldReceive('getTrackersConfiguration')->once()->andReturn(
            $this->getNewTrackersConfiguration()
        );

        $this->tracker_checker->shouldReceive('checkTrackers')->once();

        $this->config->shouldReceive('setProjectConfiguration')
            ->with(
                $this->project,
                $this->new_campaign_tracker_id,
                $this->new_definition_tracker_id,
                $this->new_execution_tracker_id,
                $this->new_issue_tracker_id
            )->once();

        $this->config_creator->createConfigForProjectFromTemplate(
            $this->project,
            $this->template,
            $this->tracker_mapping
        );
    }

    public function testItDoesNotOverwriteAnExistingConfig()
    {
        $this->config->shouldReceive('isConfigNeeded')
            ->with($this->project)
            ->andReturn(false);

        $this->config->shouldReceive('setProjectConfiguration')
            ->with(
                $this->project,
                $this->new_campaign_tracker_id,
                $this->new_definition_tracker_id,
                $this->new_execution_tracker_id,
                $this->new_issue_tracker_id
            )->never();

        $this->config_creator->createConfigForProjectFromTemplate(
            $this->project,
            $this->template,
            $this->tracker_mapping
        );
    }

    public function testItFallsBackToXMLImportIfTrackerMappingIsMissing()
    {
        $this->config->shouldReceive('isConfigNeeded')
            ->with($this->project)
            ->andReturn(true);

        $trackers_from_template = $this->getTrackersWithoutCampaignTracker();
        $this->config->shouldReceive('getTrackersFromTemplate')->andReturn(
            $trackers_from_template
        );

        $this->testmanagement_trackers_creator->shouldReceive('createTrackerFromXML')
            ->once()
            ->andReturn($this->new_campaign_tracker);

        $this->testmanagement_trackers_configurator->shouldReceive('configureTestmanagementTracker')->withArgs(
            ["campaign", 334]
        )->once();
        $this->testmanagement_trackers_configurator->shouldReceive('configureTestmanagementTracker')->withArgs(
            ["test_def", 445]
        )->once();
        $this->testmanagement_trackers_configurator->shouldReceive('configureTestmanagementTracker')->withArgs(
            ["test_exec", 556]
        )->once();
        $this->testmanagement_trackers_configurator->shouldReceive('configureTestmanagementTracker')->withArgs(
            ["bug", 667]
        )->once();

        $this->tracker_checker->shouldReceive('checkTrackers')->once();

        $this->testmanagement_trackers_configurator->shouldReceive('getTrackersConfiguration')->once()->andReturn(
            $this->getNewTrackersConfiguration()
        );

        $this->config_creator->createConfigForProjectFromTemplate(
            $this->project,
            $this->template,
            $this->tracker_mapping
        );
    }

    public function testItCreatesTTMTrackersFromXMLTemplates()
    {
        $this->config->shouldReceive('isConfigNeeded')
            ->with($this->project)
            ->andReturn(true);

        $this->testmanagement_trackers_creator->shouldReceive('createTrackerFromXML')
            ->with($this->project, "campaign")
            ->once()
            ->andReturn($this->new_campaign_tracker);

        $this->testmanagement_trackers_creator->shouldReceive('createTrackerFromXML')
            ->with($this->project, "test_def")
            ->once()
            ->andReturn($this->new_definition_tracker);

        $this->testmanagement_trackers_creator->shouldReceive('createTrackerFromXML')
            ->with($this->project, "test_exec")
            ->once()
            ->andReturn($this->new_execution_tracker);

        $this->testmanagement_trackers_creator->shouldReceive('createTrackerFromXML')
            ->with($this->project, "bug")
            ->once()
            ->andReturn($this->new_issue_tracker);

        $this->testmanagement_trackers_configurator->shouldReceive('configureTestmanagementTracker')->times(4);

        $this->testmanagement_trackers_configurator->shouldReceive('getTrackersConfiguration')->once()->andReturn(
            $this->getNewTrackersConfiguration()
        );

        $this->tracker_checker->shouldReceive('checkTrackers')->once();

        $this->config->shouldReceive('setProjectConfiguration')
            ->with(
                $this->project,
                $this->new_campaign_tracker_id,
                $this->new_definition_tracker_id,
                $this->new_execution_tracker_id,
                $this->new_issue_tracker_id
            )->once();

        $this->config_creator->createConfigForProjectFromXML($this->project);
    }

    public function testItDoesNotCreateExistingTrackers()
    {
        $this->tracker_factory->shouldReceive('isShortNameExists')
            ->with('campaign', $this->project->getID())
            ->andReturn(true);

        $this->tracker_factory->shouldReceive('getTrackerByShortnameAndProjectId')
            ->with('campaign', $this->project->getID())
            ->andReturn($this->new_campaign_tracker);

        $this->config->shouldReceive('isConfigNeeded')
            ->with($this->project)
            ->andReturn(true);

        $this->testmanagement_trackers_creator->shouldReceive('createTrackerFromXML')
            ->with($this->project, "campaign")
            ->never();

        $this->testmanagement_trackers_creator->shouldReceive('createTrackerFromXML')
            ->with($this->project, "test_def")
            ->once()
            ->andReturn($this->new_definition_tracker);

        $this->testmanagement_trackers_creator->shouldReceive('createTrackerFromXML')
            ->with($this->project, "test_exec")
            ->once()
            ->andReturn($this->new_execution_tracker);

        $this->testmanagement_trackers_creator->shouldReceive('createTrackerFromXML')
            ->with($this->project, "bug")
            ->once()
            ->andReturn($this->new_issue_tracker);

        $this->testmanagement_trackers_configurator->shouldReceive('configureTestmanagementTracker')->times(4);

        $this->testmanagement_trackers_configurator->shouldReceive('getTrackersConfiguration')->once()->andReturn(
            $this->getNewTrackersConfiguration()
        );

        $this->tracker_checker->shouldReceive('checkTrackers')->once();

        $this->config_creator->createConfigForProjectFromXML($this->project);
    }

    public function testItThrowsAnExceptionIfTrackerExistsInLegacyEngineInTemplateContext()
    {
        $this->config->shouldReceive('getTrackersFromTemplate')->andReturn(
            $this->getTrackersWithoutCampaignTracker()
        );

        $this->tracker_factory->shouldReceive('isShortNameExists')
            ->with('campaign', $this->project->getID())
            ->andReturn(true);

        $this->tracker_factory->shouldReceive('getTrackerByShortnameAndProjectId')
            ->with('campaign', $this->project->getID())
            ->andReturn(null);

        $this->config->shouldReceive('isConfigNeeded')
            ->with($this->project)
            ->andReturn(true);

        $this->expectException(TrackerComesFromLegacyEngineException::class);

        $this->config_creator->createConfigForProjectFromTemplate(
            $this->project,
            $this->template,
            $this->tracker_mapping
        );
    }

    public function testItThrowsAnExceptionIfTrackerExistsInLegacyEngineInXMLContext()
    {
        $this->tracker_factory->shouldReceive('isShortNameExists')
            ->with('campaign', $this->project->getID())
            ->andReturn(true);

        $this->tracker_factory->shouldReceive('getTrackerByShortnameAndProjectId')
            ->with('campaign', $this->project->getID())
            ->andReturn(null);

        $this->config->shouldReceive('isConfigNeeded')
            ->with($this->project)
            ->andReturn(true);

        $this->testmanagement_trackers_creator->shouldReceive('createTrackerFromXML')->never();

        $this->expectException(TrackerComesFromLegacyEngineException::class);

        $this->config_creator->createConfigForProjectFromXML($this->project);
    }

    public function testItThrowsAnExceptionIfTrackerIsNotCreatedInXMLContext()
    {
        $this->config->shouldReceive('isConfigNeeded')
            ->with($this->project)
            ->andReturn(true);

        $this->testmanagement_trackers_creator->shouldReceive('createTrackerFromXML')
            ->with($this->project, "campaign")
            ->once()
            ->andThrow(TrackerNotCreatedException::class);

        $this->expectException(TrackerNotCreatedException::class);

        $this->config_creator->createConfigForProjectFromXML($this->project);
    }

    public function testItDoesNotSaveTheConfigurationIfATrackerIsNotUsableInTemplateContext()
    {
        $this->config->shouldReceive('getTrackersFromTemplate')->andReturn(
            $this->getTrackers()
        );

        $this->config->shouldReceive('isConfigNeeded')
            ->with($this->project)
            ->andReturn(true);

        $this->testmanagement_trackers_configurator->shouldReceive('configureTestmanagementTracker')->times(4);

        $this->testmanagement_trackers_configurator->shouldReceive('getTrackersConfiguration')->once()->andReturn(
            $this->getNewTrackersConfiguration()
        );

        $this->tracker_checker->shouldReceive('checkTrackers')->once()->andThrow(
            TrackerHasAtLeastOneFrozenFieldsPostActionException::class
        );

        $this->expectException(TrackerHasAtLeastOneFrozenFieldsPostActionException::class);

        $this->config->shouldReceive('setProjectConfiguration')->never();

        $this->config_creator->createConfigForProjectFromTemplate(
            $this->project,
            $this->template,
            $this->tracker_mapping
        );
    }

    private function getTrackers(): array
    {
        return [
            new TestmanagementConfigTracker(
                "Validation Campaign",
                "campaign",
                $this->campaign_tracker_id
            ),
            new TestmanagementConfigTracker(
                "Test Cases",
                "test_def",
                $this->definition_tracker_id
            ),
            new TestmanagementConfigTracker(
                "Test Execution",
                "test_exec",
                $this->execution_tracker_id
            ),
            new TestmanagementConfigTracker(
                "bugs",
                "bug",
                $this->issue_tracker_id
            ),

        ];
    }

    private function getTrackersWithoutCampaignTracker(): array
    {
        return [
            new TestmanagementConfigTracker(
                "Validation Campaign",
                "campaign",
                false
            ),
            new TestmanagementConfigTracker(
                "Test Cases",
                "test_def",
                $this->definition_tracker_id
            ),
            new TestmanagementConfigTracker(
                "Test Execution",
                "test_exec",
                $this->execution_tracker_id
            ),
            new TestmanagementConfigTracker(
                "bugs",
                "bug",
                $this->issue_tracker_id
            ),

        ];
    }

    private function getNewTrackersConfiguration(): TestmanagementTrackersConfiguration
    {
        $tracker_configuration = new TestmanagementTrackersConfiguration();
        $tracker_configuration->setCampaign(
            new TestmanagementConfigTracker(
                "Validation Campaign",
                "campaign",
                $this->new_campaign_tracker_id
            )
        );
        $tracker_configuration->setTestDefinition(
            new TestmanagementConfigTracker(
                "Test Cases",
                "test_def",
                $this->new_definition_tracker_id
            )
        );
        $tracker_configuration->setTestExecution(
            new TestmanagementConfigTracker(
                "Test Execution",
                "test_exec",
                $this->new_execution_tracker_id
            )
        );
        $tracker_configuration->setIssue(
            new TestmanagementConfigTracker(
                "bugs",
                "bug",
                $this->new_issue_tracker_id
            )
        );

        return $tracker_configuration;
    }
}
