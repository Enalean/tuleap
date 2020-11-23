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

namespace Tuleap\AgileDashboard\Milestone;

use AgileDashboard_PaneInfoIdentifier;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tuleap\AgileDashboard\Planning\HeaderOptionsForPlanningProvider;

class HeaderOptionsProviderTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var HeaderOptionsProvider
     */
    private $provider;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|HeaderOptionsForPlanningProvider
     */
    private $header_options_for_planning_provider;
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
        $this->header_options_for_planning_provider = Mockery::mock(HeaderOptionsForPlanningProvider::class);

        $this->provider = new HeaderOptionsProvider(
            new AgileDashboard_PaneInfoIdentifier(),
            $this->header_options_for_planning_provider,
        );

        $this->user      = Mockery::mock(\PFUser::class);
        $this->milestone = Mockery::mock(\Planning_Milestone::class);
    }

    public function testGetHeaderOptionsForPV2(): void
    {
        $this->header_options_for_planning_provider->shouldReceive('addPlanningOptions')->once();

        self::assertEquals(
            [
                'include_fat_combined' => false,
                'body_class'           => ['agiledashboard-body'],
            ],
            $this->provider->getHeaderOptions($this->user, $this->milestone, 'planning-v2'),
        );
    }

    public function testGetHeaderOptionsForTopPV2(): void
    {
        $this->header_options_for_planning_provider->shouldReceive('addPlanningOptions')->once();

        self::assertEquals(
            [
                'include_fat_combined' => false,
                'body_class'           => ['agiledashboard-body'],
            ],
            $this->provider->getHeaderOptions($this->user, $this->milestone, 'topplanning-v2'),
        );
    }

    public function testGetHeaderOptionsForOverview(): void
    {
        self::assertEquals(
            [
                'include_fat_combined' => true,
                'body_class'           => ['agiledashboard-body'],
            ],
            $this->provider->getHeaderOptions($this->user, $this->milestone, 'details'),
        );
    }
}
