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

namespace Tuleap\CrossTracker\Widget;

use Codendi_Request;
use PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles;
use Tuleap\CrossTracker\Query\QueryCreator;
use Tuleap\CrossTracker\Tests\Stub\Query\InsertNewQueryStub;
use Tuleap\CrossTracker\Tests\Stub\Query\ResetIsDefaultColumnStub;
use Tuleap\CrossTracker\Tests\Stub\Widget\CreateWidgetStub;
use Tuleap\NeverThrow\Fault;
use Tuleap\NeverThrow\Result;
use Tuleap\Test\DB\DBTransactionExecutorPassthrough;
use Tuleap\Test\DB\UUIDTestContext;
use Tuleap\Test\PHPUnit\TestCase;

#[DisableReturnValueGenerationForTestDoubles]
final class CrossTrackerWidgetCreatorTest extends TestCase
{
    private CrossTrackerWidgetCreator $cross_tracker_widget_creator;
    private InsertNewQueryStub $insert_query_dao;

    private const WIDGET_ID = 15;

    #[\Override]
    protected function setUp(): void
    {
        $executor               = new DBTransactionExecutorPassthrough();
        $this->insert_query_dao = InsertNewQueryStub::withUUID(new UUIDTestContext());

        $this->cross_tracker_widget_creator = new CrossTrackerWidgetCreator(
            CreateWidgetStub::withWidget(self::WIDGET_ID),
            new QueryCreator(
                $executor,
                $this->insert_query_dao,
                ResetIsDefaultColumnStub::build()
            ),
            $executor
        );
    }

    public function testItCreatesTheWidgetFromTheUI(): void
    {
        $request = new Codendi_Request([]);
        $result  = $this->cross_tracker_widget_creator->createWithQueries($request);

        self::assertTrue(Result::isOk($result));
        self::assertSame(15, $result->value);
        self::assertSame(0, $this->insert_query_dao->getCallCount());
    }

    public function testItCreatesFromXMLTemplate(): void
    {
        $request = new Codendi_Request(
            [
                'queries' => [
                    [
                        'title'       => 'First query',
                        'description' => '',
                        'is_default'  => false,
                        'tql'         => 'SELECT @tracker.name FROM @project IN("aggregated", "self") WHERE @id >= 1',
                    ],
                    [
                        'title'       => 'Second query',
                        'description' => 'Second description',
                        'is_default'  => true,
                        'tql'         => 'SELECT @pretty_title FROM @project IN("self") WHERE @id >= 1',
                    ],
                ],
            ]
        );

        $result = $this->cross_tracker_widget_creator->createWithQueries($request);

        self::assertTrue(Result::isOk($result));
        self::assertSame(15, $result->value);
        self::assertSame(2, $this->insert_query_dao->getCallCount());
    }

    public function testItReturnsFaultWhenOneOfTheQueryFromTheRequestIsMalformed(): void
    {
        $request = new Codendi_Request(
            [
                'queries' => [
                    [
                        'title'       => 'First query',
                        'description' => '',
                        'is_default'  => false,
                    ],
                    [
                        'title'       => 'Second query',
                        'description' => 'Second description',
                        'is_default'  => true,
                        'tql'         => 'SELECT @pretty_title FROM @project IN("self") WHERE @id >= 1',
                    ],
                ],
            ]
        );

        $result = $this->cross_tracker_widget_creator->createWithQueries($request);

        self::assertTrue(Result::isErr($result));
        self::assertInstanceOf(Fault::class, $result->error);
        self::assertSame(0, $this->insert_query_dao->getCallCount());
    }
}
