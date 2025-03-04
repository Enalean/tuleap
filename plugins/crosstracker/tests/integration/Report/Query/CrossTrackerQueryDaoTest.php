<?php
/**
 * Copyright (c) Enalean, 2025-Present. All Rights Reserved.
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

namespace Tuleap\CrossTracker\Report\Query;

use Tuleap\Test\PHPUnit\TestIntegrationTestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class CrossTrackerQueryDaoTest extends TestIntegrationTestCase
{
    private CrossTrackerQueryDao $query_dao;

    protected function setUp(): void
    {
        $this->query_dao = new CrossTrackerQueryDao();
    }

    public function testCreate(): void
    {
        $uuid = $this->query_dao->create('SELECT @id FROM @project = "self" WHERE @id = 1', 'title', 'description', 1);
        self::assertNotNull($this->query_dao->searchQueryByUuid($uuid->toString()));
        $queries_by_widget_id = $this->query_dao->searchQueriesByWidgetId(1);
        self::assertCount(1, $queries_by_widget_id);
        self::assertSame($uuid->toString(), $queries_by_widget_id[0]['id']->toString());
        self::assertSame('title', $queries_by_widget_id[0]['title']);
        self::assertSame('description', $queries_by_widget_id[0]['description']);
    }

    public function testUpdate(): void
    {
        $uuid = $this->query_dao->create('SELECT @id FROM @project = "self" WHERE @id = 1', 'title', 'description', 1);
        $this->query_dao->update($uuid, 'SELECT nothing', 'foo', 'bar');
        $query = $this->query_dao->searchQueryByUuid($uuid->toString());
        self::assertNotNull($query);
        self::assertSame($uuid->toString(), $query['id']->toString());
        self::assertSame('SELECT nothing', $query['query']);
        self::assertSame('foo', $query['title']);
        self::assertSame('bar', $query['description']);
        self::assertSame(1, $query['widget_id']);
    }

    public function testDelete(): void
    {
        $uuid = $this->query_dao->create('SELECT @id FROM @project = "self" WHERE @id = 1', 'title', 'description', 1);
        self::assertNotNull($this->query_dao->searchQueryByUuid($uuid->toString()));
        $this->query_dao->delete($uuid);
        self::assertNull($this->query_dao->searchQueryByUuid($uuid->toString()));
    }
}
