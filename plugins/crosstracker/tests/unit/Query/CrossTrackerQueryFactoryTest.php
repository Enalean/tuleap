<?php
/**
 *  Copyright (c) Enalean, 2017 - Present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
 *
 *  Tuleap is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  Tuleap is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace Tuleap\CrossTracker\Query;

use LogicException;
use Tuleap\CrossTracker\REST\v1\CrossTrackerQueryNotFoundException;
use Tuleap\CrossTracker\Tests\Stub\Query\RetrieveQueriesStub;
use Tuleap\DB\DatabaseUUIDV7Factory;
use Tuleap\DB\UUID;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class CrossTrackerQueryFactoryTest extends TestCase
{
    private const QUERY_ID = '00000000-1b58-7366-ad1b-dfe646c5ce9d';

    private UUID $query_id;

    protected function setUp(): void
    {
        $query_id = (new DatabaseUUIDV7Factory())->buildUUIDFromHexadecimalString(self::QUERY_ID)->unwrapOr(null);
        if ($query_id === null) {
            throw new LogicException('UUID factory failed to build from "00000000-1b58-7366-ad1b-dfe646c5ce9d"');
        }
        $this->query_id = $query_id;
    }

    /**
     * @throws CrossTrackerQueryNotFoundException
     */
    private function getById(string $query_id): CrossTrackerQuery
    {
        $queries_retriever = RetrieveQueriesStub::withQueries([
            'id'          => $this->query_id,
            'query'       => '',
            'title'       => '',
            'description' => '',
            'widget_id'   => 1,
            'is_default'   => false,
        ]);
        $factory           = new CrossTrackerQueryFactory($queries_retriever);
        return $factory->getById($query_id);
    }

    private function getByWidgetId(int $id): array
    {
        $queries_retriever = RetrieveQueriesStub::withQueries([
            'id'          => $this->query_id,
            'query'       => '',
            'title'       => '',
            'description' => '',
            'widget_id'   => 1,
            'is_default'   => false,
        ]);
        $factory           = new CrossTrackerQueryFactory($queries_retriever);
        return $factory->getByWidgetId($id);
    }

    public function testItThrowsAnExceptionWhenWidgetIsNotFound(): void
    {
        $this->expectException(CrossTrackerQueryNotFoundException::class);
        $this->getById('something');
    }

    public function testItReturnsAQuery(): void
    {
        $expected_result = new CrossTrackerQuery(
            $this->query_id,
            '',
            '',
            '',
            1,
            false,
        );

        $result = $this->getById(self::QUERY_ID);

        self::assertInstanceOf(CrossTrackerQuery::class, $result);
        self::assertEquals($expected_result, $result);
    }

    public function testItReturnsEmptyArrayWhenWidgetHasNoQuery(): void
    {
        self::assertEmpty($this->getByWidgetId(404));
    }

    public function testItReturnsArrayOfQueries(): void
    {
        $expected_result = new CrossTrackerQuery(
            $this->query_id,
            '',
            '',
            '',
            1,
            false
        );

        $result = $this->getByWidgetId(1);
        self::assertEquals([$expected_result], $result);
    }
}
