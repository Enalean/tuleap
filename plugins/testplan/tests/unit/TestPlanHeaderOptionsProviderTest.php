<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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

namespace Tuleap\TestPlan;

use Project;
use Tracker;
use TrackerFactory;
use Tuleap\AgileDashboard\Milestone\HeaderOptionsProvider;
use Tuleap\Layout\NewDropdown\CurrentContextSectionToHeaderOptionsInserter;
use Tuleap\Layout\NewDropdown\NewDropdownLinkPresenter;
use Tuleap\Layout\NewDropdown\NewDropdownLinkSectionPresenter;
use Tuleap\Option\Option;
use Tuleap\TestManagement\Config;
use Tuleap\Tracker\NewDropdown\TrackerNewDropdownLinkPresenterBuilder;

final class TestPlanHeaderOptionsProviderTest extends \Tuleap\Test\PHPUnit\TestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&HeaderOptionsProvider
     */
    private mixed $header_options_provider;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&Config
     */
    private mixed $testmanagement_config;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&TrackerFactory
     */
    private mixed $tracker_factory;
    private TestPlanHeaderOptionsProvider $provider;
    /**
     * @var \PFUser&\PHPUnit\Framework\MockObject\MockObject
     */
    private mixed $user;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&\Planning_Milestone
     */
    private mixed $milestone;

    protected function setUp(): void
    {
        $this->header_options_provider = $this->createMock(HeaderOptionsProvider::class);
        $this->testmanagement_config   = $this->createMock(Config::class);
        $this->tracker_factory         = $this->createMock(TrackerFactory::class);
        $presenter_builder             = new TrackerNewDropdownLinkPresenterBuilder();
        $header_options_inserter       = new CurrentContextSectionToHeaderOptionsInserter();

        $this->provider = new TestPlanHeaderOptionsProvider(
            $this->header_options_provider,
            $this->testmanagement_config,
            $this->tracker_factory,
            $presenter_builder,
            $header_options_inserter,
        );

        $this->user      = $this->createMock(\PFUser::class);
        $this->milestone = $this->createMock(\Planning_Milestone::class);
        $this->milestone->method('getProject')->willReturn($this->createMock(Project::class));
        $this->milestone->method('getArtifactTitle')->willReturn('Milestone title');
    }

    public function testItDoesNotAddLinkToCampaignInCurrentContextSectionIfThereIsNoCampaignTrackerIdInConfig(): void
    {
        $this->header_options_provider
            ->method('getCurrentContextSection')
            ->willReturn(Option::nothing(NewDropdownLinkSectionPresenter::class));

        $this->testmanagement_config
            ->method('getCampaignTrackerId')
            ->willReturn(false);

        self::assertTrue($this->provider->getCurrentContextSection($this->user, $this->milestone)->isNothing());
    }

    public function testItDoesNotAddLinkToCampaignInCurrentContextSectionIfTheCampaignTrackerCannotBeInstantiated(): void
    {
        $this->header_options_provider
            ->method('getCurrentContextSection')
            ->willReturn(Option::nothing(NewDropdownLinkSectionPresenter::class));

        $this->testmanagement_config
            ->method('getCampaignTrackerId')
            ->willReturn(42);

        $this->tracker_factory
            ->method('getTrackerById')
            ->willReturn(null);

        self::assertTrue($this->provider->getCurrentContextSection($this->user, $this->milestone)->isNothing());
    }

    public function testItDoesNotAddLinkToCampaignInCurrentContextSectionIfUserCannotCreateArtifactInTheCampaignTracker(): void
    {
        $this->header_options_provider
            ->method('getCurrentContextSection')
            ->willReturn(Option::nothing(NewDropdownLinkSectionPresenter::class));

        $this->testmanagement_config
            ->method('getCampaignTrackerId')
            ->willReturn(42);

        $tracker = $this->createMock(Tracker::class);
        $tracker->method('userCanSubmitArtifact')->willReturn(false);

        $this->tracker_factory
            ->method('getTrackerById')
            ->willReturn($tracker);

        self::assertTrue($this->provider->getCurrentContextSection($this->user, $this->milestone)->isNothing());
    }

    public function testItAddsLinkToCampaignInCurrentContextSection(): NewDropdownLinkSectionPresenter
    {
        $this->header_options_provider
            ->method('getCurrentContextSection')
            ->willReturn(Option::nothing(NewDropdownLinkSectionPresenter::class));

        $this->testmanagement_config
            ->method('getCampaignTrackerId')
            ->willReturn(42);

        $tracker = $this->createMock(Tracker::class);
        $tracker->method('getId')->willReturn(102);
        $tracker->method('getSubmitUrl')->willReturn('/path/to/102');
        $tracker->method('getItemName')->willReturn('campaign');
        $tracker->method('userCanSubmitArtifact')->willReturn(true);

        $this->tracker_factory
            ->method('getTrackerById')
            ->willReturn($tracker);

        $section = $this->provider->getCurrentContextSection($this->user, $this->milestone)->unwrapOr(null);

        self::assertEquals('Milestone title', $section->label);
        self::assertCount(1, $section->links);
        self::assertEquals('New campaign', $section->links[0]->label);

        return $section;
    }

    /**
     * @depends testItAddsLinkToCampaignInCurrentContextSection
     */
    public function testItSetASpecialDataAttributeSoThatTestPlanAppCanSelectItMoreEasily(
        NewDropdownLinkSectionPresenter $section,
    ): void {
        self::assertEquals('test-plan-create-new-campaign', $section->links[0]->data_attributes[1]->name);
    }

    public function testItAddsLinkToCampaignInExistingCurrentContextSection(): void
    {
        $this->header_options_provider
            ->method('getCurrentContextSection')
            ->willReturn(Option::fromValue(
                new NewDropdownLinkSectionPresenter(
                    'Milestone title',
                    [
                        new NewDropdownLinkPresenter('url', 'New story', 'icon', []),
                    ]
                ),
            ));

        $this->testmanagement_config
            ->method('getCampaignTrackerId')
            ->willReturn(42);

        $tracker = $this->createMock(Tracker::class);
        $tracker->method('getId')->willReturn(102);
        $tracker->method('getSubmitUrl')->willReturn('/path/to/102');
        $tracker->method('getItemName')->willReturn('campaign');
        $tracker->method('userCanSubmitArtifact')->willReturn(true);

        $this->tracker_factory
            ->method('getTrackerById')
            ->willReturn($tracker);

        $section = $this->provider->getCurrentContextSection($this->user, $this->milestone)->unwrapOr(null);

        self::assertEquals('Milestone title', $section->label);
        self::assertCount(2, $section->links);
        self::assertEquals('New story', $section->links[0]->label);
        self::assertEquals('New campaign', $section->links[1]->label);
    }
}
