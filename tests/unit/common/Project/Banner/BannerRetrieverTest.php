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

use PHPUnit\Framework\MockObject\MockObject;
use Project;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;

final class BannerRetrieverTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private BannerDao&MockObject $banner_dao;
    private BannerRetriever $banner_retriever;
    private Project $project;

    protected function setUp(): void
    {
        $this->banner_dao       = $this->createMock(BannerDao::class);
        $this->banner_retriever = new BannerRetriever($this->banner_dao);

        $this->project = ProjectTestBuilder::aProject()->withId(102)->build();
    }

    public function testBannerCanBeRetrievedWhenItExists(): void
    {
        $expected_banner_message = 'banner message';
        $this->banner_dao->method('searchBannerByProjectId')->willReturn($expected_banner_message);

        $banner = $this->banner_retriever->getBannerForProject($this->project);

        self::assertEquals($expected_banner_message, $banner->getMessage());
    }

    public function testCanCheckBannerDoesNotExistForAProject(): void
    {
        $this->banner_dao->method('searchBannerByProjectId')->willReturn(null);

        $banner = $this->banner_retriever->getBannerForProject($this->project);

        self::assertNull($banner);
    }

    public function testBannerVisibilityForAUserThatHasNotOptOutFromTheProjectBanner(): void
    {
        $expected_banner_message = 'banner message';
        $this->banner_dao->method('searchBannerWithVisibilityByProjectID')->willReturn(
            ['message' => $expected_banner_message, 'preference_value' => null]
        );

        $user = UserTestBuilder::aUser()->withId(1200)->build();

        $banner = $this->banner_retriever->getBannerForDisplayPurpose($this->project, $user);

        self::assertEquals($expected_banner_message, $banner->getMessage());
        self::assertTrue($banner->isVisible());
    }

    public function testBannerVisibilityForAUserThatHasOptOutFromTheProjectBanner(): void
    {
        $expected_banner_message = 'banner message';
        $this->banner_dao->method('searchBannerWithVisibilityByProjectID')->willReturn(
            ['message' => $expected_banner_message, 'preference_value' => 'hidden']
        );

        $user = UserTestBuilder::aUser()->withId(1200)->build();

        $banner = $this->banner_retriever->getBannerForDisplayPurpose($this->project, $user);

        self::assertEquals($expected_banner_message, $banner->getMessage());
        self::assertFalse($banner->isVisible());
    }

    public function testCanCheckBannerVisibilityDoesNotExistForAProject(): void
    {
        $this->banner_dao->method('searchBannerWithVisibilityByProjectID')->willReturn(null);

        $user = UserTestBuilder::aUser()->withId(1200)->build();

        $banner = $this->banner_retriever->getBannerForDisplayPurpose($this->project, $user);

        self::assertNull($banner);
    }
}
