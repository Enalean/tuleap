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

namespace Tuleap\Project\REST\v1;

use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\ServiceBuilder;
use Tuleap\Test\PHPUnit\TestCase;

final class ServiceRepresentationTest extends TestCase
{
    /**
     * @throws \ServiceNotAllowedForProjectException
     */
    public function testItBuildsTheRepresentationOfCustomService(): void
    {
        $service_url    = "https://example.com";
        $service        = ServiceBuilder::aProjectDefinedService(ProjectTestBuilder::aProject()->build())->withUrl(
            $service_url
        )->build();
        $representation = ServiceRepresentation::build(
            $service
        );
        self::assertSame(102, $representation->id);
        self::assertSame(ServiceRepresentation::ROUTE . "/102", $representation->uri);
        self::assertSame($service_url, $representation->url);
        self::assertTrue($representation->is_custom);
    }

    /**
     * @throws \ServiceNotAllowedForProjectException
     */
    public function testItBuildsTheRepresentationOfSystemService(): void
    {
        $service_url    = "https://example.com";
        $service        = ServiceBuilder::aSystemService(ProjectTestBuilder::aProject()->build())->withUrl(
            $service_url
        )->build();
        $representation = ServiceRepresentation::build(
            $service
        );
        self::assertSame(102, $representation->id);
        self::assertSame(ServiceRepresentation::ROUTE . "/102", $representation->uri);
        self::assertSame($service_url, $representation->url);
        self::assertFalse($representation->is_custom);
    }
}
