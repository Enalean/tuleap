<?php
/**
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\TestManagement\Campaign;

use PHPUnit\Framework\MockObject\MockObject;
use Tracker_FormElement_Field_List;
use Tracker_FormElementFactory;
use TrackerFactory;
use Tuleap\TestManagement\Config;
use Tuleap\Tracker\Artifact\Artifact;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class InformationNeededToRetrieveTestStatusOfACampaignTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private const CAMPAIGN_ID     = 683;
    private const USER_UGROUP_IDS = ['123', 4];

    private Artifact&MockObject $campaign;
    private \Tracker&MockObject $campaign_tracker;
    private \PFUser&MockObject $user;
    private Config&MockObject $testmanagement_config;
    private TrackerFactory&MockObject $tracker_factory;
    private Tracker_FormElementFactory&MockObject $form_element_factory;

    protected function setUp(): void
    {
        $this->campaign = $this->createMock(\Tuleap\Tracker\Artifact\Artifact::class);
        $this->campaign->method('getId')->willReturn((string) self::CAMPAIGN_ID);
        $this->campaign_tracker = $this->createMock(\Tracker::class);
        $this->campaign->method('getTracker')->willReturn($this->campaign_tracker);
        $project = $this->createMock(\Project::class);
        $project->method('getID')->willReturn('102');
        $this->campaign_tracker->method('getProject')->willReturn($project);
        $this->user = $this->createMock(\PFUser::class);
        $this->user->method('getUgroups')->willReturn(self::USER_UGROUP_IDS);
        $this->testmanagement_config = $this->createMock(Config::class);
        $this->tracker_factory       = $this->createMock(TrackerFactory::class);
        $this->form_element_factory  = $this->createMock(Tracker_FormElementFactory::class);
    }

    public function testBuildsFromCampaignWhenUsersHaveEnoughRights(): void
    {
        $this->campaign->method('userCanView')->willReturn(true);
        $this->testmanagement_config->method('getTestExecutionTrackerId')->willReturn(11);
        $test_exec_tracker = $this->buildTracker(true);
        $this->tracker_factory->method('getTrackerById')->with(11)->willReturn($test_exec_tracker);
        $status_field    = $this->createMock(Tracker_FormElement_Field_List::class);
        $status_field_id = 4444;
        $status_field->method('getId')->willReturn($status_field_id);
        $status_field->method('userCanRead')->willReturn(true);
        $test_exec_tracker->method('getStatusField')->willReturn($status_field);
        $campaign_art_link_field_id = 852;

        $link = $this->createMock(\Tuleap\Tracker\FormElement\Field\ArtifactLink\ArtifactLinkField::class);
        $link->method('getId')->willReturn($campaign_art_link_field_id);

        $this->form_element_factory->method('getAnArtifactLinkField')
            ->with($this->user, $this->campaign_tracker)
            ->willReturn($link);

        $information = InformationNeededToRetrieveTestStatusOfACampaign::fromCampaign(
            $this->campaign,
            $this->user,
            $this->testmanagement_config,
            $this->tracker_factory,
            $this->form_element_factory
        );
        self::assertSame(self::CAMPAIGN_ID, $information->campaign_id);
        self::assertSame(self::USER_UGROUP_IDS, $information->current_user_ugroup_ids);
        self::assertSame($status_field_id, $information->test_exec_status_field_id);
        self::assertSame($campaign_art_link_field_id, $information->test_campaign_art_link_field_id);
    }

    public function testUsersCannotAccessTheInformationWhenTheyCannotViewTheCampaign(): void
    {
        $this->campaign->method('userCanView')->willReturn(false);

        $information = InformationNeededToRetrieveTestStatusOfACampaign::fromCampaign(
            $this->campaign,
            $this->user,
            $this->testmanagement_config,
            $this->tracker_factory,
            $this->form_element_factory
        );

        $this->assertNull($information);
    }

    public function testUsersCannotAccessTheInformationWhenTheyCannotSeeTheArtLinkFieldOfCampaigns(): void
    {
        $this->campaign->method('userCanView')->willReturn(true);
        $this->form_element_factory->method('getAnArtifactLinkField')
            ->with($this->user, $this->campaign_tracker)
            ->willReturn(null);

        $information = InformationNeededToRetrieveTestStatusOfACampaign::fromCampaign(
            $this->campaign,
            $this->user,
            $this->testmanagement_config,
            $this->tracker_factory,
            $this->form_element_factory
        );

        $this->assertNull($information);
    }

    public function testUsersCannotAccessTheInformationWhenTheyCannotSeeTheTestExecStatusField(): void
    {
        $this->campaign->method('userCanView')->willReturn(true);
        $this->testmanagement_config->method('getTestExecutionTrackerId')->willReturn(11);
        $test_exec_tracker = $this->buildTracker(true);
        $this->tracker_factory->method('getTrackerById')->with(11)->willReturn($test_exec_tracker);
        $status_field = $this->createMock(Tracker_FormElement_Field_List::class);
        $status_field->method('getId')->willReturn(4444);
        $status_field->method('userCanRead')->willReturn(false);
        $test_exec_tracker->method('getStatusField')->willReturn($status_field);

        $link = $this->createMock(\Tuleap\Tracker\FormElement\Field\ArtifactLink\ArtifactLinkField::class);
        $link->method('getId')->willReturn(852);

        $this->form_element_factory->method('getAnArtifactLinkField')
            ->with($this->user, $this->campaign_tracker)
            ->willReturn($link);

        $information = InformationNeededToRetrieveTestStatusOfACampaign::fromCampaign(
            $this->campaign,
            $this->user,
            $this->testmanagement_config,
            $this->tracker_factory,
            $this->form_element_factory
        );

        $this->assertNull($information);
    }

    public function testUsersCannotAccessTheInformationWhenTheTestExecStatusFieldDoesNotExist(): void
    {
        $this->campaign->method('userCanView')->willReturn(true);
        $this->testmanagement_config->method('getTestExecutionTrackerId')->willReturn(11);
        $test_exec_tracker = $this->buildTracker(true);
        $this->tracker_factory->method('getTrackerById')->with(11)->willReturn($test_exec_tracker);
        $test_exec_tracker->method('getStatusField')->willReturn(null);

        $link = $this->createMock(\Tuleap\Tracker\FormElement\Field\ArtifactLink\ArtifactLinkField::class);
        $link->method('getId')->willReturn(852);

        $this->form_element_factory->method('getAnArtifactLinkField')
            ->with($this->user, $this->campaign_tracker)
            ->willReturn($link);

        $information = InformationNeededToRetrieveTestStatusOfACampaign::fromCampaign(
            $this->campaign,
            $this->user,
            $this->testmanagement_config,
            $this->tracker_factory,
            $this->form_element_factory
        );

        $this->assertNull($information);
    }

    public function testUsersCannotAccessTheInformationWhenTheyCannotViewTheTestExecTracker(): void
    {
        $this->campaign->method('userCanView')->willReturn(true);
        $this->testmanagement_config->method('getTestExecutionTrackerId')->willReturn(11);
        $test_exec_tracker = $this->buildTracker(false);
        $this->tracker_factory->method('getTrackerById')->with(11)->willReturn($test_exec_tracker);

        $link = $this->createMock(\Tuleap\Tracker\FormElement\Field\ArtifactLink\ArtifactLinkField::class);
        $link->method('getId')->willReturn(852);

        $this->form_element_factory->method('getAnArtifactLinkField')
            ->with($this->user, $this->campaign_tracker)
            ->willReturn($link);

        $information = InformationNeededToRetrieveTestStatusOfACampaign::fromCampaign(
            $this->campaign,
            $this->user,
            $this->testmanagement_config,
            $this->tracker_factory,
            $this->form_element_factory
        );

        $this->assertNull($information);
    }

    public function testUsersCannotAccessTheInformationWhenTheTTMDoesNotHaveATestExecTracker(): void
    {
        $this->campaign->method('userCanView')->willReturn(true);
        $this->testmanagement_config->method('getTestExecutionTrackerId')->willReturn(false);

        $link = $this->createMock(\Tuleap\Tracker\FormElement\Field\ArtifactLink\ArtifactLinkField::class);
        $link->method('getId')->willReturn(852);

        $this->form_element_factory->method('getAnArtifactLinkField')
            ->with($this->user, $this->campaign_tracker)
            ->willReturn($link);

        $information = InformationNeededToRetrieveTestStatusOfACampaign::fromCampaign(
            $this->campaign,
            $this->user,
            $this->testmanagement_config,
            $this->tracker_factory,
            $this->form_element_factory
        );

        $this->assertNull($information);
    }

    private function buildTracker(bool $can_user_view_it): \Tracker&MockObject
    {
        $tracker = $this->createMock(\Tracker::class);
        $tracker->method('userCanView')->willReturn($can_user_view_it);

        return $tracker;
    }
}
