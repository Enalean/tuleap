<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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
 * phpcs:disable PSR1.Classes.ClassDeclaration
 */

declare(strict_types=1);

namespace Tuleap\Project;

use ForgeAccess;
use ForgeConfig;
use PHPUnit\Framework\MockObject\MockObject;
use Project;
use ProjectManager;
use Service;
use ServiceManager;
use Tuleap\ForgeConfigSandbox;
use Tuleap\Project\Registration\Template\TemplateFromProjectForCreation;
use Tuleap\Project\XML\Import\ExternalFieldsExtractor;
use Tuleap\Test\Builders\ProjectTestBuilder;
use XML_RNGValidator;

final class ProjectCreationDataTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use ForgeConfigSandbox;

    private ProjectManager&MockObject $project_manager;
    private XML_RNGValidator&MockObject $xml_rngvalidator;
    private ServiceManager&MockObject $service_manager;
    private DefaultProjectVisibilityRetriever $default_project_visibility_retriever;

    protected function setUp(): void
    {
        $this->default_project_visibility_retriever = new DefaultProjectVisibilityRetriever();

        $this->xml_rngvalidator = $this->createMock(XML_RNGValidator::class);
        $this->xml_rngvalidator->method('validate');

        $admin_service = $this->createMock(Service::class);
        $admin_service->method('getShortName')->willReturn('admin');
        $admin_service->method('getId')->willReturn(1);
        $git_service = $this->createMock(Service::class);
        $git_service->method('getShortName')->willReturn('plugin_git');
        $git_service->method('getId')->willReturn(10);

        $this->service_manager = $this->createMock(ServiceManager::class);
        $this->service_manager->method('getListOfAllowedServicesForProject')->willReturn([$admin_service, $git_service,]);

        $this->project_manager = $this->createMock(ProjectManager::class);
        $this->project_manager->method('getProject')->with(100)->willReturn(ProjectTestBuilder::aProject()->build());
    }

    public function testItHasBasicMetadataFromProject(): void
    {
        $xml          = simplexml_load_string(file_get_contents(__DIR__ . '/_fixtures/ProjectCreationData/project_with_services.xml'));
        $project_data = ProjectCreationData::buildFromXML($xml, $this->xml_rngvalidator, $this->service_manager);
        self::assertEquals('kanbansampleproject', $project_data->getUnixName());
        self::assertEquals('Kanban Sample project', $project_data->getFullName());
        self::assertEquals('Control project workflow and focus on what’s hot with a card board. Connect it to development tools.', $project_data->getShortDescription());
        self::assertEquals(Project::ACCESS_PUBLIC, $project_data->getAccess());
    }

    public function testItLoadsPrivateProjects(): void
    {
        $xml                      = simplexml_load_string(file_get_contents(__DIR__ . '/_fixtures/ProjectCreationData/project_with_services.xml'));
        $xml['access']            = 'private';
        $external_field_extractor = $this->createMock(ExternalFieldsExtractor::class);
        $external_field_extractor->method('extractExternalFieldFromProjectElement');

        $project_data = ProjectCreationData::buildFromXML($xml, $this->xml_rngvalidator, $this->service_manager, null, null, $external_field_extractor);
        self::assertEquals(Project::ACCESS_PRIVATE, $project_data->getAccess());
    }

    public function testItLoadsPublicWithRestrictedProjects(): void
    {
        ForgeConfig::set(ForgeAccess::CONFIG, ForgeAccess::RESTRICTED);

        $xml           = simplexml_load_string(file_get_contents(__DIR__ . '/_fixtures/ProjectCreationData/project_with_services.xml'));
        $xml['access'] = 'unrestricted';
        $project_data  = ProjectCreationData::buildFromXML($xml, $this->xml_rngvalidator, $this->service_manager);
        self::assertEquals(Project::ACCESS_PUBLIC_UNRESTRICTED, $project_data->getAccess());
    }

    public function testItLoadsPrivateWithRestrictedProjects(): void
    {
        ForgeConfig::set(ForgeAccess::CONFIG, ForgeAccess::RESTRICTED);

        $xml           = simplexml_load_string(file_get_contents(__DIR__ . '/_fixtures/ProjectCreationData/project_with_services.xml'));
        $xml['access'] = 'private-wo-restr';
        $project_data  = ProjectCreationData::buildFromXML($xml, $this->xml_rngvalidator, $this->service_manager);
        self::assertEquals(Project::ACCESS_PRIVATE_WO_RESTRICTED, $project_data->getAccess());
    }

    public function testItCreatesProjectWithDefaultPlatformAccessWhenDataNotInXML(): void
    {
        ForgeConfig::set('sys_is_project_public', 1);

        $xml = simplexml_load_string(file_get_contents(__DIR__ . '/_fixtures/ProjectCreationData/project_with_services.xml'));
        unset($xml['access']);

        $project_data = ProjectCreationData::buildFromXML($xml, $this->xml_rngvalidator, $this->service_manager);
        self::assertEquals(Project::ACCESS_PUBLIC, $project_data->getAccess());
    }

    public function testItCreatesAPrivateProjectFromWebPayload(): void
    {
        ForgeConfig::set(ProjectManager::SYS_USER_CAN_CHOOSE_PROJECT_PRIVACY, 1);

        $project_data = ProjectCreationData::buildFromFormArray(
            $this->default_project_visibility_retriever,
            TemplateFromProjectForCreation::fromGlobalProjectAdminTemplate(),
            [
                'project' => [
                    'is_public' => '0',
                ],
            ]
        );

        self::assertEquals(Project::ACCESS_PRIVATE, $project_data->getAccess());
    }

    /**
     * @testWith [true, true, "unrestricted"]
     *           [true, false, "public"]
     *           [false, true, "private"]
     *           [false, false, "private-wo-restr"]
     */
    public function testItCreatesAProjectWithRestrictedVisibilityFromWebPayload(
        bool $is_public,
        bool $allow_restricted,
        string $expected_visibility,
    ): void {
        ForgeConfig::set(ProjectManager::SYS_USER_CAN_CHOOSE_PROJECT_PRIVACY, 1);
        ForgeConfig::set(ForgeAccess::CONFIG, ForgeAccess::RESTRICTED);

        $web_payload = [
            'project' => [
                'is_public'        => $is_public ? '1' : '0',
                'allow_restricted' => $allow_restricted ? '1' : '0',
            ],
        ];

        $project_data = ProjectCreationData::buildFromFormArray(
            $this->default_project_visibility_retriever,
            TemplateFromProjectForCreation::fromGlobalProjectAdminTemplate(),
            $web_payload
        );

        self::assertEquals($expected_visibility, $project_data->getAccess());
    }

    public function testItCreatesAPublicProjectFromWebPayload(): void
    {
        ForgeConfig::set(ProjectManager::SYS_USER_CAN_CHOOSE_PROJECT_PRIVACY, 1);

        $project_data = ProjectCreationData::buildFromFormArray(
            $this->default_project_visibility_retriever,
            TemplateFromProjectForCreation::fromGlobalProjectAdminTemplate(),
            [
                'project' => [
                    'is_public' => '1',
                ],
            ]
        );

        self::assertEquals(Project::ACCESS_PUBLIC, $project_data->getAccess());
    }

    public function testItTakesPlatformConfigWhenNoDataSent(): void
    {
        ForgeConfig::set(ProjectManager::SYS_USER_CAN_CHOOSE_PROJECT_PRIVACY, 1);
        ForgeConfig::set('sys_is_project_public', 0);

        $project_data = ProjectCreationData::buildFromFormArray(
            $this->default_project_visibility_retriever,
            TemplateFromProjectForCreation::fromGlobalProjectAdminTemplate(),
            []
        );

        self::assertEquals(Project::ACCESS_PRIVATE, $project_data->getAccess());
    }
}
