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
namespace Tuleap\CrossTracker\Report\Query;

use Tuleap\CrossTracker\Tests\Builders\CrossTrackerQueryUnitTestBuilder;
use Tuleap\CrossTracker\Tests\Stub\Report\Query\ResetIsDefaultColumnStub;
use Tuleap\CrossTracker\Tests\Stub\Report\Query\UpdateQueryStub;
use Tuleap\Test\DB\DBTransactionExecutorPassthrough;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class QueryUpdaterTest extends TestCase
{
    private ResetIsDefaultColumnStub $reset_is_default_column_query_dao;
    private UpdateQueryStub $updater_query_dao;

    protected function setUp(): void
    {
        $this->reset_is_default_column_query_dao =  ResetIsDefaultColumnStub::build();
        $this->updater_query_dao                 = UpdateQueryStub::build();
    }

    private function getQueryUpdater(): QueryUpdater
    {
        return new QueryUpdater(
            new DBTransactionExecutorPassthrough(),
            $this->updater_query_dao,
            $this->reset_is_default_column_query_dao
        );
    }

    public function testUpdateQueryAndSetTheNewDefaultQueryIfTheCreatedQueryIsNewDefaultQuery(): void
    {
        $query = CrossTrackerQueryUnitTestBuilder::aQuery()->isDefault()->build();

        $this->getQueryUpdater()->updateQuery($query);

        self::assertSame(1, $this->updater_query_dao->getCallCount());
        self::assertSame(1, $this->reset_is_default_column_query_dao->getCallCount());
    }

    public function testUpdateQueryOfANonDefaultQuery(): void
    {
        $query = CrossTrackerQueryUnitTestBuilder::aQuery()->build();

        $this->getQueryUpdater()->updateQuery($query);

        self::assertSame(1, $this->updater_query_dao->getCallCount());
        self::assertSame(0, $this->reset_is_default_column_query_dao->getCallCount());
    }
}
