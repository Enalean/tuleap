<?php
/**
 * Copyright (c) Enalean, 2023 - Present. All Rights Reserved.
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

namespace Tuleap\MediawikiStandalone\Instance;

use Tuleap\DB\DBFactory;
use Tuleap\Test\PHPUnit\TestCase;

final class OngoingInitializationsDaoTest extends TestCase
{
    protected function tearDown(): void
    {
        DBFactory::getMainTuleapDBConnection()
            ->getDB()
            ->run('DELETE FROM plugin_mediawiki_standalone_ongoing_initializations');
    }

    public function testStartOngoingMigration(): void
    {
        $dao = new OngoingInitializationsDao();

        self::assertFalse($dao->isOngoingMigration(101));
        $dao->startInitialization(101);
        self::assertTrue($dao->isOngoingMigration(101));

        // ignore already started initializations
        $dao->startInitialization(101);
        self::assertTrue($dao->isOngoingMigration(101));
        self::assertFalse($dao->isInError(101));
    }

    public function testError(): void
    {
        $dao = new OngoingInitializationsDao();

        $dao->startInitialization(101);
        self::assertFalse($dao->isInError(101));

        $dao->markAsError(101);
        self::assertTrue($dao->isInError(101));
        self::assertTrue($dao->isOngoingMigration(101));
    }
}
