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

use PHPUnit\Framework\MockObject\MockObject;
use Project;
use Tracker;
use TrackerFactory;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\TestManagement\Administration\TrackerChecker;
use Tuleap\TestManagement\Administration\TrackerHasAtLeastOneFrozenFieldsPostActionException;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class FirstConfigCreatorTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private Config&MockObject $config;

    private Project $template;

    private Project $project;

    private TrackerFactory&MockObject $tracker_factory;

    private TrackerChecker&MockObject $tracker_checker;

    private TestmanagementTrackersCreator&MockObject $testmanagement_trackers_creator;
    private TestmanagementTrackersConfigurator&MockObject $testmanagement_trackers_configurator;
    private Tracker $new_campaign_tracker;
    private Tracker $new_definition_tracker;
    private Tracker $new_execution_tracker;
    private Tracker $new_issue_tracker;
    private FirstConfigCreator $config_creator;

    private int $template_id           = 101;
    private int $campaign_tracker_id   = 333;
    private int $definition_tracker_id = 444;
    private int $execution_tracker_id  = 555;
    private int $issue_tracker_id      = 666;

    private int $project_id                = 102;
    private int $new_campaign_tracker_id   = 334;
    private int $new_definition_tracker_id = 445;
    private int $new_execution_tracker_id  = 556;
    private int $new_issue_tracker_id      = 667;
    private array $tracker_mapping;

    protected function setUp(): void
    {
        parent::setUp();

        $this->template = ProjectTestBuilder::aProject()->withId($this->template_id)->build();
        $this->project  = ProjectTestBuilder::aProject()->withId($this->project_id)->build();

        $this->tracker_mapping = [
            $this->campaign_tracker_id   => $this->new_campaign_tracker_id,
            $this->definition_tracker_id => $this->new_definition_tracker_id,
            $this->execution_tracker_id  => $this->new_execution_tracker_id,
            $this->issue_tracker_id      => $this->new_issue_tracker_id,
        ];

        $this->config          = $this->createMock(Config::class);
        $this->tracker_factory = $this->createMock(TrackerFactory::class);

        $this->new_campaign_tracker   = TrackerTestBuilder::aTracker()->withId($this->new_campaign_tracker_id)->build();
        $this->new_definition_tracker = TrackerTestBuilder::aTracker()->withId($this->new_definition_tracker_id)->build();
        $this->new_execution_tracker  = TrackerTestBuilder::aTracker()->withId($this->new_execution_tracker_id)->build();
        $this->new_issue_tracker      = TrackerTestBuilder::aTracker()->withId($this->new_issue_tracker_id)->build();

        $this->tracker_checker                      = $this->createMock(TrackerChecker::class);
        $this->testmanagement_trackers_creator      = $this->createMock(TestmanagementTrackersCreator::class);
        $this->testmanagement_trackers_configurator = $this->createMock(TestmanagementTrackersConfigurator::class);

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
        $this->config->method('isConfigNeeded')
            ->with($this->project)
            ->willReturn(true);

        $trackers_from_template = $this->getTrackers();

        $this->config->method('getTrackersFromTemplate')->willReturn(
            $trackers_from_template
        );
        $this->testmanagement_trackers_configurator->expects(self::exactly(4))->method('configureTestmanagementTracker');

        $this->testmanagement_trackers_configurator->expects(self::once())->method('getTrackersConfiguration')->willReturn(
            $this->getNewTrackersConfiguration()
        );

        $this->tracker_checker->expects(self::once())->method('checkTrackers');

        $this->config
            ->expects(self::once())
            ->method('setProjectConfiguration')
            ->with(
                $this->project,
                $this->new_campaign_tracker_id,
                $this->new_definition_tracker_id,
                $this->new_execution_tracker_id,
                $this->new_issue_tracker_id
            );

        $this->config_creator->createConfigForProjectFromTemplate(
            $this->project,
            $this->template,
            $this->tracker_mapping
        );
    }

    public function testItDoesNotOverwriteAnExistingConfig()
    {
        $this->config->method('isConfigNeeded')
            ->with($this->project)
            ->willReturn(false);

        $this->config
            ->expects(self::never())
            ->method('setProjectConfiguration');

        $this->config_creator->createConfigForProjectFromTemplate(
            $this->project,
            $this->template,
            $this->tracker_mapping
        );
    }

    public function testItFallsBackToXMLImportIfTrackerMappingIsMissing()
    {
        $this->tracker_factory->method('isShortNameExists')
            ->with('campaign', $this->project->getID())
            ->willReturn(false);

        $this->config->method('isConfigNeeded')
            ->with($this->project)
            ->willReturn(true);

        $trackers_from_template = $this->getTrackersWithoutCampaignTracker();
        $this->config->method('getTrackersFromTemplate')->willReturn(
            $trackers_from_template
        );

        $this->testmanagement_trackers_creator
            ->expects(self::once())
            ->method('createTrackerFromXML')
            ->willReturn($this->new_campaign_tracker);

        $this->testmanagement_trackers_configurator
            ->expects(self::exactly(4))
            ->method('configureTestmanagementTracker')
            ->willReturnMap([
                ['campaign', 334, new TestmanagementConfigTracker('campaign', 'campaign', 334)],
                ['test_def', 445, new TestmanagementConfigTracker('test_def', 'test_def', 445)],
                ['test_exec', 556, new TestmanagementConfigTracker('text_exec', 'test_exec', 556)],
                ['bug', 667, new TestmanagementConfigTracker('bug', 'bug', 667)],
            ]);

        $this->tracker_checker->expects(self::once())->method('checkTrackers');

        $this->testmanagement_trackers_configurator
            ->expects(self::once())
            ->method('getTrackersConfiguration')
            ->willReturn(
                $this->getNewTrackersConfiguration()
            );

        $this->config->method('setProjectConfiguration');

        $this->config_creator->createConfigForProjectFromTemplate(
            $this->project,
            $this->template,
            $this->tracker_mapping
        );
    }

    public function testItCreatesTTMTrackersFromXMLTemplates()
    {
        $this->tracker_factory->method('isShortNameExists')
            ->willReturn(false);

        $this->config->method('isConfigNeeded')
            ->with($this->project)
            ->willReturn(true);

        $this->testmanagement_trackers_creator
            ->method('createTrackerFromXML')
            ->willReturnCallback(fn (Project $project, string $tracker_itemname) => match ($tracker_itemname) {
                'campaign'  => $this->new_campaign_tracker,
                'test_def'  => $this->new_definition_tracker,
                'test_exec' => $this->new_execution_tracker,
                'bug'       => $this->new_issue_tracker,
            });

        $this->testmanagement_trackers_configurator
            ->expects(self::exactly(4))
            ->method('configureTestmanagementTracker');

        $this->testmanagement_trackers_configurator
            ->expects(self::once())
            ->method('getTrackersConfiguration')
            ->willReturn(
                $this->getNewTrackersConfiguration()
            );

        $this->tracker_checker->expects(self::once())->method('checkTrackers');

        $this->config
            ->expects(self::once())
            ->method('setProjectConfiguration')
            ->with(
                $this->project,
                $this->new_campaign_tracker_id,
                $this->new_definition_tracker_id,
                $this->new_execution_tracker_id,
                $this->new_issue_tracker_id
            );

        $this->config_creator->createConfigForProjectFromXML($this->project);
    }

    public function testItDoesNotCreateExistingTrackers()
    {
        $this->tracker_factory->method('isShortNameExists')
            ->willReturnCallback(fn(string $shortname, $group_id) => $shortname === 'campaign');

        $this->tracker_factory->method('getTrackerByShortnameAndProjectId')
            ->with('campaign', $this->project->getID())
            ->willReturn($this->new_campaign_tracker);

        $this->config->method('isConfigNeeded')
            ->with($this->project)
            ->willReturn(true);

        $this->testmanagement_trackers_creator
            ->method('createTrackerFromXML')
            ->willReturnCallback(fn (Project $project, string $tracker_itemname) => match ($tracker_itemname) {
                'test_def'  => $this->new_definition_tracker,
                'test_exec' => $this->new_execution_tracker,
                'bug'       => $this->new_issue_tracker,
            });

        $this->testmanagement_trackers_configurator
            ->expects(self::exactly(4))
            ->method('configureTestmanagementTracker');

        $this->testmanagement_trackers_configurator
            ->expects(self::once())
            ->method('getTrackersConfiguration')
            ->willReturn(
                $this->getNewTrackersConfiguration()
            );

        $this->tracker_checker->expects(self::once())->method('checkTrackers');

        $this->config->method('setProjectConfiguration');

        $this->config_creator->createConfigForProjectFromXML($this->project);
    }

    public function testItThrowsAnExceptionIfTrackerExistsInLegacyEngineInTemplateContext()
    {
        $this->config->method('getTrackersFromTemplate')->willReturn(
            $this->getTrackersWithoutCampaignTracker()
        );

        $this->tracker_factory->method('isShortNameExists')
            ->willReturnCallback(fn(string $shortname, $group_id) => $shortname === 'campaign');

        $this->tracker_factory->method('getTrackerByShortnameAndProjectId')
            ->with('campaign', $this->project->getID())
            ->willReturn(null);

        $this->config->method('isConfigNeeded')
            ->with($this->project)
            ->willReturn(true);

        $this->expectException(TrackerComesFromLegacyEngineException::class);

        $this->config_creator->createConfigForProjectFromTemplate(
            $this->project,
            $this->template,
            $this->tracker_mapping
        );
    }

    public function testItThrowsAnExceptionIfTrackerExistsInLegacyEngineInXMLContext()
    {
        $this->tracker_factory->method('isShortNameExists')
            ->willReturnCallback(fn(string $shortname, $group_id) => $shortname === 'campaign');

        $this->tracker_factory->method('getTrackerByShortnameAndProjectId')
            ->with('campaign', $this->project->getID())
            ->willReturn(null);

        $this->config->method('isConfigNeeded')
            ->with($this->project)
            ->willReturn(true);

        $this->testmanagement_trackers_creator->expects(self::never())->method('createTrackerFromXML');

        $this->expectException(TrackerComesFromLegacyEngineException::class);

        $this->config_creator->createConfigForProjectFromXML($this->project);
    }

    public function testItThrowsAnExceptionIfTrackerIsNotCreatedInXMLContext()
    {
        $this->tracker_factory->method('isShortNameExists')
            ->willReturn(false);

        $this->config->method('isConfigNeeded')
            ->with($this->project)
            ->willReturn(true);

        $this->testmanagement_trackers_creator
            ->method('createTrackerFromXML')
            ->with($this->project, 'campaign')
            ->willThrowException(new TrackerNotCreatedException());

        $this->expectException(TrackerNotCreatedException::class);

        $this->config_creator->createConfigForProjectFromXML($this->project);
    }

    public function testItDoesNotSaveTheConfigurationIfATrackerIsNotUsableInTemplateContext()
    {
        $this->config->method('getTrackersFromTemplate')->willReturn(
            $this->getTrackers()
        );

        $this->config->method('isConfigNeeded')
            ->with($this->project)
            ->willReturn(true);

        $this->testmanagement_trackers_configurator
            ->expects(self::exactly(4))
            ->method('configureTestmanagementTracker');

        $this->testmanagement_trackers_configurator
            ->expects(self::once())
            ->method('getTrackersConfiguration')
            ->willReturn(
                $this->getNewTrackersConfiguration()
            );

        $this->tracker_checker->method('checkTrackers')->willThrowException(
            new TrackerHasAtLeastOneFrozenFieldsPostActionException()
        );

        $this->expectException(TrackerHasAtLeastOneFrozenFieldsPostActionException::class);

        $this->config
            ->expects(self::never())
            ->method('setProjectConfiguration');

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
                'Validation Campaign',
                'campaign',
                $this->campaign_tracker_id
            ),
            new TestmanagementConfigTracker(
                'Test Cases',
                'test_def',
                $this->definition_tracker_id
            ),
            new TestmanagementConfigTracker(
                'Test Execution',
                'test_exec',
                $this->execution_tracker_id
            ),
            new TestmanagementConfigTracker(
                'bugs',
                'bug',
                $this->issue_tracker_id
            ),

        ];
    }

    private function getTrackersWithoutCampaignTracker(): array
    {
        return [
            new TestmanagementConfigTracker(
                'Validation Campaign',
                'campaign',
                false
            ),
            new TestmanagementConfigTracker(
                'Test Cases',
                'test_def',
                $this->definition_tracker_id
            ),
            new TestmanagementConfigTracker(
                'Test Execution',
                'test_exec',
                $this->execution_tracker_id
            ),
            new TestmanagementConfigTracker(
                'bugs',
                'bug',
                $this->issue_tracker_id
            ),

        ];
    }

    private function getNewTrackersConfiguration(): TestmanagementTrackersConfiguration
    {
        $tracker_configuration = new TestmanagementTrackersConfiguration();
        $tracker_configuration->setCampaign(
            new TestmanagementConfigTracker(
                'Validation Campaign',
                'campaign',
                $this->new_campaign_tracker_id
            )
        );
        $tracker_configuration->setTestDefinition(
            new TestmanagementConfigTracker(
                'Test Cases',
                'test_def',
                $this->new_definition_tracker_id
            )
        );
        $tracker_configuration->setTestExecution(
            new TestmanagementConfigTracker(
                'Test Execution',
                'test_exec',
                $this->new_execution_tracker_id
            )
        );
        $tracker_configuration->setIssue(
            new TestmanagementConfigTracker(
                'bugs',
                'bug',
                $this->new_issue_tracker_id
            )
        );

        return $tracker_configuration;
    }
}
