<?php
/**
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
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

use Tuleap\DB\DBFactory;
use Tuleap\Test\PHPUnit\TestCase;

final class BannerDaoTest extends TestCase
{
    private BannerDao $dao;

    public function setUp(): void
    {
        $this->dao = new BannerDao();
    }

    public static function tearDownAfterClass(): void
    {
        $db = DBFactory::getMainTuleapDBConnection()->getDB();

        $db->run('DELETE FROM platform_banner');
    }

    public function testCreatesABanner(): void
    {
        $message    = 'Some message';
        $importance = 'critical';
        $this->dao->addBanner($message, $importance, null);
        self::assertEquals(
            ['message' => $message, 'importance' => $importance, 'expiration_date' => null],
            $this->dao->searchBanner()
        );
        self::assertEquals(
            ['message' => $message, 'importance' => $importance, 'preference_value' => null],
            $this->dao->searchNonExpiredBannerWithVisibility(102, new \DateTimeImmutable('@100'))
        );
    }

    public function testDoesNotRetrieveExpiredBannerForEndUsers(): void
    {
        $current_time          = new \DateTimeImmutable('@100');
        $somewhere_in_the_past = $current_time->modify('-10 seconds');

        $this->dao->addBanner('A message', 'critical', $somewhere_in_the_past);

        self::assertNotNull($this->dao->searchBanner());
        self::assertEquals(
            null,
            $this->dao->searchNonExpiredBannerWithVisibility(102, $current_time)
        );
    }
}
