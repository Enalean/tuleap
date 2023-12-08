<?php
/**
 * Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
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
use PHPUnit\Framework\MockObject\MockObject;
use ReferenceManager;
use ServiceDao;
use Tuleap\Project\Admin\Service\ProjectServiceActivator;
use Tuleap\Project\Registration\Template\TemplateFromProjectForCreation;
use Tuleap\Project\Service\ServiceLinkDataBuilder;
use Tuleap\Service\ServiceCreator;
use Tuleap\Test\Builders\ProjectTestBuilder;

final class ProjectServiceActivatorTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private ServiceLinkDataBuilder&MockObject $link_builder;
    private \ServiceManager&MockObject $service_manager;
    private ProjectServiceActivator $service_activator;
    private ServiceDao&MockObject $service_dao;
    private EventManager&MockObject $event_manager;
    private ServiceCreator&MockObject $service_creator;
    private ReferenceManager&MockObject $reference_manager;

    protected function setUp(): void
    {
        $this->service_creator   = $this->createMock(ServiceCreator::class);
        $this->event_manager     = $this->createMock(EventManager::class);
        $this->service_dao       = $this->createMock(ServiceDao::class);
        $this->service_manager   = $this->createMock(\ServiceManager::class);
        $this->link_builder      = $this->createMock(ServiceLinkDataBuilder::class);
        $this->reference_manager = $this->createMock(ReferenceManager::class);

        $this->service_activator = new ProjectServiceActivator(
            $this->service_creator,
            $this->event_manager,
            $this->service_dao,
            $this->service_manager,
            $this->link_builder,
            $this->reference_manager
        );
    }

    public function testServiceUsageIsInheritedFromData(): void
    {
        $project = ProjectTestBuilder::aProject()->withId(101)->build();

        $template = ProjectTestBuilder::aProject()
            ->withId(201)
            ->withUnixName('test-name')
            ->build();

        $data = $this->createMock(\ProjectCreationData::class);
        $data->method('getServiceInfo')->willReturn(['is_used' => true]);
        $data->method('isIsBuiltFromXml')->willReturn(false);

        $template_service = ['service_id' => 10, 'short_name' => 'document', 'is_used' => 0];
        $this->service_dao->method('getServiceInfoQueryForNewProject')->willReturn([$template_service]);

        $this->service_creator->expects(self::once())->method('createService')->with(
            $template_service,
            101,
            [
                'system'  => false,
                'name'    => 'test-name',
                'id'      => 201,
                'is_used' => 1,
            ]
        );

        $this->event_manager->expects(self::once())->method('processEvent');

        $this->service_activator->activateServicesFromTemplate($project, $template, $data, []);
    }

    public function testServiceUsageIsInheritedFromTemplate(): void
    {
        $project = ProjectTestBuilder::aProject()->withId(101)->build();

        $template = ProjectTestBuilder::aProject()
            ->withId(201)
            ->withUnixName('test-name')
            ->build();

        $data = $this->createMock(\ProjectCreationData::class);
        $data->method('getServiceInfo')->willReturn([]);
        $data->method('isIsBuiltFromXml')->willReturn(false);

        $template_service       = ['service_id' => 10, 'short_name' => 'document', 'is_used' => 1];
        $other_template_service = ['service_id' => 10, 'short_name' => 'document', 'is_used' => 1];
        $this->service_dao->method('getServiceInfoQueryForNewProject')->willReturn([
            $template_service,
            $other_template_service,
        ]);

        $this->service_creator->expects(self::exactly(2))->method('createService')->withConsecutive(
            [
                $template_service,
                101,
                [
                    'system'  => false,
                    'name'    => 'test-name',
                    'id'      => 201,
                    'is_used' => 1,
                ],
            ],
            [
                $other_template_service,
                101,
                [
                    'system'  => false,
                    'name'    => 'test-name',
                    'id'      => 201,
                    'is_used' => 1,
                ],
            ]
        );

        $this->event_manager->expects(self::once())->method('processEvent');

        $this->service_activator->activateServicesFromTemplate($project, $template, $data, []);
    }

    public function testAdminServiceIsAlwaysActive(): void
    {
        $project = ProjectTestBuilder::aProject()->withId(101)->build();

        $template = ProjectTestBuilder::aProject()
            ->withId(201)
            ->withUnixName('test-name')
            ->build();

        $data = $this->createMock(\ProjectCreationData::class);
        $data->method('getServiceInfo')->willReturn([]);
        $data->method('isIsBuiltFromXml')->willReturn(false);

        $template_service = ['service_id' => 10, 'short_name' => 'admin', 'is_used' => 0];
        $this->service_dao->method('getServiceInfoQueryForNewProject')->willReturn([$template_service]);

        $this->service_creator->expects(self::once())->method('createService')->with(
            $template_service,
            101,
            [
                'system'  => false,
                'name'    => 'test-name',
                'id'      => 201,
                'is_used' => 1,
            ]
        );

        $this->event_manager->expects(self::once())->method('processEvent');

        $this->service_activator->activateServicesFromTemplate($project, $template, $data, []);
    }

    public function testTV3ServiceUsageIsInheritedFromXml(): void
    {
        $project = ProjectTestBuilder::aProject()->withId(101)->build();

        $template_project = ProjectTestBuilder::aProject()->withId(201)->build();

        $data = $this->createMock(\ProjectCreationData::class);
        $data->method('getServiceInfo')->willReturn(['is_used' => true]);
        $data->method('isIsBuiltFromXml')->willReturn(true);
        $data->method('getDataServices')->willReturn([10 => ['is_used' => true]]);

        $template_for_creation = $this->createMock(TemplateFromProjectForCreation::class);
        $template_for_creation->method('getProject')->willReturn($template_project);
        $data->method('getBuiltFromTemplateProject')->willReturn($template_for_creation);

        $service = $this->createMock(\Service::class);
        $service->method('getShortName')->willReturn('tracker');
        $service->method('getIcon')->willReturn('');
        $service->method('getLabel')->willReturn('tracker');
        $service->method('getDescription')->willReturn('description');
        $service->method('getUrl')->willReturn('/tracker/group_id=$group_id');
        $service->method('getScope')->willReturn('P');
        $service->method('getRank')->willReturn(1);
        $service->method('isOpenedInNewTab')->willReturn(false);
        $this->service_manager->method('getService')->willReturn($service);

        $this->link_builder->method('substituteVariablesInLink')->willReturn('/tracker/group_id=101');

        $template_service = ['service_id' => 10, 'short_name' => 'tracker', 'is_used' => 0];
        $this->service_dao->method('getServiceInfoQueryForNewProject')->willReturn([$template_service]);

        $this->service_dao->expects(self::once())->method('create')->with(
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
        );

        $this->event_manager->expects(self::once())->method('processEvent');

        $this->reference_manager->expects(self::once())->method('addSystemReferencesForService');
        $this->reference_manager->expects(self::once())->method('updateReferenceForService');

        $this->service_activator->activateServicesFromTemplate($project, $template_project, $data, []);
    }

    public function testTV3ServiceUsageIsNotInheritedFromTemplate(): void
    {
        $project = ProjectTestBuilder::aProject()->withId(101)->build();

        $template = ProjectTestBuilder::aProject()
            ->withId(201)
            ->withUnixName('test-name')
            ->build();

        $data = $this->createMock(\ProjectCreationData::class);
        $data->method('getServiceInfo')->willReturn([]);
        $data->method('isIsBuiltFromXml')->willReturn(false);

        $template_service = ['service_id' => 10, 'short_name' => 'tracker', 'is_used' => 1];
        $this->service_dao->method('getServiceInfoQueryForNewProject')->willReturn([$template_service]);

        $this->service_creator->expects(self::once())->method('createService')->with(
            $template_service,
            101,
            [
                'system'  => false,
                'name'    => 'test-name',
                'id'      => 201,
                'is_used' => false,
            ]
        );

        $this->event_manager->expects(self::once())->method('processEvent');

        $this->service_activator->activateServicesFromTemplate($project, $template, $data, []);
    }

    public function testSvnCoreServiceUsageIsInheritedFromXml(): void
    {
        $project = ProjectTestBuilder::aProject()->withId(101)->build();

        $template_project = ProjectTestBuilder::aProject()->withId(201)->build();

        $data = $this->createMock(\ProjectCreationData::class);
        $data->method('getServiceInfo')->willReturn(['is_used' => true]);
        $data->method('isIsBuiltFromXml')->willReturn(true);
        $data->method('getDataServices')->willReturn([10 => ['is_used' => true]]);

        $template_for_creation = $this->createMock(TemplateFromProjectForCreation::class);
        $template_for_creation->method('getProject')->willReturn($template_project);
        $data->method('getBuiltFromTemplateProject')->willReturn($template_for_creation);

        $service = $this->createMock(\Service::class);
        $service->method('getShortName')->willReturn('svn');
        $service->method('getIcon')->willReturn('');
        $service->method('getLabel')->willReturn('svn');
        $service->method('getDescription')->willReturn('description');
        $service->method('getUrl')->willReturn('/svn/group_id=$group_id');
        $service->method('getScope')->willReturn('P');
        $service->method('getRank')->willReturn(1);
        $service->method('isOpenedInNewTab')->willReturn(false);
        $this->service_manager->method('getService')->willReturn($service);

        $this->link_builder->method('substituteVariablesInLink')->willReturn('/svn/group_id=101');

        $template_service = ['service_id' => 10, 'short_name' => 'svn', 'is_used' => 0];
        $this->service_dao->method('getServiceInfoQueryForNewProject')->willReturn([$template_service]);

        $this->service_dao->expects(self::once())->method('create')->with(
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
        );

        $this->event_manager->expects(self::once())->method('processEvent');

        $this->reference_manager->expects(self::once())->method('addSystemReferencesForService');
        $this->reference_manager->expects(self::once())->method('updateReferenceForService');

        $this->service_activator->activateServicesFromTemplate($project, $template_project, $data, []);
    }

    public function testSvnCoreServiceUsageIsNotInheritedFromTemplate(): void
    {
        $project = ProjectTestBuilder::aProject()->withId(101)->build();

        $template = ProjectTestBuilder::aProject()
            ->withId(201)
            ->withUnixName('test-name')
            ->build();

        $data = $this->createMock(\ProjectCreationData::class);
        $data->method('getServiceInfo')->willReturn([]);
        $data->method('isIsBuiltFromXml')->willReturn(false);

        $template_service = ['service_id' => 10, 'short_name' => 'svn', 'is_used' => 1];
        $this->service_dao->method('getServiceInfoQueryForNewProject')->willReturn([$template_service]);

        $this->service_creator->expects(self::once())->method('createService')->with(
            $template_service,
            101,
            [
                'system'  => false,
                'name'    => 'test-name',
                'id'      => 201,
                'is_used' => false,
            ]
        );

        $this->event_manager->expects(self::once())->method('processEvent');

        $this->service_activator->activateServicesFromTemplate($project, $template, $data, []);
    }

    public function testServiceUsageInheritsPropertySetInXml(): void
    {
        $project = ProjectTestBuilder::aProject()->withId(101)->build();

        $template_project = ProjectTestBuilder::aProject()->withId(201)->build();

        $data = $this->createMock(\ProjectCreationData::class);
        $data->method('getServiceInfo')->willReturn([]);
        $data->method('isIsBuiltFromXml')->willReturn(true);
        $data->method('getDataServices')->willReturn([10 => ['is_used' => true]]);

        $template_for_creation = $this->createMock(TemplateFromProjectForCreation::class);
        $template_for_creation->method('getProject')->willReturn($template_project);
        $data->method('getBuiltFromTemplateProject')->willReturn($template_for_creation);

        $service = $this->createMock(\Service::class);
        $service->method('getShortName')->willReturn('document');
        $service->method('getIcon')->willReturn('');
        $service->method('getLabel')->willReturn('document');
        $service->method('getDescription')->willReturn('description');
        $service->method('getUrl')->willReturn('/document/test-name');
        $service->method('getScope')->willReturn('P');
        $service->method('getRank')->willReturn(1);
        $service->method('isOpenedInNewTab')->willReturn(false);
        $this->service_manager->method('getService')->willReturn($service);

        $this->link_builder->method('substituteVariablesInLink')->willReturn('/document/test-name');

        $template_service = ['service_id' => 10, 'short_name' => 'document', 'is_used' => 0];
        $this->service_dao->method('getServiceInfoQueryForNewProject')->willReturn([$template_service]);

        $this->service_dao->expects(self::once())->method('create')->with(
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
        );

        $this->event_manager->expects(self::once())->method('processEvent');

        $this->reference_manager->expects(self::once())->method('addSystemReferencesForService');
        $this->reference_manager->expects(self::once())->method('updateReferenceForService');

        $this->service_activator->activateServicesFromTemplate($project, $template_project, $data, []);
    }

    public function testAdminServiceShouldAlwaysBeEnabledForProjectFromXmlImport(): void
    {
        $project = ProjectTestBuilder::aProject()->withId(101)->build();

        $template_project = ProjectTestBuilder::aProject()->withId(201)->build();

        $data = $this->createMock(\ProjectCreationData::class);
        $data->method('getServiceInfo')->willReturn([]);
        $data->method('isIsBuiltFromXml')->willReturn(true);
        $data->method('getDataServices')->willReturn([10 => false]);

        $template_for_creation = $this->createMock(TemplateFromProjectForCreation::class);
        $template_for_creation->method('getProject')->willReturn($template_project);
        $data->method('getBuiltFromTemplateProject')->willReturn($template_for_creation);

        $service = $this->createMock(\Service::class);
        $service->method('getShortName')->willReturn('admin');
        $service->method('getIcon')->willReturn('');
        $service->method('getLabel')->willReturn('admin');
        $service->method('getDescription')->willReturn('description');
        $service->method('getUrl')->willReturn('/admin/group_id=101');
        $service->method('getScope')->willReturn('P');
        $service->method('getRank')->willReturn(1);
        $service->method('isOpenedInNewTab')->willReturn(false);
        $this->service_manager->method('getService')->willReturn($service);

        $this->link_builder->method('substituteVariablesInLink')->willReturn('/admin/group_id=101');

        $this->service_dao->expects(self::once())->method('create')->with(
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
        );

        $this->event_manager->expects(self::once())->method('processEvent');

        $this->reference_manager->expects(self::once())->method('addSystemReferencesForService');
        $this->reference_manager->expects(self::once())->method('updateReferenceForService');

        $this->service_activator->activateServicesFromTemplate($project, $template_project, $data, []);
    }

    public function testGitServiceUsageIsInheritedFromXml(): void
    {
        $project = ProjectTestBuilder::aProject()->withId(101)->build();

        $template_project = ProjectTestBuilder::aProject()->withId(201)->build();

        $data = $this->createMock(\ProjectCreationData::class);
        $data->method('getServiceInfo')->willReturn(['is_used' => true]);
        $data->method('isIsBuiltFromXml')->willReturn(true);
        $data->method('getDataServices')->willReturn([10 => ['is_used' => true]]);

        $template_for_creation = $this->createMock(TemplateFromProjectForCreation::class);
        $template_for_creation->method('getProject')->willReturn($template_project);
        $data->method('getBuiltFromTemplateProject')->willReturn($template_for_creation);

        $service = $this->createMock(\Service::class);
        $service->method('getShortName')->willReturn('plugin_git');
        $service->method('getIcon')->willReturn('');
        $service->method('getLabel')->willReturn('git');
        $service->method('getDescription')->willReturn('description');
        $service->method('getUrl')->willReturn('/git/group_id=$group_id');
        $service->method('getScope')->willReturn('P');
        $service->method('getRank')->willReturn(1);
        $service->method('isOpenedInNewTab')->willReturn(false);
        $this->service_manager->method('getService')->willReturn($service);

        $this->link_builder->method('substituteVariablesInLink')->willReturn('/git/group_id=101');

        $template_service = ['service_id' => 10, 'short_name' => 'plugin_git', 'is_used' => 0];
        $this->service_dao->method('getServiceInfoQueryForNewProject')->willReturn([$template_service]);

        $this->service_dao->expects(self::once())->method('create')->with(
            $project->getID(),
            $service->getLabel(),
            '',
            $service->getDescription(),
            $service->getShortName(),
            '/git/group_id=101',
            1,
            1,
            $service->getScope(),
            $service->getRank(),
            $service->isOpenedInNewTab()
        );

        $this->event_manager->expects(self::once())->method('processEvent');

        $this->reference_manager->expects(self::once())->method('addSystemReferencesForService');
        $this->reference_manager->expects(self::once())->method('updateReferenceForService');

        $this->service_activator->activateServicesFromTemplate($project, $template_project, $data, []);
    }
}
