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

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Project;
use Tracker;
use TrackerFactory;
use Tuleap\AgileDashboard\Milestone\HeaderOptionsProvider;
use Tuleap\layout\NewDropdown\CurrentContextSectionToHeaderOptionsInserter;
use Tuleap\layout\NewDropdown\NewDropdownLinkPresenter;
use Tuleap\layout\NewDropdown\NewDropdownLinkSectionPresenter;
use Tuleap\TestManagement\Config;
use Tuleap\Tracker\NewDropdown\TrackerNewDropdownLinkPresenterBuilder;

class TestPlanHeaderOptionsProviderTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|HeaderOptionsProvider
     */
    private $header_options_provider;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|Config
     */
    private $testmanagement_config;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|TrackerFactory
     */
    private $tracker_factory;
    /**
     * @var TestPlanHeaderOptionsProvider
     */
    private $provider;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|\PFUser
     */
    private $user;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|\Planning_Milestone
     */
    private $milestone;

    protected function setUp(): void
    {
        $this->header_options_provider = Mockery::mock(HeaderOptionsProvider::class);
        $this->testmanagement_config   = Mockery::mock(Config::class);
        $this->tracker_factory         = Mockery::mock(TrackerFactory::class);
        $presenter_builder             = new TrackerNewDropdownLinkPresenterBuilder();
        $header_options_inserter       = new CurrentContextSectionToHeaderOptionsInserter();

        $this->provider = new TestPlanHeaderOptionsProvider(
            $this->header_options_provider,
            $this->testmanagement_config,
            $this->tracker_factory,
            $presenter_builder,
            $header_options_inserter,
        );

        $this->user      = Mockery::mock(\PFUser::class);
        $this->milestone = Mockery::mock(\Planning_Milestone::class)
            ->shouldReceive(
                [
                    'getProject'       => Mockery::mock(Project::class),
                    'getArtifactTitle' => 'Milestone title',
                ]
            )->getMock();
    }

    public function testItAddsTheFluidMainToExistingMainClasses(): void
    {
        $this->header_options_provider
            ->shouldReceive(
                [
                    'getHeaderOptions' => [
                        'main_classes' => ['toto'],
                    ],
                ]
            );

        $this->testmanagement_config
            ->shouldReceive('getCampaignTrackerId')
            ->andReturnFalse();

        $header_options = $this->provider->getHeaderOptions($this->user, $this->milestone);

        self::assertEquals(['toto', 'fluid-main'], $header_options['main_classes']);
    }

    public function testItAddsTheFluidMainToMainClasses(): void
    {
        $this->header_options_provider
            ->shouldReceive(
                [
                    'getHeaderOptions' => [],
                ]
            );

        $this->testmanagement_config
            ->shouldReceive('getCampaignTrackerId')
            ->andReturnFalse();

        $header_options = $this->provider->getHeaderOptions($this->user, $this->milestone);

        self::assertEquals(['fluid-main'], $header_options['main_classes']);
    }

    public function testItDoesNotAddLinkToCampaignInCurrentContextSectionIfThereIsNoCampaignTrackerIdInConfig(): void
    {
        $this->header_options_provider
            ->shouldReceive(
                [
                    'getHeaderOptions' => [],
                ]
            );

        $this->testmanagement_config
            ->shouldReceive('getCampaignTrackerId')
            ->andReturnFalse();

        $header_options = $this->provider->getHeaderOptions($this->user, $this->milestone);

        self::assertFalse(isset($header_options['new_dropdown_current_context_section']));
    }

    public function testItDoesNotAddLinkToCampaignInCurrentContextSectionIfTheCampaignTrackerCannotBeInstantiated(): void
    {
        $this->header_options_provider
            ->shouldReceive(
                [
                    'getHeaderOptions' => [],
                ]
            );

        $this->testmanagement_config
            ->shouldReceive('getCampaignTrackerId')
            ->andReturn(42);

        $this->tracker_factory
            ->shouldReceive('getTrackerById')
            ->andReturnNull();

        $header_options = $this->provider->getHeaderOptions($this->user, $this->milestone);

        self::assertFalse(isset($header_options['new_dropdown_current_context_section']));
    }

    public function testItDoesNotAddLinkToCampaignInCurrentContextSectionIfUserCannotCreateArtifactInTheCampaignTracker(): void
    {
        $this->header_options_provider
            ->shouldReceive(
                [
                    'getHeaderOptions' => [],
                ]
            );

        $this->testmanagement_config
            ->shouldReceive('getCampaignTrackerId')
            ->andReturn(42);

        $tracker = Mockery::mock(Tracker::class)
            ->shouldReceive(['userCanSubmitArtifact' => false])
            ->getMock();

        $this->tracker_factory
            ->shouldReceive('getTrackerById')
            ->andReturn($tracker);

        $header_options = $this->provider->getHeaderOptions($this->user, $this->milestone);

        self::assertFalse(isset($header_options['new_dropdown_current_context_section']));
    }

    public function testItAddsLinkToCampaignInCurrentContextSection(): NewDropdownLinkSectionPresenter
    {
        $this->header_options_provider
            ->shouldReceive(
                [
                    'getHeaderOptions' => [],
                ]
            );

        $this->testmanagement_config
            ->shouldReceive('getCampaignTrackerId')
            ->andReturn(42);

        $tracker = Mockery::mock(Tracker::class)
            ->shouldReceive(
                [
                    'getId'                 => 102,
                    'getSubmitUrl'          => '/path/to/102',
                    'getItemName'           => 'campaign',
                    'userCanSubmitArtifact' => true,
                ]
            )->getMock();

        $this->tracker_factory
            ->shouldReceive('getTrackerById')
            ->andReturn($tracker);

        $header_options = $this->provider->getHeaderOptions($this->user, $this->milestone);

        $section = $header_options['new_dropdown_current_context_section'];
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
            ->shouldReceive(
                [
                    'getHeaderOptions' => [
                        'new_dropdown_current_context_section' => new NewDropdownLinkSectionPresenter(
                            'Milestone title',
                            [
                                new NewDropdownLinkPresenter('url', 'New story', 'icon', []),
                            ]
                        ),
                    ],
                ]
            );

        $this->testmanagement_config
            ->shouldReceive('getCampaignTrackerId')
            ->andReturn(42);

        $tracker = Mockery::mock(Tracker::class)
            ->shouldReceive(
                [
                    'getId'                 => 102,
                    'getSubmitUrl'          => '/path/to/102',
                    'getItemName'           => 'campaign',
                    'userCanSubmitArtifact' => true,
                ]
            )->getMock();

        $this->tracker_factory
            ->shouldReceive('getTrackerById')
            ->andReturn($tracker);

        $header_options = $this->provider->getHeaderOptions($this->user, $this->milestone);

        $section = $header_options['new_dropdown_current_context_section'];
        self::assertEquals('Milestone title', $section->label);
        self::assertCount(2, $section->links);
        self::assertEquals('New story', $section->links[0]->label);
        self::assertEquals('New campaign', $section->links[1]->label);
    }
}
