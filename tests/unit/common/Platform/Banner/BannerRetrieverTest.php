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

use PHPUnit\Framework\MockObject\MockObject;
use Tuleap\Test\Builders\UserTestBuilder;

final class BannerRetrieverTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private BannerDao&MockObject $banner_dao;
    private BannerRetriever $banner_retriever;

    protected function setUp(): void
    {
        $this->banner_dao       = $this->createMock(BannerDao::class);
        $this->banner_retriever = new BannerRetriever($this->banner_dao);
    }

    public function testBannerCanBeRetrievedWhenItExists(): void
    {
        $expected_banner_message         = 'banner message';
        $expected_banner_importance      = 'critical';
        $expected_banner_expiration_time = 1;
        $this->banner_dao->method('searchBanner')->willReturn(
            [
                'message' => $expected_banner_message,
                'importance' => $expected_banner_importance,
                'expiration_date' => $expected_banner_expiration_time,
            ]
        );

        $banner = $this->banner_retriever->getBanner();

        self::assertEquals($expected_banner_message, $banner->getMessage());
        self::assertEquals($expected_banner_importance, $banner->getImportance());
        self::assertEquals($expected_banner_expiration_time, $banner->getExpirationDate()->getTimestamp());
    }

    public function testBannerDoesNotExist(): void
    {
        $this->banner_dao->method('searchBanner')->willReturn(null);

        self::assertNull($this->banner_retriever->getBanner());
    }

    public function testBannerVisibilityForAUserThatHasNotOptOutFromTheProjectBanner(): void
    {
        $expected_banner_message    = 'banner message';
        $expected_banner_importance = 'critical';
        $this->banner_dao->method('searchNonExpiredBannerWithVisibility')->willReturn(
            [
                'message' => $expected_banner_message,
                'importance' => $expected_banner_importance,
                'preference_value' => null,
            ]
        );

        $user = UserTestBuilder::aUser()->withId(1200)->build();

        $banner = $this->banner_retriever->getBannerForDisplayPurpose($user, new \DateTimeImmutable('@10'));

        self::assertEquals($expected_banner_message, $banner->getMessage());
        self::assertEquals($expected_banner_importance, $banner->getImportance());
        self::assertTrue($banner->isVisible());
    }

    public function testBannerVisibilityForAUserThatHasOptOutFromTheProjectBanner(): void
    {
        $expected_banner_message    = 'banner message';
        $expected_banner_importance = 'critical';
        $this->banner_dao->method('searchNonExpiredBannerWithVisibility')->willReturn(
            [
                'message' => $expected_banner_message,
                'importance' => $expected_banner_importance,
                'preference_value' => 'hidden',
            ]
        );

        $user = UserTestBuilder::aUser()->withId(1200)->build();

        $banner = $this->banner_retriever->getBannerForDisplayPurpose($user, new \DateTimeImmutable('@10'));

        self::assertEquals($expected_banner_message, $banner->getMessage());
        self::assertEquals($expected_banner_importance, $banner->getImportance());
        self::assertFalse($banner->isVisible());
    }

    public function testCanCheckBannerVisibilityDoesNotExistForAProject(): void
    {
        $this->banner_dao->method('searchNonExpiredBannerWithVisibility')->willReturn(null);

        $user = UserTestBuilder::aUser()->withId(1200)->build();

        $banner = $this->banner_retriever->getBannerForDisplayPurpose($user, new \DateTimeImmutable('@10'));

        self::assertNull($banner);
    }
}
