<?php
/**
 * Copyright (c) Enalean 2021 -  Present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\AgileDashboard\Planning\Configuration;

use Tuleap\AgileDashboard\Stub\RetrievePlanningStub;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;

final class ScrumConfigurationTest extends TestCase
{
    public function testItBuildsCorrectConfigurationWhenNonLastLevelPlanningAreFilled(): void
    {
        $scrum_retriever = RetrievePlanningStub::stubNonLastLevelPlanning();
        $configuration   = ScrumConfiguration::fromProjectId($scrum_retriever, 101, UserTestBuilder::aUser()->build());

        self::assertTrue($configuration->isNotEmpty());
    }

    public function testItBuildsCorrectConfigurationWhenLastLevelPlanningAreFilled(): void
    {
        $scrum_retriever = RetrievePlanningStub::stubLastLevelPlanning();
        $configuration   = ScrumConfiguration::fromProjectId($scrum_retriever, 101, UserTestBuilder::aUser()->build());

        self::assertTrue($configuration->isNotEmpty());
    }

    public function testItBuildCorrectConfigurationWhenAllLevelPlanningAreFilled(): void
    {
        $scrum_retriever = RetrievePlanningStub::stubAllPlannings();
        $configuration   = ScrumConfiguration::fromProjectId($scrum_retriever, 101, UserTestBuilder::aUser()->build());

        self::assertTrue($configuration->isNotEmpty());
    }

    public function testItBuildsEmptyConfiguration(): void
    {
        $scrum_retriever = RetrievePlanningStub::stubNoPlannings();
        $configuration   = ScrumConfiguration::fromProjectId($scrum_retriever, 101, UserTestBuilder::aUser()->build());

        self::assertFalse($configuration->isNotEmpty());
    }
}
