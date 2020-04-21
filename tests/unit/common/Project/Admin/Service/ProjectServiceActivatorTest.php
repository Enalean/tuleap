<?php
/**
 * Copyright (c) Enalean, 2019 - present. All Rights Reserved.
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

namespace Tuleap\common\Project\Admin\Service;

use EventManager;
use Mockery;
use PHPUnit\Framework\TestCase;
use ServiceDao;
use Tuleap\Project\Admin\Service\ProjectServiceActivator;
use Tuleap\Project\Service\ServiceLinkDataBuilder;
use Tuleap\Service\ServiceCreator;

final class ProjectServiceActivatorTest extends TestCase
{
    use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|ServiceLinkDataBuilder
     */
    private $link_builder;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|\ServiceManager
     */
    private $service_manager;
    /**
     * @var ProjectServiceActivator
     */
    private $service_activator;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|ServiceDao
     */
    private $service_dao;
    /**
     * @var EventManager|\Mockery\LegacyMockInterface|\Mockery\MockInterface
     */
    private $event_manager;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|ServiceCreator
     */
    private $service_creator;

    protected function setUp(): void
    {
        $this->service_creator = Mockery::mock(ServiceCreator::class);
        $this->event_manager   = Mockery::mock(EventManager::class);
        $this->service_dao     = Mockery::mock(ServiceDao::class);
        $this->service_manager = Mockery::mock(\ServiceManager::class);
        $this->link_builder          = Mockery::mock(ServiceLinkDataBuilder::class);

        $this->service_activator = new ProjectServiceActivator(
            $this->service_creator,
            $this->event_manager,
            $this->service_dao,
            $this->service_manager,
            $this->link_builder
        );
    }

    public function testServiceUsageIsInheritedFromData(): void
    {
        $project = Mockery::mock(\Project::class);
        $project->shouldReceive('getID')->andReturn(101);

        $template = Mockery::mock(\Project::class);
        $template->shouldReceive('getID')->andReturn(201);
        $template->shouldReceive('isSystem')->twice()->andReturn(false);
        $template->shouldReceive('getUnixName')->once()->andReturn('test-name');

        $data = Mockery::mock(\ProjectCreationData::class);
        $data->shouldReceive('getServiceInfo')->andReturn(['is_used' => true]);
        $data->shouldReceive('isIsBuiltFromXml')->andReturnFalse();

        $template_service = ['service_id' => 10, 'short_name' => 'document', 'is_used' => 0];
        $this->service_dao->shouldReceive('getServiceInfoQueryForNewProject')->andReturn(
            [
                $template_service
            ]
        );

        $this->service_creator->shouldReceive('createService')->withArgs(
            [
                $template_service,
                101,
                [
                    'system'  => false,
                    'name'    => 'test-name',
                    'id'      => 201,
                    'is_used' => 1,
                ]
            ]
        )->once();

        $this->event_manager->shouldReceive('processEvent')->once();

        $this->service_activator->activateServicesFromTemplate($project, $template, $data, []);
    }

    public function testServiceUsageIsInheritedFromTemplate(): void
    {
        $project = Mockery::mock(\Project::class);
        $project->shouldReceive('getID')->andReturn(101);

        $template = Mockery::mock(\Project::class);
        $template->shouldReceive('getID')->andReturn(201);
        $template->shouldReceive('isSystem')->andReturn(false);
        $template->shouldReceive('getUnixName')->andReturn('test-name');

        $data = Mockery::mock(\ProjectCreationData::class);
        $data->shouldReceive('getServiceInfo')->andReturn([]);
        $data->shouldReceive('isIsBuiltFromXml')->andReturnFalse();

        $template_service       = ['service_id' => 10, 'short_name' => 'document', 'is_used' => 1];
        $other_template_service = ['service_id' => 10, 'short_name' => 'document', 'is_used' => 1];
        $this->service_dao->shouldReceive('getServiceInfoQueryForNewProject')->andReturn(
            [
                $template_service,
                $other_template_service
            ]
        );

        $this->service_creator->shouldReceive('createService')->withArgs(
            [
                $template_service,
                101,
                [
                    'system'  => false,
                    'name'    => 'test-name',
                    'id'      => 201,
                    'is_used' => 1,
                ]
            ]
        )->once();
        $this->service_creator->shouldReceive('createService')->withArgs(
            [
                $other_template_service,
                101,
                [
                    'system'  => false,
                    'name'    => 'test-name',
                    'id'      => 201,
                    'is_used' => 1,
                ]
            ]
        )->once();

        $this->event_manager->shouldReceive('processEvent')->once();

        $this->service_activator->activateServicesFromTemplate($project, $template, $data, []);
    }

    public function testAdminServiceIsAlwaysActive(): void
    {
        $project = Mockery::mock(\Project::class);
        $project->shouldReceive('getID')->andReturn(101);

        $template = \Mockery::mock(\Project::class);
        $template->shouldReceive('getID')->andReturn(201);
        $template->shouldReceive('isSystem')->twice()->andReturn(false);
        $template->shouldReceive('getUnixName')->once()->andReturn('test-name');

        $data = \Mockery::mock(\ProjectCreationData::class);
        $data->shouldReceive('getServiceInfo')->andReturn([]);
        $data->shouldReceive('isIsBuiltFromXml')->andReturnFalse();

        $template_service = ['service_id' => 10, 'short_name' => 'admin', 'is_used' => 0];
        $this->service_dao->shouldReceive('getServiceInfoQueryForNewProject')->andReturn(
            [
                $template_service
            ]
        );

        $this->service_creator->shouldReceive('createService')->withArgs(
            [
                $template_service,
                101,
                [
                    'system'  => false,
                    'name'    => 'test-name',
                    'id'      => 201,
                    'is_used' => 1,
                ]
            ]
        )->once();

        $this->event_manager->shouldReceive('processEvent')->once();

        $this->service_activator->activateServicesFromTemplate($project, $template, $data, []);
    }

    public function testTV3ServiceUsageIsInheritedFromXml(): void
    {
        $project = Mockery::mock(\Project::class);
        $project->shouldReceive('getID')->andReturn(101);

        $template = \Mockery::mock(\Project::class);
        $template->shouldReceive('getID')->andReturn(201);

        $data = \Mockery::mock(\ProjectCreationData::class);
        $data->shouldReceive('getServiceInfo')->andReturn(['is_used' => true]);
        $data->shouldReceive('isIsBuiltFromXml')->andReturnTrue();
        $data->shouldReceive('getDataServices')->andReturn([10 => ['is_used' => true]]);

        $service = Mockery::mock(\Service::class);
        $service->shouldReceive('getShortName')->andReturn('tracker');
        $service->shouldReceive('getIcon')->andReturn('');
        $service->shouldReceive('getLabel')->andReturn('tracker');
        $service->shouldReceive('getDescription')->andReturn('description');
        $service->shouldReceive('getUrl')->andReturn('/tracker/group_id=$group_id');
        $service->shouldReceive('getScope')->andReturn('P');
        $service->shouldReceive('getRank')->andReturn('1');
        $service->shouldReceive('isOpenedInNewTab')->andReturn(false);
        $this->service_manager->shouldReceive('getService')->andReturn($service);

        $this->link_builder->shouldReceive('substituteVariablesInLink')->andReturn('/tracker/group_id=101');

        $template_service = ['service_id' => 10, 'short_name' => 'tracker', 'is_used' => 0];
        $this->service_dao->shouldReceive('getServiceInfoQueryForNewProject')->andReturn(
            [
                $template_service
            ]
        );

        $this->service_dao->shouldReceive('create')->withArgs(
            [
                $project->getID(),
                $service->getLabel(),
                '',
                $service->getDescription(),
                $service->getShortName(),
                '/tracker/group_id=101',
                1,
                1,
                $service->getScope(),
                $service->getRank(),
                $service->isOpenedInNewTab()
            ]
        )->once();

        $this->event_manager->shouldReceive('processEvent')->once();

        $this->service_activator->activateServicesFromTemplate($project, $template, $data, []);
    }

    public function testTV3ServiceUsageIsNotInheritedFromTemplate(): void
    {
        $project = Mockery::mock(\Project::class);
        $project->shouldReceive('getID')->andReturn(101);

        $template = \Mockery::mock(\Project::class);
        $template->shouldReceive('getID')->andReturn(201);
        $template->shouldReceive('isSystem')->twice()->andReturn(false);
        $template->shouldReceive('getUnixName')->once()->andReturn('test-name');

        $data = \Mockery::mock(\ProjectCreationData::class);
        $data->shouldReceive('getServiceInfo')->andReturn([]);
        $data->shouldReceive('isIsBuiltFromXml')->andReturnFalse();

        $template_service = ['service_id' => 10, 'short_name' => 'tracker', 'is_used' => 1];
        $this->service_dao->shouldReceive('getServiceInfoQueryForNewProject')->andReturn(
            [
                $template_service
            ]
        );

        $this->service_creator->shouldReceive('createService')->withArgs(
            [
                $template_service,
                101,
                [
                    'system'  => false,
                    'name'    => 'test-name',
                    'id'      => 201,
                    'is_used' => false,
                ]
            ]
        )->once();

        $this->event_manager->shouldReceive('processEvent')->once();

        $this->service_activator->activateServicesFromTemplate($project, $template, $data, []);
    }

    public function testSvnCoreServiceUsageIsInheritedFromXml(): void
    {
        $project = Mockery::mock(\Project::class);
        $project->shouldReceive('getID')->andReturn(101);

        $template = \Mockery::mock(\Project::class);
        $template->shouldReceive('getID')->andReturn(201);

        $data = \Mockery::mock(\ProjectCreationData::class);
        $data->shouldReceive('getServiceInfo')->andReturn(['is_used' => true]);
        $data->shouldReceive('isIsBuiltFromXml')->andReturnTrue();
        $data->shouldReceive('getDataServices')->andReturn([10 => ['is_used' => true]]);

        $service = Mockery::mock(\Service::class);
        $service->shouldReceive('getShortName')->andReturn('svn');
        $service->shouldReceive('getIcon')->andReturn('');
        $service->shouldReceive('getLabel')->andReturn('svn');
        $service->shouldReceive('getDescription')->andReturn('description');
        $service->shouldReceive('getUrl')->andReturn('/svn/group_id=$group_id');
        $service->shouldReceive('getScope')->andReturn('P');
        $service->shouldReceive('getRank')->andReturn('1');
        $service->shouldReceive('isOpenedInNewTab')->andReturn(false);
        $this->service_manager->shouldReceive('getService')->andReturn($service);

        $this->link_builder->shouldReceive('substituteVariablesInLink')->andReturn('/svn/group_id=101');

        $template_service = ['service_id' => 10, 'short_name' => 'svn', 'is_used' => 0];
        $this->service_dao->shouldReceive('getServiceInfoQueryForNewProject')->andReturn(
            [
                $template_service
            ]
        );

        $this->service_dao->shouldReceive('create')->withArgs(
            [
                $project->getID(),
                $service->getLabel(),
                '',
                $service->getDescription(),
                $service->getShortName(),
                '/svn/group_id=101',
                1,
                1,
                $service->getScope(),
                $service->getRank(),
                $service->isOpenedInNewTab()
            ]
        )->once();

        $this->event_manager->shouldReceive('processEvent')->once();

        $this->service_activator->activateServicesFromTemplate($project, $template, $data, []);
    }

    public function testSvnCoreServiceUsageIsNotInheritedFromTemplate(): void
    {
        $project = Mockery::mock(\Project::class);
        $project->shouldReceive('getID')->andReturn(101);

        $template = \Mockery::mock(\Project::class);
        $template->shouldReceive('getID')->andReturn(201);
        $template->shouldReceive('isSystem')->twice()->andReturn(false);
        $template->shouldReceive('getUnixName')->once()->andReturn('test-name');

        $data = \Mockery::mock(\ProjectCreationData::class);
        $data->shouldReceive('getServiceInfo')->andReturn([]);
        $data->shouldReceive('isIsBuiltFromXml')->andReturnFalse();

        $template_service = ['service_id' => 10, 'short_name' => 'svn', 'is_used' => 1];
        $this->service_dao->shouldReceive('getServiceInfoQueryForNewProject')->andReturn(
            [
                $template_service
            ]
        );

        $this->service_creator->shouldReceive('createService')->withArgs(
            [
                $template_service,
                101,
                [
                    'system'  => false,
                    'name'    => 'test-name',
                    'id'      => 201,
                    'is_used' => false,
                ]
            ]
        )->once();

        $this->event_manager->shouldReceive('processEvent')->once();

        $this->service_activator->activateServicesFromTemplate($project, $template, $data, []);
    }

    public function testServiceUsageInheritsPropertySetInXml(): void
    {
        $project = Mockery::mock(\Project::class);
        $project->shouldReceive('getID')->andReturn(101);

        $template = Mockery::mock(\Project::class);
        $template->shouldReceive('getID')->andReturn(201);

        $data = Mockery::mock(\ProjectCreationData::class);
        $data->shouldReceive('getServiceInfo')->andReturn([]);
        $data->shouldReceive('isIsBuiltFromXml')->andReturnTrue();
        $data->shouldReceive('getDataServices')->andReturn([10 => ['is_used' => true]]);

        $service = Mockery::mock(\Service::class);
        $service->shouldReceive('getShortName')->andReturn('document');
        $service->shouldReceive('getIcon')->andReturn('');
        $service->shouldReceive('getLabel')->andReturn('document');
        $service->shouldReceive('getDescription')->andReturn('description');
        $service->shouldReceive('getUrl')->andReturn('/document/test-name');
        $service->shouldReceive('getScope')->andReturn('P');
        $service->shouldReceive('getRank')->andReturn('1');
        $service->shouldReceive('isOpenedInNewTab')->andReturn(false);
        $this->service_manager->shouldReceive('getService')->andReturn($service);

        $this->link_builder->shouldReceive('substituteVariablesInLink')->andReturn('/document/test-name');

        $template_service = ['service_id' => 10, 'short_name' => 'document', 'is_used' => 0];
        $this->service_dao->shouldReceive('getServiceInfoQueryForNewProject')->andReturn(
            [
                $template_service
            ]
        );

        $this->service_dao->shouldReceive('create')->withArgs(
            [
                $project->getID(),
                $service->getLabel(),
                '',
                $service->getDescription(),
                $service->getShortName(),
                '/document/test-name',
                1,
                1,
                $service->getScope(),
                $service->getRank(),
                $service->isOpenedInNewTab()
            ]
        )->once();

        $this->event_manager->shouldReceive('processEvent')->once();

        $this->service_activator->activateServicesFromTemplate($project, $template, $data, []);
    }

    public function testAdminServiceShouldAlwaysBeEnabledForProjectFromXmlImport(): void
    {
        $project = Mockery::mock(\Project::class);
        $project->shouldReceive('getID')->andReturn(101);

        $template = Mockery::mock(\Project::class);
        $template->shouldReceive('getID')->andReturn(201);

        $data = Mockery::mock(\ProjectCreationData::class);
        $data->shouldReceive('getServiceInfo')->andReturn([]);
        $data->shouldReceive('isIsBuiltFromXml')->andReturnTrue();
        $data->shouldReceive('getDataServices')->andReturn([10 => false]);

        $service = Mockery::mock(\Service::class);
        $service->shouldReceive('getShortName')->andReturn('admin');
        $service->shouldReceive('getIcon')->andReturn('');
        $service->shouldReceive('getLabel')->andReturn('admin');
        $service->shouldReceive('getDescription')->andReturn('description');
        $service->shouldReceive('getUrl')->andReturn('/admin/group_id=101');
        $service->shouldReceive('getScope')->andReturn('P');
        $service->shouldReceive('getRank')->andReturn('1');
        $service->shouldReceive('isOpenedInNewTab')->andReturn(false);
        $this->service_manager->shouldReceive('getService')->andReturn($service);

        $this->link_builder->shouldReceive('substituteVariablesInLink')->andReturn('/admin/group_id=101');

        $this->service_dao->shouldReceive('create')->withArgs(
            [
                $project->getID(),
                $service->getLabel(),
                '',
                $service->getDescription(),
                $service->getShortName(),
                '/admin/group_id=101',
                1,
                1,
                $service->getScope(),
                $service->getRank(),
                $service->isOpenedInNewTab()
            ]
        )->once();

        $this->event_manager->shouldReceive('processEvent')->once();

        $this->service_activator->activateServicesFromTemplate($project, $template, $data, []);
    }
}
