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
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Tracker;
use TrackerFromXmlException;
use Tuleap\TestManagement\Administration\TrackerChecker;
use Tuleap\TestManagement\Administration\TrackerHasAtLeastOneFrozenFieldsPostActionException;
use XMLImportHelper;

require_once __DIR__ . '/../bootstrap.php';

class FirstConfigCreatorTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /** @var Config */
    private $config;

    /** @var Project */
    private $template;

    /** @var Project */
    private $project;

    /** @var Tracker_Factory */
    private $tracker_factory;

    /** @var TrackerChecker */
    private $tracker_checker;

    /** @var XMLImportHelper */
    private $xml_import;

    private $template_id                  = 101;
    private $campaign_tracker_id          = 333;
    private $definition_tracker_id        = 444;
    private $execution_tracker_id         = 555;
    private $issue_tracker_id             = 666;

    private $project_id                   = 102;
    private $new_campaign_tracker_id      = 334;
    private $new_definition_tracker_id    = 445;
    private $new_execution_tracker_id     = 556;
    private $new_issue_tracker_id         = 667;
    private $tracker_mapping;

    private $campaign_tracker_xml_path;
    private $definition_tracker_xml_path;
    private $execution_tracker_xml_path;
    private $issue_tracker_xml_path;

    protected function setUp(): void
    {
        parent::setUp();

        $this->campaign_tracker_xml_path   = TESTMANAGEMENT_RESOURCE_DIR . '/Tracker_campaign.xml';
        $this->definition_tracker_xml_path = TESTMANAGEMENT_RESOURCE_DIR . '/Tracker_test_def.xml';
        $this->execution_tracker_xml_path  = TESTMANAGEMENT_RESOURCE_DIR . '/Tracker_test_exec.xml';
        $this->issue_tracker_xml_path      = realpath(__DIR__ . '/../../../tracker/resources/templates/Tracker_Bugs.xml');

        $this->template = Mockery::spy(\Project::class);
        $this->template->shouldReceive('getID')->andReturn($this->template_id);

        $this->project = Mockery::spy(\Project::class);
        $this->project->shouldReceive('getID')->andReturn($this->project_id);

        $this->tracker_mapping = array(
            $this->campaign_tracker_id   => $this->new_campaign_tracker_id,
            $this->definition_tracker_id => $this->new_definition_tracker_id,
            $this->execution_tracker_id  => $this->new_execution_tracker_id,
            $this->issue_tracker_id      => $this->new_issue_tracker_id
        );

        $this->config          = \Mockery::spy(\Tuleap\TestManagement\Config::class);
        $this->tracker_factory = \Mockery::spy(\TrackerFactory::class);
        $this->xml_import      = Mockery::spy(\TrackerXmlImport::class);

        $this->xml_import->shouldReceive('getTrackerItemNameFromXMLFile')
            ->with($this->campaign_tracker_xml_path)
            ->andReturn('campaign');

        $this->xml_import->shouldReceive('getTrackerItemNameFromXMLFile')
            ->with($this->definition_tracker_xml_path)
            ->andReturn('test_def');

        $this->xml_import->shouldReceive('getTrackerItemNameFromXMLFile')
            ->with($this->execution_tracker_xml_path)
            ->andReturn('test_exec');

        $this->xml_import->shouldReceive('createFromXMLFile')
            ->with($this->issue_tracker_xml_path)
            ->andReturn('bugs');

        $this->new_campaign_tracker = Mockery::spy(Tracker::class);
        $this->new_campaign_tracker->shouldReceive('getId')->andReturn($this->new_campaign_tracker_id);
        $this->xml_import->shouldReceive('getTrackerItemNameFromXMLFile')
            ->with($this->project, $this->campaign_tracker_xml_path)
            ->andReturn($this->new_campaign_tracker);

        $this->new_definition_tracker = Mockery::spy(Tracker::class);
        $this->new_definition_tracker->shouldReceive('getId')->andReturn($this->new_definition_tracker_id);
        $this->xml_import->shouldReceive('getTrackerItemNameFromXMLFile')
            ->with($this->project, $this->definition_tracker_xml_path)
            ->andReturn($this->new_definition_tracker);

        $this->new_execution_tracker = Mockery::spy(Tracker::class);
        $this->new_execution_tracker->shouldReceive('getId')->andReturn($this->new_execution_tracker_id);
        $this->xml_import->shouldReceive('getTrackerItemNameFromXMLFile')
            ->with($this->project, $this->execution_tracker_xml_path)
            ->andReturn($this->new_execution_tracker);

        $this->new_issue_tracker = Mockery::spy(Tracker::class);
        $this->new_issue_tracker->shouldReceive('getId')->andReturn($this->new_issue_tracker_id);
        $this->xml_import->shouldReceive('getTrackerItemNameFromXMLFile')
            ->with($this->project, $this->issue_tracker_xml_path)
            ->andReturn($this->new_issue_tracker);

        $this->tracker_checker = Mockery::mock(TrackerChecker::class);

        $this->config_creator = new FirstConfigCreator(
            $this->config,
            $this->tracker_factory,
            $this->xml_import,
            $this->tracker_checker,
            new NullLogger()
        );
    }

    public function testItSetsTheProjectTTMTrackerIdsInConfig()
    {
        $this->config->shouldReceive('getCampaignTrackerId')
            ->with($this->template)
            ->andReturn($this->campaign_tracker_id);

        $this->config->shouldReceive('getTestDefinitionTrackerId')
            ->with($this->template)
            ->andReturn($this->definition_tracker_id);

        $this->config->shouldReceive('getTestExecutionTrackerId')
            ->with($this->template)
            ->andReturn($this->execution_tracker_id);

        $this->config->shouldReceive('getIssueTrackerId')
            ->with($this->template)
            ->andReturn($this->issue_tracker_id);

        $this->config->shouldReceive('isConfigNeeded')
            ->with($this->project)
            ->andReturn(true);

        $this->config->shouldReceive('setProjectConfiguration')
            ->with(
                $this->project,
                $this->new_campaign_tracker_id,
                $this->new_definition_tracker_id,
                $this->new_execution_tracker_id,
                $this->new_issue_tracker_id
            )->once();

        $this->xml_import->shouldReceive('createFromXMLFile')->never();

        $this->tracker_checker->shouldReceive('checkTrackerIsInProject')->times(2);
        $this->tracker_checker->shouldReceive('checkSubmittedTrackerCanBeUsed')->times(2);

        $this->config_creator->createConfigForProjectFromTemplate(
            $this->project,
            $this->template,
            $this->tracker_mapping
        );
    }

    public function testItDoesNotOverwriteAnExistingConfig()
    {
        $this->config->shouldReceive('getCampaignTrackerId')
            ->with($this->project)
            ->andReturn(1);

        $this->config->shouldReceive('getTestDefinitionTrackerId')
            ->with($this->project)
            ->andReturn(2);

        $this->config->shouldReceive('getTestExecutionTrackerId')
            ->with($this->project)
            ->andReturn(3);

        $this->config->shouldReceive('getIssueTrackerId')
            ->with($this->project)
            ->andReturn(4);

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
        $this->config->shouldReceive('getCampaignTrackerId')
            ->with($this->template)
            ->andReturn(false);

        $this->config->shouldReceive('getTestDefinitionTrackerId')
            ->with($this->template)
            ->andReturn($this->definition_tracker_id);

        $this->config->shouldReceive('getTestExecutionTrackerId')
            ->with($this->template)
            ->andReturn($this->execution_tracker_id);

        $this->config->shouldReceive('getIssueTrackerId')
            ->with($this->template)
            ->andReturn($this->issue_tracker_id);

        $this->config->shouldReceive('isConfigNeeded')
            ->with($this->project)
            ->andReturn(true);

        $this->xml_import->shouldReceive('createFromXMLFile')
            ->once()
            ->andReturn($this->new_campaign_tracker);

        $this->config->shouldReceive('setProjectConfiguration')
            ->with(
                $this->project,
                $this->new_campaign_tracker_id,
                $this->new_definition_tracker_id,
                $this->new_execution_tracker_id,
                $this->new_issue_tracker_id
            )->once();

        $this->tracker_checker->shouldReceive('checkTrackerIsInProject')->times(2);
        $this->tracker_checker->shouldReceive('checkSubmittedTrackerCanBeUsed')->times(2);

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

        $this->xml_import->shouldReceive('createFromXMLFile')
            ->with($this->project, $this->campaign_tracker_xml_path)
            ->once()
            ->andReturn($this->new_campaign_tracker);

        $this->xml_import->shouldReceive('createFromXMLFile')
            ->with($this->project, $this->definition_tracker_xml_path)
            ->once()
            ->andReturn($this->new_definition_tracker);

        $this->xml_import->shouldReceive('createFromXMLFile')
            ->with($this->project, $this->execution_tracker_xml_path)
            ->once()
            ->andReturn($this->new_execution_tracker);

        $this->xml_import->shouldReceive('createFromXMLFile')
            ->with($this->project, $this->issue_tracker_xml_path)
            ->once()
            ->andReturn($this->new_issue_tracker);

        $this->tracker_checker->shouldReceive('checkTrackerIsInProject')->times(2);
        $this->tracker_checker->shouldReceive('checkSubmittedTrackerCanBeUsed')->times(2);

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

        $this->xml_import->shouldReceive('createFromXMLFile')
            ->with($this->project, $this->campaign_tracker_xml_path)
            ->never();

        $this->xml_import->shouldReceive('createFromXMLFile')
            ->with($this->project, $this->definition_tracker_xml_path)
            ->once()
            ->andReturn($this->new_definition_tracker);

        $this->xml_import->shouldReceive('createFromXMLFile')
            ->with($this->project, $this->execution_tracker_xml_path)
            ->once()
            ->andReturn($this->new_execution_tracker);

        $this->xml_import->shouldReceive('createFromXMLFile')
            ->with($this->project, $this->issue_tracker_xml_path)
            ->once()
            ->andReturn($this->new_issue_tracker);

        $this->tracker_checker->shouldReceive('checkTrackerIsInProject')->times(2);
        $this->tracker_checker->shouldReceive('checkSubmittedTrackerCanBeUsed')->times(2);

        $this->config_creator->createConfigForProjectFromXML($this->project);
    }

    public function testItThrowsAnExceptionIfTrackerExistsInLegacyEngineInTemplateContext()
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

        $this->expectException(TrackerComesFromLegacyEngineException::class);

        $this->config_creator->createConfigForProjectFromTemplate(
            $this->project,
            $this->template,
            $this->tracker_mapping
        );
    }

    public function testItThrowsAnExceptionIfTrackerIsNotCreatedInTemplateContext()
    {
        $this->config->shouldReceive('isConfigNeeded')
            ->with($this->project)
            ->andReturn(true);

        $this->xml_import->shouldReceive('createFromXMLFile')
            ->with($this->project, $this->campaign_tracker_xml_path)
            ->once()
            ->andThrow(TrackerFromXmlException::class);

        $this->expectException(TrackerNotCreatedException::class);

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

        $this->xml_import->shouldReceive('createFromXMLFile')->never();

        $this->expectException(TrackerComesFromLegacyEngineException::class);

        $this->config_creator->createConfigForProjectFromXML($this->project);
    }

    public function testItThrowsAnExceptionIfTrackerIsNotCreatedInXMLContext()
    {
        $this->config->shouldReceive('isConfigNeeded')
            ->with($this->project)
            ->andReturn(true);

        $this->xml_import->shouldReceive('createFromXMLFile')
            ->with($this->project, $this->campaign_tracker_xml_path)
            ->once()
            ->andThrow(TrackerFromXmlException::class);

        $this->expectException(TrackerNotCreatedException::class);

        $this->config_creator->createConfigForProjectFromXML($this->project);
    }

    public function testItDoesNotSaveTheConfigurationIfATrackerIsNotUsableInTemplateContext()
    {
        $this->config->shouldReceive('isConfigNeeded')
            ->with($this->project)
            ->andReturn(true);

        $this->config->shouldReceive('getCampaignTrackerId')
            ->with($this->template)
            ->andReturn($this->campaign_tracker_id);

        $this->config->shouldReceive('getTestDefinitionTrackerId')
            ->with($this->template)
            ->andReturn($this->definition_tracker_id);

        $this->config->shouldReceive('getTestExecutionTrackerId')
            ->with($this->template)
            ->andReturn($this->execution_tracker_id);

        $this->config->shouldReceive('getIssueTrackerId')
            ->with($this->template)
            ->andReturn($this->issue_tracker_id);

        $this->config->shouldReceive('isConfigNeeded')
            ->with($this->project)
            ->andReturn(true);

        $this->tracker_checker->shouldReceive('checkTrackerIsInProject')->times(2);
        $this->tracker_checker->shouldReceive('checkSubmittedTrackerCanBeUsed')
            ->with($this->project, Mockery::any())
            ->andThrow(TrackerHasAtLeastOneFrozenFieldsPostActionException::class);

        $this->expectException(TrackerHasAtLeastOneFrozenFieldsPostActionException::class);

        $this->config->shouldReceive('setProjectConfiguration')->never();

        $this->config_creator->createConfigForProjectFromTemplate(
            $this->project,
            $this->template,
            $this->tracker_mapping
        );
    }

    public function testItDoesNotSaveTheConfigurationIfATrackerIsNotUsableInXMLContext()
    {
        $this->config->shouldReceive('isConfigNeeded')
            ->with($this->project)
            ->andReturn(true);

        $this->xml_import->shouldReceive('createFromXMLFile')
            ->with($this->project, $this->campaign_tracker_xml_path)
            ->once()
            ->andReturn($this->new_campaign_tracker);

        $this->xml_import->shouldReceive('createFromXMLFile')
            ->with($this->project, $this->definition_tracker_xml_path)
            ->once()
            ->andReturn($this->new_definition_tracker);

        $this->xml_import->shouldReceive('createFromXMLFile')
            ->with($this->project, $this->execution_tracker_xml_path)
            ->once()
            ->andReturn($this->new_execution_tracker);

        $this->xml_import->shouldReceive('createFromXMLFile')
            ->with($this->project, $this->issue_tracker_xml_path)
            ->once()
            ->andReturn($this->new_issue_tracker);

        $this->tracker_checker->shouldReceive('checkTrackerIsInProject')->times(2);
        $this->tracker_checker->shouldReceive('checkSubmittedTrackerCanBeUsed')
            ->with($this->project, Mockery::any())
            ->andThrow(TrackerHasAtLeastOneFrozenFieldsPostActionException::class);

        $this->expectException(TrackerHasAtLeastOneFrozenFieldsPostActionException::class);

        $this->config->shouldReceive('setProjectConfiguration')->never();

        $this->config_creator->createConfigForProjectFromXML($this->project);
    }
}
