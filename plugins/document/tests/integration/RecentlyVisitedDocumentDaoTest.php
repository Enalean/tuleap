<?php
/**
 * Copyright (c) Enalean, 2024 - Present. All Rights Reserved.
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

namespace Tuleap\Document\Tests;

use Tuleap\DB\DBFactory;
use Tuleap\Document\RecentlyVisited\RecentlyVisitedDocumentDao;
use Tuleap\Test\PHPUnit\TestIntegrationTestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class RecentlyVisitedDocumentDaoTest extends TestIntegrationTestCase
{
    public function testDeleteOldEntriesPerUser(): void
    {
        $db = DBFactory::getMainTuleapDBConnection();

        $dao = new RecentlyVisitedDocumentDao();
        $i   = 1;
        while ($i <= 60) {
            $db->getDB()->insert('plugin_docman_item', [
                'item_id'     => $db->getDB()->insertReturnId('plugin_docman_item_id', []),
                'title'       => 'Document ' . $i,
                'description' => '',
            ]);
            $dao->save(102, $i, $i);
            $dao->save(103, $i, $i);
            $i++;
        }
        self::assertCount(60, $dao->searchVisitByUserId(102, 100));
        self::assertCount(60, $dao->searchVisitByUserId(103, 100));

        $dao->deleteOldVisits();

        self::assertCount(30, $dao->searchVisitByUserId(102, 100));
        self::assertCount(30, $dao->searchVisitByUserId(103, 100));
    }
}
