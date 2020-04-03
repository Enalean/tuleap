<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

namespace Tuleap\Project\Banner;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PFUser;
use PHPUnit\Framework\TestCase;
use Project;

final class BannerRetrieverTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|BannerDao
     */
    private $banner_dao;
    /**
     * @var BannerRetriever
     */
    private $banner_retriever;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|Project
     */
    private $project;

    protected function setUp(): void
    {
        $this->banner_dao        = Mockery::mock(BannerDao::class);
        $this->banner_retriever = new BannerRetriever($this->banner_dao);

        $this->project = Mockery::mock(Project::class);
        $this->project->shouldReceive('getID')->andReturn('102');
    }

    public function testBannerCanBeRetrievedWhenItExists(): void
    {
        $expected_banner_message = 'banner message';
        $this->banner_dao->shouldReceive('searchBannerByProjectId')->andReturn($expected_banner_message);

        $banner = $this->banner_retriever->getBannerForProject($this->project);

        $this->assertEquals($expected_banner_message, $banner->getMessage());
    }

    public function testCanCheckBannerDoesNotExistForAProject(): void
    {
        $this->banner_dao->shouldReceive('searchBannerByProjectId')->andReturn(null);

        $banner = $this->banner_retriever->getBannerForProject($this->project);

        $this->assertNull($banner);
    }

    public function testBannerVisibilityForAUserThatHasNotOptOutFromTheProjectBanner(): void
    {
        $expected_banner_message = 'banner message';
        $this->banner_dao->shouldReceive('searchBannerWithVisibilityByProjectID')->andReturn(
            ['message' => $expected_banner_message, 'preference_value' => null]
        );

        $user = Mockery::mock(PFUser::class);
        $user->shouldReceive('getId')->andReturn('1200');

        $banner = $this->banner_retriever->getBannerForDisplayPurpose($this->project, $user);

        $this->assertEquals($expected_banner_message, $banner->getMessage());
        $this->assertTrue($banner->isVisible());
    }

    public function testBannerVisibilityForAUserThatHasOptOutFromTheProjectBanner(): void
    {
        $expected_banner_message = 'banner message';
        $this->banner_dao->shouldReceive('searchBannerWithVisibilityByProjectID')->andReturn(
            ['message' => $expected_banner_message, 'preference_value' => 'hidden']
        );

        $user = Mockery::mock(PFUser::class);
        $user->shouldReceive('getId')->andReturn('1200');

        $banner = $this->banner_retriever->getBannerForDisplayPurpose($this->project, $user);

        $this->assertEquals($expected_banner_message, $banner->getMessage());
        $this->assertFalse($banner->isVisible());
    }

    public function testCanCheckBannerVisibilityDoesNotExistForAProject(): void
    {
        $this->banner_dao->shouldReceive('searchBannerWithVisibilityByProjectID')->andReturn(null);

        $user = Mockery::mock(PFUser::class);
        $user->shouldReceive('getId')->andReturn('1200');

        $banner = $this->banner_retriever->getBannerForDisplayPurpose($this->project, $user);

        $this->assertNull($banner);
    }
}
