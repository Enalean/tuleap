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

use Mockery;
use PFUser;
use PHPUnit\Framework\TestCase;
use Project;
use Service;
use ServiceManager;

class ServiceRepresentationCollectionBuilderTest extends TestCase
{
    use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    /**
     * @var Mockery\MockInterface|ServiceManager
     */
    private $service_manager;
    /**
     * @var Mockery\MockInterface|Project
     */
    private $project;
    /**
     * @var Mockery\MockInterface|PFUser
     */
    private $siteadmin;
    /**
     * @var Mockery\MockInterface|PFUser
     */
    private $projectadmin;
    /**
     * @var ServiceRepresentationCollectionBuilder
     */
    private $builder;

    protected function setUp(): void
    {
        $this->service_manager = Mockery::mock(ServiceManager::class);
        $this->project         = Mockery::mock(Project::class);
        $this->siteadmin       = Mockery::mock(PFUser::class);
        $this->projectadmin    = Mockery::mock(PFUser::class);

        $this->projectadmin->shouldReceive('isSuperUser')->andReturn(false);
        $this->siteadmin->shouldReceive('isSuperUser')->andReturn(true);

        $this->builder = new ServiceRepresentationCollectionBuilder($this->service_manager);

        parent::setUp();
    }

    public function testItDoesNotReturnNone(): void
    {
        $service = Mockery::mock(Service::class);
        $service->shouldReceive('getId')->andReturn(100);

        $this->service_manager->shouldReceive('getListOfAllowedServicesForProject')->andReturn([$service]);

        $collection = $this->builder->getServiceRepresentationCollectionForProject($this->project, $this->siteadmin);
        $this->assertEmpty($collection);

        $collection = $this->builder->getServiceRepresentationCollectionForProject($this->project, $this->projectadmin);
        $this->assertEmpty($collection);
    }

    public function testItReturnsInactiveServiceOnlyForSiteadmin(): void
    {
        $service = Mockery::mock(Service::class);
        $service->shouldReceive('getId')->andReturn(101);
        $service->shouldReceive('isActive')->andReturn(false);
        $service->shouldReceive('isUsed')->andReturn('true');
        $service->shouldReceive('getShortName')->andReturn('plugin_git');
        $service->shouldReceive('getInternationalizedName')->andReturn('Git');
        $service->shouldReceive('getIconName')->andReturn('fa-tlp-versioning-git');

        $this->service_manager->shouldReceive('getListOfAllowedServicesForProject')->andReturn([$service]);

        $collection = $this->builder->getServiceRepresentationCollectionForProject($this->project, $this->siteadmin);
        $this->assertNotEmpty($collection);

        $collection = $this->builder->getServiceRepresentationCollectionForProject($this->project, $this->projectadmin);
        $this->assertEmpty($collection);
    }

    public function testItReturnsActiveServiceForEveryone(): void
    {
        $service = Mockery::mock(Service::class);
        $service->shouldReceive('getId')->andReturn(101);
        $service->shouldReceive('isActive')->andReturn(true);
        $service->shouldReceive('isUsed')->andReturn('true');
        $service->shouldReceive('getShortName')->andReturn('plugin_git');
        $service->shouldReceive('getInternationalizedName')->andReturn('Git');
        $service->shouldReceive('getIconName')->andReturn('fa-tlp-versioning-git');

        $this->service_manager->shouldReceive('getListOfAllowedServicesForProject')->andReturn([$service]);

        $collection = $this->builder->getServiceRepresentationCollectionForProject($this->project, $this->siteadmin);
        $this->assertNotEmpty($collection);

        $collection = $this->builder->getServiceRepresentationCollectionForProject($this->project, $this->projectadmin);
        $this->assertNotEmpty($collection);
    }
}
