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

use Mockery as M;
use PHPUnit\Framework\TestCase;
use Tuleap\Configuration\Logger\LoggerInterface;
use Tuleap\Project\DefaultProjectVisibilityRetriever;
use Tuleap\Project\Registration\Template\TemplateFromProjectForCreation;

class ProjectCreationDataTest extends TestCase
{
    use M\Adapter\Phpunit\MockeryPHPUnitIntegration;

    /**
     * @var M\MockInterface|ProjectManager
     */
    private $project_manager;
    /**
     * @var M\MockInterface|LoggerInterface
     */
    private $logger;
    /**
     * @var M\MockInterface|XML_RNGValidator
     */
    private $xml_rngvalidator;
    /**
     * @var M\MockInterface|ServiceManager
     */
    private $service_manager;
    /**
     * @var DefaultProjectVisibilityRetriever
     */
    private $default_project_visibility_retriever;

    protected function setUp(): void
    {
        $this->default_project_visibility_retriever = new DefaultProjectVisibilityRetriever();

        $this->xml_rngvalidator = M::mock(XML_RNGValidator::class);
        $this->xml_rngvalidator->shouldReceive('validate');
        $this->service_manager  = M::mock(ServiceManager::class);
        $this->service_manager->shouldReceive('getListOfAllowedServicesForProject')->andReturns([]);
        $this->project_manager  = M::mock(ProjectManager::class);
        $this->project_manager->shouldReceive('getProject')->with(100)->andReturns(M::mock(Project::class));
        $this->logger           = M::spy(LoggerInterface::class);
        ForgeConfig::store();
    }

    protected function tearDown(): void
    {
        ForgeConfig::restore();
    }

    public function testItHasBasicMetadataFromProject()
    {
        $xml = simplexml_load_string(file_get_contents(__DIR__ . '/_fixtures/ProjectCreationData/project_with_services.xml'));
        $project_data = ProjectCreationData::buildFromXML($xml, $this->xml_rngvalidator, $this->service_manager);
        $this->assertEquals('kanbansampleproject', $project_data->getUnixName());
        $this->assertEquals('Kanban Sample project', $project_data->getFullName());
        $this->assertEquals('Control project workflow and focus on what’s hot with a card board. Connect it to development tools.', $project_data->getShortDescription());
        $this->assertEquals(Project::ACCESS_PUBLIC, $project_data->getAccess());
    }

    public function testItLoadsPrivateProjects()
    {
        $xml = simplexml_load_string(file_get_contents(__DIR__ . '/_fixtures/ProjectCreationData/project_with_services.xml'));
        $xml['access'] = 'private';
        $project_data = ProjectCreationData::buildFromXML($xml, $this->xml_rngvalidator, $this->service_manager);
        $this->assertEquals(Project::ACCESS_PRIVATE, $project_data->getAccess());
    }

    public function testItLoadsPublicWithRestrictedProjects()
    {
        ForgeConfig::set(ForgeAccess::CONFIG, ForgeAccess::RESTRICTED);

        $xml = simplexml_load_string(file_get_contents(__DIR__ . '/_fixtures/ProjectCreationData/project_with_services.xml'));
        $xml['access'] = 'unrestricted';
        $project_data = ProjectCreationData::buildFromXML($xml, $this->xml_rngvalidator, $this->service_manager);
        $this->assertEquals(Project::ACCESS_PUBLIC_UNRESTRICTED, $project_data->getAccess());
    }

    public function testItLoadsPrivateWithRestrictedProjects()
    {
        ForgeConfig::set(ForgeAccess::CONFIG, ForgeAccess::RESTRICTED);

        $xml = simplexml_load_string(file_get_contents(__DIR__ . '/_fixtures/ProjectCreationData/project_with_services.xml'));
        $xml['access'] = 'private-wo-restr';
        $project_data = ProjectCreationData::buildFromXML($xml, $this->xml_rngvalidator, $this->service_manager);
        $this->assertEquals(Project::ACCESS_PRIVATE_WO_RESTRICTED, $project_data->getAccess());
    }

    public function testItThrowAnExceptionWithUnrestrictedProjectsOnNonRestrictedPlatform()
    {
        ForgeConfig::set(ForgeAccess::CONFIG, ForgeAccess::ANONYMOUS);

        $xml = simplexml_load_string(file_get_contents(__DIR__ . '/_fixtures/ProjectCreationData/project_with_services.xml'));
        $xml['access'] = 'unrestricted';

        $this->expectException(Tuleap\Project\XML\Import\ImportNotValidException::class);

        ProjectCreationData::buildFromXML($xml, $this->xml_rngvalidator, $this->service_manager);
    }

