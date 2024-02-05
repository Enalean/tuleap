<?php
/**
 * Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
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

namespace Tuleap\Project\REST\v1;

use PFUser;
use PHPUnit\Framework\MockObject\MockObject;
use Project;
use ServiceManager;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\ServiceBuilder;
use Tuleap\Test\Builders\UserTestBuilder;

class ServiceRepresentationCollectionBuilderTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private ServiceManager&MockObject $service_manager;
    private Project $project;
    private PFUser $siteadmin;
    private PFUser $projectadmin;
    private ServiceRepresentationCollectionBuilder $builder;

    protected function setUp(): void
    {
        $this->service_manager = $this->createMock(ServiceManager::class);
        $this->project         = ProjectTestBuilder::aProject()->build();
        $this->siteadmin       = UserTestBuilder::buildSiteAdministrator();
        $this->projectadmin    = UserTestBuilder::aUser()
            ->withAdministratorOf($this->project)
            ->withoutSiteAdministrator()
            ->build();

        $this->builder = new ServiceRepresentationCollectionBuilder($this->service_manager);

        parent::setUp();
    }

    public function testItDoesNotReturnNone(): void
    {
        $service = ServiceBuilder::aSystemService(
            ProjectTestBuilder::aProject()->build()
        )
                      ->withId(100)->build();

        $this->service_manager->method('getListOfAllowedServicesForProject')->willReturn([$service]);

        $collection = $this->builder->getServiceRepresentationCollectionForProject($this->project, $this->siteadmin);
        self::assertEmpty($collection);

        $collection = $this->builder->getServiceRepresentationCollectionForProject($this->project, $this->projectadmin);
        self::assertEmpty($collection);
    }

    public function testItReturnsInactiveServiceOnlyForSiteadmin(): void
    {
        $service = ServiceBuilder::aSystemService(
            ProjectTestBuilder::aProject()->build()
        )
                                 ->withId(101)->isActive(false)->isUsed(true)->withShortName('plugin_git')->withServiceIcon('fa-tlp-versioning-git')->build();

        $this->service_manager->method('getListOfAllowedServicesForProject')->willReturn([$service]);

        $collection = $this->builder->getServiceRepresentationCollectionForProject($this->project, $this->siteadmin);
        self::assertNotEmpty($collection);

        $collection = $this->builder->getServiceRepresentationCollectionForProject($this->project, $this->projectadmin);
        self::assertEmpty($collection);
    }

    public function testItReturnsActiveServiceForEveryone(): void
    {
        $service = ServiceBuilder::aSystemService(
            ProjectTestBuilder::aProject()->build()
        )
                                 ->withId(101)->isActive(true)->isUsed(true)->withShortName('plugin_git')->withServiceIcon('fa-tlp-versioning-git')->build();


        $this->service_manager->method('getListOfAllowedServicesForProject')->willReturn([$service]);

        $collection = $this->builder->getServiceRepresentationCollectionForProject($this->project, $this->siteadmin);
        self::assertNotEmpty($collection);

        $collection = $this->builder->getServiceRepresentationCollectionForProject($this->project, $this->projectadmin);
        self::assertNotEmpty($collection);
    }
}
