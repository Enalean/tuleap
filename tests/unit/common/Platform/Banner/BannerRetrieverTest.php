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

namespace Tuleap\Platform\Banner;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PFUser;
use PHPUnit\Framework\TestCase;

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

    protected function setUp(): void
    {
        $this->banner_dao       = Mockery::mock(BannerDao::class);
        $this->banner_retriever = new BannerRetriever($this->banner_dao);
    }

    public function testBannerCanBeRetrievedWhenItExists(): void
    {
        $expected_banner_message    = 'banner message';
        $expected_banner_importance = 'critical';
        $this->banner_dao->shouldReceive('searchBanner')->andReturn(
            [
                'message'    => $expected_banner_message,
                'importance' => $expected_banner_importance,
            ]
        );

        $banner = $this->banner_retriever->getBanner();

        self::assertEquals($expected_banner_message, $banner->getMessage());
        self::assertEquals($expected_banner_importance, $banner->getImportance());
    }

    public function testBannerDoesNotExist(): void
    {
        $this->banner_dao->shouldReceive('searchBanner')->andReturn(null);

        self::assertNull($this->banner_retriever->getBanner());
    }

    public function testBannerVisibilityForAUserThatHasNotOptOutFromTheProjectBanner(): void
    {
        $expected_banner_message    = 'banner message';
        $expected_banner_importance = 'critical';
        $this->banner_dao->shouldReceive('searchBannerWithVisibility')->andReturn(
            [
                'message'          => $expected_banner_message,
                'importance'       => $expected_banner_importance,
                'preference_value' => null]
        );

        $user = Mockery::mock(PFUser::class);
        $user->shouldReceive('getId')->andReturn('1200');

        $banner = $this->banner_retriever->getBannerForDisplayPurpose($user);

        self::assertEquals($expected_banner_message, $banner->getMessage());
        self::assertEquals($expected_banner_importance, $banner->getImportance());
        self::assertTrue($banner->isVisible());
    }

    public function testBannerVisibilityForAUserThatHasOptOutFromTheProjectBanner(): void
    {
        $expected_banner_message    = 'banner message';
        $expected_banner_importance = 'critical';
        $this->banner_dao->shouldReceive('searchBannerWithVisibility')->andReturn(
            [
                'message'          => $expected_banner_message,
                'importance'       => $expected_banner_importance,
                'preference_value' => 'hidden'
            ]
        );

        $user = Mockery::mock(PFUser::class);
        $user->shouldReceive('getId')->andReturn('1200');

        $banner = $this->banner_retriever->getBannerForDisplayPurpose($user);

        self::assertEquals($expected_banner_message, $banner->getMessage());
        self::assertEquals($expected_banner_importance, $banner->getImportance());
        self::assertFalse($banner->isVisible());
    }

    public function testCanCheckBannerVisibilityDoesNotExistForAProject(): void
    {
        $this->banner_dao->shouldReceive('searchBannerWithVisibility')->andReturn(null);

        $user = Mockery::mock(PFUser::class);
        $user->shouldReceive('getId')->andReturn('1200');

        $banner = $this->banner_retriever->getBannerForDisplayPurpose($user);

        self::assertNull($banner);
    }
}