    public function testItThrowAnExceptionWithPrivateWoRestrictedProjectsOnNonRestrictedPlatform()
    {
        ForgeConfig::set(ForgeAccess::CONFIG, ForgeAccess::ANONYMOUS);

        $xml = simplexml_load_string(file_get_contents(__DIR__ . '/_fixtures/ProjectCreationData/project_with_services.xml'));
        $xml['access'] = 'private-wo-restr';

        $this->expectException(Tuleap\Project\XML\Import\ImportNotValidException::class);

        ProjectCreationData::buildFromXML($xml, $this->xml_rngvalidator, $this->service_manager);
    }

    public function testItCreatesProjectWithDefaultPlatformAccessWhenDataNotInXML()
    {
        ForgeConfig::set('sys_is_project_public', 1);

        $xml = simplexml_load_string(file_get_contents(__DIR__ . '/_fixtures/ProjectCreationData/project_with_services.xml'));
        unset($xml['access']);

        $project_data = ProjectCreationData::buildFromXML($xml, $this->xml_rngvalidator, $this->service_manager);
        $this->assertEquals(Project::ACCESS_PUBLIC, $project_data->getAccess());
    }

    public function testItCreatesAPrivateProjectFromWebPayload() : void
    {
        ForgeConfig::set('sys_user_can_choose_project_privacy', 1);

        $project_data = ProjectCreationData::buildFromFormArray(
            $this->default_project_visibility_retriever,
            TemplateFromProjectForCreation::fromGlobalProjectAdminTemplate(),
            [
                'project' => [
                    'is_public' => '0',
                 ],
            ]
        );

        $this->assertEquals(Project::ACCESS_PRIVATE, $project_data->getAccess());
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
        string $expected_visibility
    ) : void {
        ForgeConfig::set('sys_user_can_choose_project_privacy', 1);
        ForgeConfig::set(ForgeAccess::CONFIG, ForgeAccess::RESTRICTED);

        $web_payload = [
            'project' => [
                'is_public'           => $is_public ? '1' : '0',
            ],
        ];
        if ($allow_restricted) {
            $web_payload['project']['allow_restricted'] = '1';
        }

        $project_data = ProjectCreationData::buildFromFormArray(
            $this->default_project_visibility_retriever,
            TemplateFromProjectForCreation::fromGlobalProjectAdminTemplate(),
            $web_payload
        );

        $this->assertEquals($expected_visibility, $project_data->getAccess());
    }


    public function testItCreatesAPublicProjectFromWebPayload()
    {
        ForgeConfig::set('sys_user_can_choose_project_privacy', 1);

        $project_data = ProjectCreationData::buildFromFormArray(
            $this->default_project_visibility_retriever,
            TemplateFromProjectForCreation::fromGlobalProjectAdminTemplate(),
            [
                'project' => [
                    'is_public' => '1'
                ],
            ]
        );

        $this->assertEquals(Project::ACCESS_PUBLIC, $project_data->getAccess());
    }

    public function testItTakesPublicWhenSiteAdminDecidedToMakeAllProjectsPublicByDefault()
    {
        ForgeConfig::set('sys_user_can_choose_project_privacy', 0);
        ForgeConfig::set('sys_is_project_public', 1);

        $project_data = ProjectCreationData::buildFromFormArray(
            $this->default_project_visibility_retriever,
            TemplateFromProjectForCreation::fromGlobalProjectAdminTemplate(),
            [
                'project' => [
                    'is_public' => '0',
                ],
            ]
        );

        $this->assertEquals(Project::ACCESS_PUBLIC, $project_data->getAccess());
    }

    public function testItTakesPrivateWhenSiteAdminDecidedToMakeAllProjectsPrivateByDefault()
    {
        ForgeConfig::set('sys_user_can_choose_project_privacy', 0);
        ForgeConfig::set('sys_is_project_public', 0);

        $project_data = ProjectCreationData::buildFromFormArray(
            $this->default_project_visibility_retriever,
            TemplateFromProjectForCreation::fromGlobalProjectAdminTemplate(),
            [
                'project' => [
                    'is_public' => '1',
                ],
            ]
        );

        $this->assertEquals(Project::ACCESS_PRIVATE, $project_data->getAccess());
    }

    public function testItTakesPlatformConfigWhenNoDataSent()
    {
        ForgeConfig::set('sys_user_can_choose_project_privacy', 1);
        ForgeConfig::set('sys_is_project_public', 0);

        $project_data = ProjectCreationData::buildFromFormArray(
            $this->default_project_visibility_retriever,
            TemplateFromProjectForCreation::fromGlobalProjectAdminTemplate(),
            []
        );

        $this->assertEquals(Project::ACCESS_PRIVATE, $project_data->getAccess());
    }
}
