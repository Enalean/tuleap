<?php
/**
 * Copyright (c) Enalean, 2025-present. All Rights Reserved.
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
namespace Tuleap\CrossTracker\Query;

use Tuleap\CrossTracker\Tests\Builders\CrossTrackerQueryUnitTestBuilder;
use Tuleap\CrossTracker\Tests\Stub\Query\InsertNewQueryStub;
use Tuleap\CrossTracker\Tests\Stub\Query\ResetIsDefaultColumnStub;
use Tuleap\DB\UUID;
use Tuleap\Test\DB\DBTransactionExecutorPassthrough;
use Tuleap\Test\DB\UUIDTestContext;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class QueryCreatorTest extends TestCase
{
    private ResetIsDefaultColumnStub $reset_is_default_column_query_dao;
    private UUID $uuid;

    #[\Override]
    protected function setUp(): void
    {
        $this->uuid                              = new UUIDTestContext();
        $this->reset_is_default_column_query_dao =  ResetIsDefaultColumnStub::build();
    }

    private function createNewQuery(CrossTrackerQuery $query): CrossTrackerQuery
    {
        $query_creator = new QueryCreator(
            new DBTransactionExecutorPassthrough(),
            InsertNewQueryStub::withUUID($this->uuid),
            $this->reset_is_default_column_query_dao
        );
        return $query_creator->createNewQuery($query);
    }

    public function testNewQueryCreationAndSetTheNewDefaultQueryIfTheCreatedQueryIsNewDefaultQuery(): void
    {
        $query = CrossTrackerQueryUnitTestBuilder::aQuery()->withUUID($this->uuid)->isDefault()->build();

        $result = $this->createNewQuery($query);

        self::assertSame($this->uuid, $result->getUUID());
        self::assertSame(1, $this->reset_is_default_column_query_dao->getCallCount());
    }

    public function testNewQueryCreationOfANonDefaultQuery(): void
    {
        $query = CrossTrackerQueryUnitTestBuilder::aQuery()->withUUID($this->uuid)->build();

        $result = $this->createNewQuery($query);

        self::assertSame($this->uuid, $result->getUUID());
        self::assertSame(0, $this->reset_is_default_column_query_dao->getCallCount());
    }
}
