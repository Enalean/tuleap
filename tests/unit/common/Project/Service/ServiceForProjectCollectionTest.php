<?php
/**
 * Copyright (c) Enalean, 2023 - Present. All Rights Reserved.
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

namespace Tuleap\Project\Service;

use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Test\Stubs\Project\Service\ListOfAllowedServicesForProjectRetrieverStub;

final class ServiceForProjectCollectionTest extends TestCase
{
    public function testGetMinimalRankReturnsSummaryRank(): void
    {
        $project = ProjectTestBuilder::aProject()->build();

        $summary = $this->getService($project, \Service::SUMMARY, 112, true);

        $collection = new ServiceForProjectCollection(
            $project,
            ListOfAllowedServicesForProjectRetrieverStub::withServices($summary),
        );

        self::assertSame(112, $collection->getMinimalRank());
    }

    public function testGetMinimalRankReturns1WhenSummaryDoesNotExists(): void
    {
        $project = ProjectTestBuilder::aProject()->build();

        $collection = new ServiceForProjectCollection(
            $project,
            ListOfAllowedServicesForProjectRetrieverStub::withoutServices(),
        );

        self::assertSame(1, $collection->getMinimalRank());
    }

    public function testGetServicesReturnAllServices(): void
    {
        $project = ProjectTestBuilder::aProject()->build();

        $summary = $this->getService($project, \Service::SUMMARY, 112, true);
        $file    = $this->getService($project, \Service::FILE, 112, false);

        $collection = new ServiceForProjectCollection(
            $project,
            ListOfAllowedServicesForProjectRetrieverStub::withServices($summary, $file),
        );

        self::assertSame(
            [
                \Service::SUMMARY => $summary,
                \Service::FILE => $file,
            ],
            $collection->getServices(),
        );
    }

    public function testGetServiceReturnServiceIfItIsUsed(): void
    {
        $project = ProjectTestBuilder::aProject()->build();

        $summary = $this->getService($project, \Service::SUMMARY, 112, true);
        $file    = $this->getService($project, \Service::FILE, 112, false);

        $collection = new ServiceForProjectCollection(
            $project,
            ListOfAllowedServicesForProjectRetrieverStub::withServices($summary, $file),
        );

        self::assertSame(
            $summary,
            $collection->getService(\Service::SUMMARY),
        );

        self::assertNull(
            $collection->getService(\Service::FILE),
        );

        self::assertNull(
            $collection->getService(\Service::WIKI),
        );
    }

    public function testUsesService(): void
    {
        $project = ProjectTestBuilder::aProject()->build();

        $summary = $this->getService($project, \Service::SUMMARY, 112, true);
        $file    = $this->getService($project, \Service::FILE, 112, false);

        $collection = new ServiceForProjectCollection(
            $project,
            ListOfAllowedServicesForProjectRetrieverStub::withServices($summary, $file),
        );

        self::assertTrue(
            $collection->usesService(\Service::SUMMARY),
        );

        self::assertFalse(
            $collection->usesService(\Service::FILE),
        );

        self::assertFalse(
            $collection->usesService(\Service::WIKI),
        );
    }

    private function getService(\Project $project, string $shortname, int $rank, bool $is_used): \Service
    {
        return new \Service($project, [
            'short_name'  => $shortname,
            'rank'        => $rank,
            'is_used'     => (int) $is_used,
            'label'       => $shortname,
            'description' => $shortname,
        ]);
    }
}
