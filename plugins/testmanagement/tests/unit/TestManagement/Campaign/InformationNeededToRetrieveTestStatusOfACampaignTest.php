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

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tracker_FormElement_Field_List;
use Tracker_FormElementFactory;
use TrackerFactory;
use Tuleap\TestManagement\Config;

final class InformationNeededToRetrieveTestStatusOfACampaignTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private const CAMPAIGN_ID     = 683;
    private const USER_UGROUP_IDS = ['123', 4];

    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|\Tuleap\Tracker\Artifact\Artifact
     */
    private $campaign;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|\Tracker
     */
    private $campaign_tracker;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|\PFUser
     */
    private $user;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|Config
     */
    private $testmanagement_config;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|TrackerFactory
     */
    private $tracker_factory;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|Tracker_FormElementFactory
     */
    private $form_element_factory;

    protected function setUp(): void
    {
        $this->campaign = \Mockery::mock(\Tuleap\Tracker\Artifact\Artifact::class);
        $this->campaign->shouldReceive('getId')->andReturn((string) self::CAMPAIGN_ID);
        $this->campaign_tracker = \Mockery::mock(\Tracker::class);
        $this->campaign->shouldReceive('getTracker')->andReturn($this->campaign_tracker);
        $project = \Mockery::mock(\Project::class);
        $project->shouldReceive('getID')->andReturn('102');
        $this->campaign_tracker->shouldReceive('getProject')->andReturn($project);
        $this->user = \Mockery::mock(\PFUser::class);
        $this->user->shouldReceive('getUgroups')->andReturn(self::USER_UGROUP_IDS);
        $this->testmanagement_config = \Mockery::mock(Config::class);
        $this->tracker_factory       = \Mockery::mock(TrackerFactory::class);
        $this->form_element_factory  = \Mockery::mock(Tracker_FormElementFactory::class);
    }

    public function testBuildsFromCampaignWhenUsersHaveEnoughRights(): void
    {
        $this->campaign->shouldReceive('userCanView')->andReturn(true);
        $this->testmanagement_config->shouldReceive('getTestExecutionTrackerId')->andReturn(11);
        $test_exec_tracker = $this->buildTracker(true);
        $this->tracker_factory->shouldReceive('getTrackerById')->with(11)->andReturn($test_exec_tracker);
        $status_field    = \Mockery::mock(Tracker_FormElement_Field_List::class);
        $status_field_id = 4444;
        $status_field->shouldReceive('getId')->andReturn($status_field_id);
        $status_field->shouldReceive('userCanRead')->andReturn(true);
        $test_exec_tracker->shouldReceive('getStatusField')->andReturn($status_field);
        $campaign_art_link_field_id = 852;
        $this->form_element_factory->shouldReceive('getAnArtifactLinkField')
            ->with($this->user, $this->campaign_tracker)
            ->andReturn(
                \Mockery::mock(\Tracker_FormElement_Field_ArtifactLink::class)->shouldReceive('getId')->andReturn($campaign_art_link_field_id)->getMock()
            );

        $information = InformationNeededToRetrieveTestStatusOfACampaign::fromCampaign(
            $this->campaign,
            $this->user,
            $this->testmanagement_config,
            $this->tracker_factory,
            $this->form_element_factory
        );
        $this->assertSame(self::CAMPAIGN_ID, $information->campaign_id);
        $this->assertSame(self::USER_UGROUP_IDS, $information->current_user_ugroup_ids);
        $this->assertSame($status_field_id, $information->test_exec_status_field_id);
        $this->assertSame($campaign_art_link_field_id, $information->test_campaign_art_link_field_id);
    }

    public function testUsersCannotAccessTheInformationWhenTheyCannotViewTheCampaign(): void
    {
        $this->campaign->shouldReceive('userCanView')->andReturn(false);

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
        $this->campaign->shouldReceive('userCanView')->andReturn(true);
        $this->form_element_factory->shouldReceive('getAnArtifactLinkField')
            ->with($this->user, $this->campaign_tracker)
            ->andReturn(null);

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
        $this->campaign->shouldReceive('userCanView')->andReturn(true);
        $this->testmanagement_config->shouldReceive('getTestExecutionTrackerId')->andReturn(11);
        $test_exec_tracker = $this->buildTracker(true);
        $this->tracker_factory->shouldReceive('getTrackerById')->with(11)->andReturn($test_exec_tracker);
        $status_field = \Mockery::mock(Tracker_FormElement_Field_List::class);
        $status_field->shouldReceive('getId')->andReturn(4444);
        $status_field->shouldReceive('userCanRead')->andReturn(false);
        $test_exec_tracker->shouldReceive('getStatusField')->andReturn($status_field);
        $this->form_element_factory->shouldReceive('getAnArtifactLinkField')
            ->with($this->user, $this->campaign_tracker)
            ->andReturn(
                \Mockery::mock(\Tracker_FormElement_Field_ArtifactLink::class)->shouldReceive('getId')->andReturn(852)->getMock()
            );

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
        $this->campaign->shouldReceive('userCanView')->andReturn(true);
        $this->testmanagement_config->shouldReceive('getTestExecutionTrackerId')->andReturn(11);
        $test_exec_tracker = $this->buildTracker(true);
        $this->tracker_factory->shouldReceive('getTrackerById')->with(11)->andReturn($test_exec_tracker);
        $test_exec_tracker->shouldReceive('getStatusField')->andReturn(null);
        $this->form_element_factory->shouldReceive('getAnArtifactLinkField')
            ->with($this->user, $this->campaign_tracker)
            ->andReturn(
                \Mockery::mock(\Tracker_FormElement_Field_ArtifactLink::class)->shouldReceive('getId')->andReturn(852)->getMock()
            );

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
        $this->campaign->shouldReceive('userCanView')->andReturn(true);
        $this->testmanagement_config->shouldReceive('getTestExecutionTrackerId')->andReturn(11);
        $test_exec_tracker = $this->buildTracker(false);
        $this->tracker_factory->shouldReceive('getTrackerById')->with(11)->andReturn($test_exec_tracker);
        $this->form_element_factory->shouldReceive('getAnArtifactLinkField')
            ->with($this->user, $this->campaign_tracker)
            ->andReturn(
                \Mockery::mock(\Tracker_FormElement_Field_ArtifactLink::class)->shouldReceive('getId')->andReturn(852)->getMock()
            );

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
        $this->campaign->shouldReceive('userCanView')->andReturn(true);
        $this->testmanagement_config->shouldReceive('getTestExecutionTrackerId')->andReturn(false);
        $this->form_element_factory->shouldReceive('getAnArtifactLinkField')
            ->with($this->user, $this->campaign_tracker)
            ->andReturn(
                \Mockery::mock(\Tracker_FormElement_Field_ArtifactLink::class)->shouldReceive('getId')->andReturn(852)->getMock()
            );

        $information = InformationNeededToRetrieveTestStatusOfACampaign::fromCampaign(
            $this->campaign,
            $this->user,
            $this->testmanagement_config,
            $this->tracker_factory,
            $this->form_element_factory
        );

        $this->assertNull($information);
    }

    /**
     * @return \Tracker&\Mockery\MockInterface
     */
    private function buildTracker(bool $can_user_view_it): \Tracker
    {
        $tracker = \Mockery::mock(\Tracker::class);
        $tracker->shouldReceive('userCanView')->andReturn($can_user_view_it);

        return $tracker;
    }
}
