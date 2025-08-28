<?php
/**
 * Copyright (c) Enalean, 2017 - Present. All Rights Reserved.
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
use PHPUnit\Framework\MockObject\MockObject;
use Tracker_FormElementFactory;
use Tuleap\Project\ProjectByIDFactory;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\Stubs\ProjectByIDFactoryStub;
use Tuleap\TestManagement\Config;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\REST\Artifact\ArtifactCreator;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;
use Tuleap\Tracker\Test\Builders\Fields\ArtifactLinkFieldBuilder;
use Tuleap\Tracker\Test\Builders\Fields\List\ListStaticBindBuilder;
use Tuleap\Tracker\Test\Builders\Fields\SelectboxFieldBuilder;
use Tuleap\Tracker\Test\Builders\Fields\StringFieldBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use Tuleap\Tracker\Test\Stub\RetrieveTrackerStub;
use Tuleap\Tracker\Tracker;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class CampaignCreatorTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private CampaignCreator $campaign_creator;
    private ProjectByIDFactory $project_manager;
    private DefinitionSelector&MockObject $definition_selector;
    private Tracker_FormElementFactory $formelement_factory;
    private Config&MockObject $config;
    private ArtifactCreator&MockObject $artifact_creator;
    private Artifact&MockObject $campaign_artifact;
    private ExecutionCreator&MockObject $execution_creator;
    private Tracker $campaign_tracker;
    private PFUser $user;

    private int $project_id          = 101;
    private int $campaign_tracker_id = 444;

    public function setUp(): void
    {
        parent::setUp();

        $project         = ProjectTestBuilder::aProject()->withId($this->project_id)->build();
        $project_manager = ProjectByIDFactoryStub::buildWith($project);

        $this->campaign_tracker = TrackerTestBuilder::aTracker()
            ->withId($this->campaign_tracker_id)
            ->withProject($project)
            ->withName('Campaigns')
            ->build();

        $tracker_factory = RetrieveTrackerStub::withTracker($this->campaign_tracker);

        $this->user = UserTestBuilder::buildWithDefaults();

        $this->definition_selector = $this->createMock(\Tuleap\TestManagement\REST\v1\DefinitionSelector::class);
        $this->execution_creator   = $this->createMock(\Tuleap\TestManagement\REST\v1\ExecutionCreator::class);

        $this->formelement_factory = $this->createMock(\Tracker_FormElementFactory::class);
        $this->formelement_factory
            ->method('getUsedFieldByNameForUser')
            ->willReturnCallback(fn (int $tracker_id, string $field_name, PFUser $user): \Tuleap\Tracker\FormElement\Field\TrackerField => match ($field_name) {
                CampaignRepresentation::FIELD_NAME => StringFieldBuilder::aStringField(1001)->build(),
                CampaignRepresentation::FIELD_STATUS => ListStaticBindBuilder::aStaticBind(SelectboxFieldBuilder::aSelectboxField(1002)->build())->build()->getField(),
                CampaignRepresentation::FIELD_ARTIFACT_LINKS => ArtifactLinkFieldBuilder::anArtifactLinkField(1003)->build(),
            });

        $this->campaign_artifact = $this->createMock(\Tuleap\Tracker\Artifact\Artifact::class);
        $artifact_ref            = $this->createMock(\Tuleap\Tracker\REST\Artifact\ArtifactReference::class);
        $artifact_ref->method('getArtifact')->willReturn($this->campaign_artifact);

        $this->artifact_creator = $this->createMock(ArtifactCreator::class);
        $this->artifact_creator->method('create')->willReturn($artifact_ref);

        $this->config = $this->createMock(\Tuleap\TestManagement\Config::class);
        $this->config->method('getCampaignTrackerId')->willReturn($this->campaign_tracker_id);

        $this->campaign_creator = new CampaignCreator(
            $this->config,
            $project_manager,
            $this->formelement_factory,
            $tracker_factory,
            $this->definition_selector,
            $this->artifact_creator,
            $this->execution_creator
        );
    }

    public function testItCreatesACampaignWithTheGivenName(): void
    {
        $this->definition_selector->method('selectDefinitions')->willReturn([]);

        $expected_label  = 'Campaign Name';
        $test_selector   = 'all';
        $no_milestone_id = 0;
        $no_report_id    = 0;

        $this->campaign_artifact->expects($this->never())->method('linkArtifact');

        $this->campaign_creator->createCampaign(
            $this->user,
            $this->project_id,
            $expected_label,
            $test_selector,
            $no_milestone_id,
            $no_report_id
        );
    }

    public function testItCreatesAnArtifactLinkToMilestoneWhenGivenAMilestoneId(): void
    {
        $this->definition_selector->method('selectDefinitions')->willReturn([]);

        $expected_label = 'Campaign Name';
        $test_selector  = 'all';
        $milestone_id   = 10;
        $no_report_id   = 0;

        $this->campaign_artifact
            ->expects($this->once())
            ->method('linkArtifact')
            ->with($milestone_id, $this->user);

        $this->campaign_creator->createCampaign(
            $this->user,
            $this->project_id,
            $expected_label,
            $test_selector,
            $milestone_id,
            $no_report_id
        );
    }

    public function testItCreatesTestExecutionsForSelectedDefinitions(): void
    {
        $definition_1     = ArtifactTestBuilder::anArtifact(1)->build();
        $definition_2     = ArtifactTestBuilder::anArtifact(2)->build();
        $definition_3     = ArtifactTestBuilder::anArtifact(3)->build();
        $test_definitions = [$definition_1, $definition_2, $definition_3];

        $this->definition_selector->method('selectDefinitions')->willReturn($test_definitions);

        $expected_label = 'Campaign Name';
        $test_selector  = 'report';
        $milestone_id   = 10;
        $report_id      = 5;

        $this->execution_creator
            ->expects($this->exactly(count($test_definitions)))
            ->method('createTestExecution')
            ->willReturn(ArtifactTestBuilder::anArtifact(124)->build());

        $this->campaign_artifact
            ->expects($this->once())
            ->method('linkArtifact')
            ->with($milestone_id, $this->user);

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
