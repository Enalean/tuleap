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

namespace Tuleap\Project\REST\v1\Service;

use Luracast\Restler\RestException;
use PFUser;
use Tuleap\Project\Service\ServiceCannotBeUpdatedException;
use Tuleap\REST\I18NRestException;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\ServiceBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Test\Stubs\REST\v1\Service\ServiceCanBeUpdatedStub;

final class ServiceUpdateCheckerTest extends TestCase
{
    private PFUser $user;

    protected function setUp(): void
    {
        $this->user = UserTestBuilder::buildWithId(101);
    }

    private function buildServiceUpdateChecker(ServiceCanBeUpdatedStub $service_manager): ServiceUpdateChecker
    {
        return new ServiceUpdateChecker($service_manager);
    }

    public function testItThrowsAnExceptionIfTheServiceCannotBeUpdated(): void
    {
        $service_manager = ServiceCanBeUpdatedStub::serviceCannotBeUpdated(new ServiceCannotBeUpdatedException("Service cannot be updated"));

        $service = ServiceBuilder::aSystemService(ProjectTestBuilder::aProject()->build())->withShortName("plugin_git")->isActive(true)->build();
        $body    = new ServicePUTRepresentation(true);

        self::expectException(RestException::class);
        $this->buildServiceUpdateChecker($service_manager)->checkServiceCanBeUpdated($body, $service, $this->user);
    }

    public function testItThrowsAnExceptionIfTheUserEnableAInactiveService(): void
    {
        $service_manager = ServiceCanBeUpdatedStub::serviceCanBeUpdated();

        $body    = new ServicePUTRepresentation(true);
        $service = ServiceBuilder::aSystemService(ProjectTestBuilder::aProject()->build())->withShortName("plugin_git")->isActive(false)->build();

        self::expectException(I18NRestException::class);
        $this->buildServiceUpdateChecker($service_manager)->checkServiceCanBeUpdated($body, $service, $this->user);
    }

    public function testTheCheckOfThePayloadIsOk(): void
    {
        $service_manager = ServiceCanBeUpdatedStub::serviceCanBeUpdated();

        $body    = new ServicePUTRepresentation(true);
        $service = ServiceBuilder::aSystemService(ProjectTestBuilder::aProject()->build())->withShortName("plugin_git")->isActive(true)->build();

        $this->buildServiceUpdateChecker($service_manager)->checkServiceCanBeUpdated($body, $service, $this->user);
        self::expectNotToPerformAssertions();
    }
}
