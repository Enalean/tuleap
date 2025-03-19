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

use PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles;
use Tuleap\Test\PHPUnit\TestIntegrationTestCase;

#[DisableReturnValueGenerationForTestDoubles]
final class CrossTrackerQueryDaoTest extends TestIntegrationTestCase
{
    private CrossTrackerQueryDao $query_dao;

    protected function setUp(): void
    {
        $this->query_dao = new CrossTrackerQueryDao();
    }

    public function testCreate(): void
    {
        $uuid = $this->query_dao->create('SELECT @id FROM @project = "self" WHERE @id = 1', 'title', 'description', 1, false);
        self::assertNotNull($this->query_dao->searchQueryByUuid($uuid->toString()));
        $queries_by_widget_id = $this->query_dao->searchQueriesByWidgetId(1);
        self::assertCount(1, $queries_by_widget_id);
        self::assertSame($uuid->toString(), $queries_by_widget_id[0]['id']->toString());
        self::assertSame('title', $queries_by_widget_id[0]['title']);
        self::assertSame('description', $queries_by_widget_id[0]['description']);
    }

    public function testUpdate(): void
    {
        $uuid = $this->query_dao->create('SELECT @id FROM @project = "self" WHERE @id = 1', 'title', 'description', 1, false);
        $this->query_dao->update($uuid, 'SELECT nothing', 'foo', 'bar', true);
        $query = $this->query_dao->searchQueryByUuid($uuid->toString());
        self::assertNotNull($query);
        self::assertSame($uuid->toString(), $query['id']->toString());
        self::assertSame('SELECT nothing', $query['query']);
        self::assertSame('foo', $query['title']);
        self::assertSame('bar', $query['description']);
        self::assertSame(1, $query['widget_id']);
        self::assertTrue($query['is_default']);
    }

    public function testResetIsDefaultColumn(): void
    {
        $widget_id_change    = 1;
        $widget_id_no_change = 2;

        $uuid   = $this->query_dao->create('SELECT @id FROM @project = "self" WHERE @id = 1', 'title', 'description', $widget_id_change, false);
        $uuid_2 = $this->query_dao->create('SELECT @pretty_title FROM @project = "self" WHERE @id = 589', 'title 2', '', $widget_id_change, true);
        $uuid_3 = $this->query_dao->create('SELECT foo FROM bar', 'foo 2', 'description', $widget_id_no_change, true);

        $this->query_dao->resetIsDefaultColumnByWidgetId($widget_id_change);

        $query_1 = $this->query_dao->searchQueryByUuid($uuid->toString());
        self::assertNotNull($query_1);
        self::assertSame($uuid->toString(), $query_1['id']->toString());
        self::assertSame('SELECT @id FROM @project = "self" WHERE @id = 1', $query_1['query']);
        self::assertSame('title', $query_1['title']);
        self::assertSame('description', $query_1['description']);
        self::assertSame($widget_id_change, $query_1['widget_id']);
        self::assertFalse($query_1['is_default']);

        $query_2 = $this->query_dao->searchQueryByUuid($uuid_2->toString());
        self::assertNotNull($query_2);
        self::assertSame($uuid_2->toString(), $query_2['id']->toString());
        self::assertSame('SELECT @pretty_title FROM @project = "self" WHERE @id = 589', $query_2['query']);
        self::assertSame('title 2', $query_2['title']);
        self::assertSame('', $query_2['description']);
        self::assertSame($widget_id_change, $query_2['widget_id']);
        self::assertFalse($query_2['is_default']);

        $query_3 = $this->query_dao->searchQueryByUuid($uuid_3->toString());
        self::assertNotNull($query_3);
        self::assertSame($uuid_3->toString(), $query_3['id']->toString());
        self::assertSame('SELECT foo FROM bar', $query_3['query']);
        self::assertSame('foo 2', $query_3['title']);
        self::assertSame('description', $query_3['description']);
        self::assertSame($widget_id_no_change, $query_3['widget_id']);
        self::assertTrue($query_3['is_default']);
    }

    public function testDelete(): void
    {
        $uuid = $this->query_dao->create('SELECT @id FROM @project = "self" WHERE @id = 1', 'title', 'description', 1, false);
        self::assertNotNull($this->query_dao->searchQueryByUuid($uuid->toString()));
        $this->query_dao->delete($uuid);
        self::assertNull($this->query_dao->searchQueryByUuid($uuid->toString()));
    }
}
