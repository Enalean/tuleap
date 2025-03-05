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

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class BannerCreatorTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testBannerIsCreated(): void
    {
        $dao = $this->createMock(BannerDao::class);
        $dao->expects(self::once())
            ->method('addBanner')
            ->with('The message', 'critical', null);

        $banner_creator = new BannerCreator($dao);

        $banner_creator->addBanner('The message', 'critical', null, new \DateTimeImmutable('@1'));
    }

    public function testBannerWithAnExpirationDateIsCreated(): void
    {
        $expiration_date = new \DateTimeImmutable('@2');

        $dao = $this->createMock(BannerDao::class);
        $dao->expects(self::once())
            ->method('addBanner')
            ->with('The message', 'critical', $expiration_date);

        $banner_creator = new BannerCreator($dao);

        $banner_creator->addBanner('The message', 'critical', $expiration_date, new \DateTimeImmutable('@1'));
    }

    public function testBannerWithAnExpirationDateInThePastIsRejected(): void
    {
        $banner_creator = new BannerCreator($this->createStub(BannerDao::class));

        $this->expectException(CannotCreateAnAlreadyExpiredBannerException::class);

        $banner_creator->addBanner('The message', 'critical', new \DateTimeImmutable('@2'), new \DateTimeImmutable('@10'));
    }
}
