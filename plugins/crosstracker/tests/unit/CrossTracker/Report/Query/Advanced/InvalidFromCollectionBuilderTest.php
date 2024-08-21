<?php
/**
 * Copyright (c) Enalean, 2024-Present. All Rights Reserved.
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

namespace Tuleap\CrossTracker\Report\Query\Advanced;

use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\From;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\FromProject;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\FromProjectEqual;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\FromTracker;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\FromTrackerEqual;

final class InvalidFromCollectionBuilderTest extends TestCase
{
    private InvalidFromCollectionBuilder $builder;

    protected function setUp(): void
    {
        $this->builder = new InvalidFromCollectionBuilder(
            new InvalidFromTrackerCollectorVisitor(),
            new InvalidFromProjectCollectorVisitor(),
        );
    }

    public function testItRefusesUnknownFromProject(): void
    {
        $result = $this->builder
            ->buildCollectionOfInvalidFrom(new From(new FromProject('blabla', new FromProjectEqual('')), null))
            ->getInvalidFrom();
        self::assertNotEmpty($result);
        self::assertStringContainsStringIgnoringCase("You cannot search on 'blabla'", $result[0]);
    }

    public function testItRefusesUnknownFromTracker(): void
    {
        $result = $this->builder
            ->buildCollectionOfInvalidFrom(new From(new FromTracker('blabla', new FromTrackerEqual('')), null))
            ->getInvalidFrom();
        self::assertNotEmpty($result);
        self::assertStringContainsStringIgnoringCase("You cannot search on 'blabla'", $result[0]);
    }

    public function testItRefusesTwoFromProject(): void
    {
        $result = $this->builder
            ->buildCollectionOfInvalidFrom(new From(
                new FromProject('@project', new FromProjectEqual('self')),
                new FromProject('@project', new FromProjectEqual('self')),
            ))
            ->getInvalidFrom();
        self::assertNotEmpty($result);
        self::assertStringContainsStringIgnoringCase('The both conditions of \'FROM\' must be on "tracker" and "project"', $result[0]);
    }

    public function testItRefusesTwoFromTracker(): void
    {
        $result = $this->builder
            ->buildCollectionOfInvalidFrom(new From(
                new FromTracker('@tracker.name', new FromTrackerEqual('release')),
                new FromTracker('@tracker.name', new FromTrackerEqual('release')),
            ))
            ->getInvalidFrom();
        self::assertNotEmpty($result);
        self::assertStringContainsStringIgnoringCase('The both conditions of \'FROM\' must be on "tracker" and "project"', $result[0]);
    }

    public function testItReturnsEmptyForProjectAndTrackerAsNothingHasBeenImplemented(): void
    {
        $result = $this->builder->buildCollectionOfInvalidFrom(new From(
            new FromProject('@project', new FromProjectEqual('')),
            new FromTracker('@tracker.name', new FromTrackerEqual('')),
        ));
        self::assertEmpty($result->getInvalidFrom());
    }

    public function testItReturnsEmptyForProjectHasNothingAsBeenImplemented(): void
    {
        $result = $this->builder->buildCollectionOfInvalidFrom(new From(new FromProject('@project', new FromProjectEqual('')), null));
        self::assertEmpty($result->getInvalidFrom());
    }

    public function testItReturnsEmptyForTrackerAsNothingHasBeenImplemented(): void
    {
        $result = $this->builder->buildCollectionOfInvalidFrom(new From(new FromTracker('@tracker.name', new FromTrackerEqual('')), null));
        self::assertEmpty($result->getInvalidFrom());
    }
}
