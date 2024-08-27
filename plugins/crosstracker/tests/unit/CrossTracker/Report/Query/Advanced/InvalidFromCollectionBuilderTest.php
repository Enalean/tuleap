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

use Tuleap\Dashboard\Project\IRetrieveProjectFromWidget;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Test\Stubs\Dashboard\Project\IRetrieveProjectFromWidgetStub;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\From;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\FromProject;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\FromProjectEqual;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\FromProjectIn;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\FromTracker;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\FromTrackerEqual;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\FromTrackerIn;

final class InvalidFromCollectionBuilderTest extends TestCase
{
    private IRetrieveProjectFromWidget $project_from_widget;

    protected function setUp(): void
    {
        $this->project_from_widget = IRetrieveProjectFromWidgetStub::buildWithoutProjectId();
    }

    /**
     * @return list<string>
     */
    private function getInvalidFrom(
        From $from,
    ): array {
        $in_project_checker = new WidgetInProjectChecker($this->project_from_widget);
        $builder            = new InvalidFromCollectionBuilder(
            new InvalidFromTrackerCollectorVisitor($in_project_checker),
            new InvalidFromProjectCollectorVisitor($in_project_checker),
            2,
        );

        return $builder->buildCollectionOfInvalidFrom($from)->getInvalidFrom();
    }

    public function testItRefusesUnknownFromProject(): void
    {
        $result = $this->getInvalidFrom(new From(new FromProject('blabla', new FromProjectEqual('')), null));
        self::assertNotEmpty($result);
        self::assertStringContainsStringIgnoringCase("You cannot search on 'blabla'", $result[0]);
    }

    public function testItRefusesUnknownFromTracker(): void
    {
        $result = $this->getInvalidFrom(new From(new FromTracker('blabla', new FromTrackerEqual('')), null));
        self::assertNotEmpty($result);
        self::assertStringContainsStringIgnoringCase("You cannot search on 'blabla'", $result[0]);
    }

    public function testItRefusesTwoFromProject(): void
    {
        $result = $this->getInvalidFrom(new From(
            new FromProject('@project', new FromProjectEqual('self')),
            new FromProject('@project', new FromProjectEqual('self')),
        ));
        self::assertNotEmpty($result);
        self::assertStringContainsStringIgnoringCase('The both conditions of \'FROM\' must be on "tracker" and "project"', $result[0]);
    }

    public function testItRefusesTwoFromTracker(): void
    {
        $result = $this->getInvalidFrom(new From(
            new FromTracker('@tracker.name', new FromTrackerEqual('release')),
            new FromTracker('@tracker.name', new FromTrackerEqual('release')),
        ));
        self::assertNotEmpty($result);
        self::assertStringContainsStringIgnoringCase('The both conditions of \'FROM\' must be on "tracker" and "project"', $result[0]);
    }

    public function testItReturnsEmptyForProjectAndTrackerAsNothingHasBeenImplemented(): void
    {
        $this->project_from_widget = IRetrieveProjectFromWidgetStub::buildWithProjectId(1);
        $result                    = $this->getInvalidFrom(new From(
            new FromProject('@project', new FromProjectEqual('self')),
            new FromTracker('@tracker.name', new FromTrackerEqual('release')),
        ));
        self::assertEmpty($result);
    }

    public function testItReturnsErrorWhenUsingProjectIn(): void
    {
        $result = $this->getInvalidFrom(new From(new FromProject('@project', new FromProjectIn([])), null));
        self::assertCount(1, $result);
        self::assertEquals("You cannot use '@project IN(...)'", $result[0]);
    }

    public function testItReturnsErrorWhenProjectSelfOutsideProject(): void
    {
        $result = $this->getInvalidFrom(new From(new FromProject('@project', new FromProjectEqual('self')), null));
        self::assertCount(1, $result);
        self::assertEquals("You cannot use @project = 'self' in the context of a personal dashboard", $result[0]);
    }

    public function testItReturnsEmptyWhenProjectSelfInsideProject(): void
    {
        $this->project_from_widget = IRetrieveProjectFromWidgetStub::buildWithProjectId(1);
        $result                    = $this->getInvalidFrom(new From(new FromProject('@project', new FromProjectEqual('self')), null));
        self::assertEmpty($result);
    }

    public function testItReturnsErrorForProjectAggregatedAsNothingHasBeenImplemented(): void
    {
        $result = $this->getInvalidFrom(new From(new FromProject('@project', new FromProjectEqual('aggregated')), null));
        self::assertCount(1, $result);
        self::assertEquals("Only @project = 'self' is supported", $result[0]);
    }

    public function testItReturnsErrorWhenTrackerNameOutsideProjectWithoutProjectCondition(): void
    {
        $result = $this->getInvalidFrom(new From(new FromTracker('@tracker.name', new FromTrackerEqual('release')), null));
        self::assertCount(1, $result);
        self::assertEquals('In the context of a personal dashboard, you must provide a @project condition in the FROM part of your query', $result[0]);

        $result = $this->getInvalidFrom(new From(new FromTracker('@tracker.name', new FromTrackerIn(['release'])), null));
        self::assertCount(1, $result);
        self::assertEquals('In the context of a personal dashboard, you must provide a @project condition in the FROM part of your query', $result[0]);
    }

    public function testItReturnsEmptyWhenTrackerNameWithProjectCondition(): void
    {
        self::assertEmpty($this->getInvalidFrom(new From(
            new FromTracker('@tracker.name', new FromTrackerEqual('release')),
            new FromProject('@project.category', new FromProjectEqual('some')),
        )));
        self::assertEmpty($this->getInvalidFrom(new From(
            new FromTracker('@tracker.name', new FromTrackerIn(['release'])),
            new FromProject('@project.category', new FromProjectEqual('some')),
        )));
    }

    public function testItReturnsErrorWhenTrackerNameIsEmpty(): void
    {
        $this->project_from_widget = IRetrieveProjectFromWidgetStub::buildWithProjectId(1);
        $result                    = $this->getInvalidFrom(new From(new FromTracker('@tracker.name', new FromTrackerEqual('')), null));
        self::assertCount(1, $result);
        self::assertEquals('@tracker.name cannot be empty', $result[0]);

        $result = $this->getInvalidFrom(new From(new FromTracker('@tracker.name', new FromTrackerIn(['release', '', 'sprint'])), null));
        self::assertCount(1, $result);
        self::assertEquals('@tracker.name cannot be empty', $result[0]);
    }
}
