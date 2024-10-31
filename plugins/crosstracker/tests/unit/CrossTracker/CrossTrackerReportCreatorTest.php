<?php
/**
 * Copyright (c) Enalean, 2024-present. All Rights Reserved.
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

namespace Tuleap\CrossTracker;

use Tuleap\CrossTracker\Tests\Stub\Report\CreateReportStub;
use Tuleap\Dashboard\Project\ProjectDashboardController;
use Tuleap\Dashboard\User\UserDashboardController;
use Tuleap\NeverThrow\Fault;
use Tuleap\NeverThrow\Result;
use Tuleap\Test\PHPUnit\TestCase;

final class CrossTrackerReportCreatorTest extends TestCase
{
    private CrossTrackerReportCreator $creator;

    protected function setUp(): void
    {
        $this->creator = new CrossTrackerReportCreator(CreateReportStub::withReport(15));
    }

    public static function dataProviderDashboardType(): array
    {
        return [
            'With user dashboard type'           => [UserDashboardController::DASHBOARD_TYPE],
            'With user legacy dashboard type'    => [UserDashboardController::LEGACY_DASHBOARD_TYPE],
            'With project dashboard type'        => [ProjectDashboardController::DASHBOARD_TYPE],
            'With project legacy dashboard type' => [ProjectDashboardController::LEGACY_DASHBOARD_TYPE],

        ];
    }

    /**
     * @dataProvider dataProviderDashboardType
     */
    public function testItReturnsTheReportId(string $dashboard_type): void
    {
        $result = $this->creator->createReportAndReturnLastId($dashboard_type);
        self::assertTrue(Result::isOk($result));
        self::assertSame(15, $result->value);
    }

    public function testItReturnsAnErrorWhenTheDashboardTypeIsInvalid(): void
    {
        $result = $this->creator->createReportAndReturnLastId('invalid-dashboard-type');
        self::assertTrue(Result::isErr($result));
        self::assertInstanceOf(Fault::class, $result->error);
    }
}
