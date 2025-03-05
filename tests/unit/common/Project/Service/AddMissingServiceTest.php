<?php
/*
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\Project\Service;

use Service;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class AddMissingServiceTest extends TestCase
{
    public function testItDoesntAddAServiceThatIsAlreadyThereBecauseOfTheDatabase(): void
    {
        $project = ProjectTestBuilder::aProject()->withId(120)->build();

        $service = new Service(
            $project,
            [
                'short_name' => 'plugin_foo',
            ]
        );

        $add_missing_service = new AddMissingService($project, [$service]);

        $add_missing_service->addService($service);

        self::assertEquals([$service], $add_missing_service->getAllowedServices());
    }

    public function testAllowedServicesAreReturnedSortedByRank(): void
    {
        $project = ProjectTestBuilder::aProject()->withId(120)->build();

        $service = new Service(
            $project,
            [
                'short_name' => 'plugin_foo',
                'rank' => 50,
            ]
        );

        $add_missing_service = new AddMissingService(
            $project,
            [
                new Service(
                    $project,
                    [
                        'short_name' => 'plugin_foo',
                        'rank' => 50,
                    ]
                ),
                new Service(
                    $project,
                    [
                        'short_name' => 'plugin_bar',
                        'rank' => 100,
                    ]
                ),
            ]
        );

        $add_missing_service->addService(
            new Service(
                $project,
                [
                    'short_name' => 'plugin_baz',
                    'rank' => 75,
                ]
            )
        );

        self::assertEquals(
            ['plugin_foo', 'plugin_baz', 'plugin_bar'],
            array_map(
                fn (\Service $service) => $service->getShortName(),
                $add_missing_service->getAllowedServices()
            )
        );
    }
}
