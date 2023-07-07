<?php
/**
 * Copyright (c) Enalean, 2023-Present. All Rights Reserved.
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

namespace integration\Version;

use Tuleap\DB\DBFactory;
use Tuleap\Docman\Version\CoAuthorDao;
use Tuleap\Test\PHPUnit\TestCase;

final class CoAuthorDaoTest extends TestCase
{
    private CoAuthorDao $co_author_dao;

    protected function setUp(): void
    {
        $this->co_author_dao = new CoAuthorDao();
    }

    protected function tearDown(): void
    {
        $db = DBFactory::getMainTuleapDBConnection()->getDB();
        $db->run('DELETE FROM plugin_docman_version_coauthor');
    }

    public function testCanSavesCoAuthors(): void
    {
        $version_id = 12;
        $this->co_author_dao->saveVersionCoAuthors($version_id, [102, 103, 102]);

        self::assertEqualsCanonicalizing(
            [['version_id' => $version_id, 'user_id' => 102], ['version_id' => $version_id, 'user_id' => 103]],
            $this->co_author_dao->searchByVersionId($version_id)
        );
    }
}
