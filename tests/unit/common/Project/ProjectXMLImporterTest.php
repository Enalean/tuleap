<?php
/**
 * Copyright (c) Enalean, 2013 - Present. All Rights Reserved.
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

use Event;
use PHPUnit\Framework\MockObject\MockObject;
use Project_NotFoundException;
use ProjectCreator;
use ProjectXMLImporter;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use SimpleXMLElement;
use Tuleap\Project\Admin\Categories\ProjectCategoriesUpdater;
use Tuleap\Project\Admin\Service\ProjectServiceActivator;
use Tuleap\Project\Email\EmailCopier;
use Tuleap\Project\Registration\ProjectRegistrationChecker;
use Tuleap\Project\UGroups\Membership\DynamicUGroups\ProjectMemberAdder;
use Tuleap\Project\UGroups\SynchronizedProjectMembershipDao;
use Tuleap\Project\XML\Import;
use Tuleap\Project\XML\XMLFileContentRetriever;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\ProjectUGroupTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\Stubs\Project\Registration\StoreProjectInformationStub;
use Tuleap\XML\MappingsRegistry;
use XML_RNGValidator;
use XMLImportHelper;

final class ProjectXMLImporterTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private \EventManager&MockObject $event_manager;
    private \ProjectManager&MockObject $project_manager;
    private \Project $project;
    private \UGroupManager&MockObject $ugroup_manager;
    private string $xml_file_path              = __DIR__ . '/_fixtures/ProjectXMLImporter/fake_project.xml';
    private string $xml_file_path_with_ugroups = __DIR__ . '/_fixtures/ProjectXMLImporter/fake_project_with_ugroups.xml';
    private \UserManager&MockObject $user_manager;
    private LoggerInterface $logger;
    private Import\ImportConfig $configuration;
    private ProjectXMLImporter $xml_importer;
    private XMLImportHelper $user_finder;
    private UgroupDuplicator&MockObject $ugroup_duplicator;
    private string $xml_file_path_with_members = __DIR__ . '/_fixtures/ProjectXMLImporter/fake_project_with_project_members.xml';
    private SynchronizedProjectMembershipDao&MockObject $sync_members;

    protected function setUp(): void
    {
        $this->event_manager   = $this->createMock(\EventManager::class);
        $this->project_manager = $this->createMock(\ProjectManager::class);
        $this->project         = ProjectTestBuilder::aProject()->withId(122)->build();
        $this->ugroup_manager  = $this->createMock(\UGroupManager::class);
        $this->user_manager    = $this->createMock(\UserManager::class);
        $this->user_manager->method('getCurrentUser')->willReturn(UserTestBuilder::buildWithDefaults());
        $this->user_finder       = new XMLImportHelper($this->user_manager);
        $this->logger            = new NullLogger();
        $this->ugroup_duplicator = $this->createMock(\Tuleap\Project\UgroupDuplicator::class);
        $this->sync_members      = $this->createMock(\Tuleap\Project\UGroups\SynchronizedProjectMembershipDao::class);
        $frs_permissions_creator = $this->createMock(\Tuleap\FRS\FRSPermissionCreator::class);
        $user_removal            = $this->createMock(\Tuleap\Project\UserRemover::class);

        $project_creator = new ProjectCreator(
            $this->project_manager,
            $this->createMock(\ReferenceManager::class),
            $this->user_manager,
            $this->ugroup_duplicator,
            false,
            $frs_permissions_creator,
            $this->createMock(\Tuleap\FRS\LicenseAgreement\LicenseAgreementFactory::class),
            $this->createMock(\Tuleap\Dashboard\Project\ProjectDashboardDuplicator::class),
            $this->createMock(\Tuleap\Project\Label\LabelDao::class),
            $this->createMock(\Tuleap\Project\UGroups\SynchronizedProjectMembershipDuplicator::class),
            $this->event_manager,
            $this->createMock(\Tuleap\Project\Admin\DescriptionFields\FieldUpdator::class),
            $this->createMock(ProjectServiceActivator::class),
            $this->createMock(ProjectRegistrationChecker::class),
            $this->createMock(ProjectCategoriesUpdater::class),
            $this->createStub(EmailCopier::class),
            StoreProjectInformationStub::build(),
            false
        );

        $dashboard_importer = $this->createMock(\Tuleap\Dashboard\Project\ProjectDashboardXMLImporter::class);
        $dashboard_importer->method('import');
        $this->xml_importer = new ProjectXMLImporter(
            $this->event_manager,
            $this->project_manager,
            $this->user_manager,
            new XML_RNGValidator(),
            $this->ugroup_manager,
            $this->user_finder,
            $this->createMock(\ServiceManager::class),
            $this->logger,
            $frs_permissions_creator,
            $user_removal,
            $this->createMock(ProjectMemberAdder::class),
            $project_creator,
            $this->createMock(\Tuleap\FRS\UploadedLinksUpdater::class),
            $dashboard_importer,
            $this->sync_members,
            new XMLFileContentRetriever(),
            $this->createMock(DescriptionFieldsFactory::class)
        );

        $this->configuration = new Import\ImportConfig();
    }

    public function testItAsksToPluginToImportInformationsFromTheGivenXml(): void
    {
        $this->project_manager->method('getValidProjectByShortNameOrId')->willReturn($this->project);

        $this->event_manager->expects(self::once())->method('processEvent')->with(Event::IMPORT_XML_PROJECT, self::callback(function ($args) {
            return $args['logger'] === $this->logger &&
                   $args['project'] === $this->project &&
                   $args['extraction_path'] === '' &&
                   $args['user_finder'] === $this->user_finder &&
                   $args['configuration'] === $this->configuration &&
                   $args['mappings_registery'] instanceof MappingsRegistry &&
                   $args['xml_content'] instanceof SimpleXMLElement;
        }));

        $this->xml_importer->import($this->configuration, 369, $this->xml_file_path);
    }

    public function testItAsksProjectManagerForTheProject(): void
    {
        $this->project_manager->expects(self::once())->method('getValidProjectByShortNameOrId')->with(122)->willReturn($this->project);
        $this->event_manager->method('processEvent');

        $this->xml_importer->import($this->configuration, 122, $this->xml_file_path);
    }

    public function testItStopsIfNoProjectIsFound(): void
    {
        $this->project_manager->method('getValidProjectByShortNameOrId')->willThrowException(new Project_NotFoundException());
        self::expectException('Project_NotFoundException');

        $this->xml_importer->import($this->configuration, 122, $this->xml_file_path);
    }

    public function testItImportsProjectDataWithUgroups(): void
    {
        $this->project_manager->method('getValidProjectByShortNameOrId')->willReturn($this->project);
        $this->ugroup_manager->method('getUGroupByName')->willReturn(null);

        $user_01 = UserTestBuilder::aUser()->withLdapId('ldap_01')->withUserName('user_01')->withId(101)->build();
        $user_02 = UserTestBuilder::aUser()->withLdapId('ldap_02')->withUserName('user_02')->withId(102)->build();
        $user_03 = UserTestBuilder::aUser()->withLdapId('ldap_03')->withUserName('user_03')->withId(103)->build();
        $user_04 = UserTestBuilder::aUser()->withUserName('user_04')->withId(104)->build();

        $this->user_manager->method('getUserByIdentifier')
            ->withConsecutive(
                ['ldapId:ldap_01'],
                ['ldapId:ldap_02'],
                ['user_04'],
                ['ldapId:ldap_03'],
            )
            ->willReturnOnConsecutiveCalls(
                $user_01,
                $user_02,
                $user_04,
                $user_03,
            );

        $this->ugroup_manager->method('createEmptyUgroup')
            ->withConsecutive(
                [122, 'ug01', 'descr01'],
                [122, 'ug02', 'descr02'],
                [122, 'ug03', 'descr03']
            )
            ->willReturnOnConsecutiveCalls(555, 556, 557);

        $ug01 = $this->createMock(\ProjectUGroup::class);
        $ug02 = $this->createMock(\ProjectUGroup::class);
        $ug03 = $this->createMock(\ProjectUGroup::class);

        $this->ugroup_manager->method('getById')
            ->withConsecutive([555], [556], [557])
            ->willReturnOnConsecutiveCalls($ug01, $ug02, $ug03);
        $this->ugroup_manager->method('getDynamicUGoupIdByName');
        $this->ugroup_manager->method('getDynamicUGoupByName');

        $ug01->expects(self::exactly(3))->method('addUser');
        $ug01->method('getId')->willReturn(555);
        $ug02->expects(self::once())->method('addUser');
        $ug02->method('getId')->willReturn(556);
        $ug03->expects(self::never())->method('addUser');
        $ug03->method('getId')->willReturn(557);

        $this->event_manager->method('processEvent');

        $this->xml_importer->import($this->configuration, 122, $this->xml_file_path_with_ugroups);
    }

    public function testItDoesNotImportsExistingUgroups(): void
    {
        $this->project_manager->method('getValidProjectByShortNameOrId')->willReturn($this->project);
        $this->ugroup_manager->method('getUGroupByName')
            ->withConsecutive(
                [$this->project, 'ug01'],
                [$this->project, 'ug02'],
                [$this->project, 'ug03'],
            )
            ->willReturnOnConsecutiveCalls(
                null,
                \Tuleap\Test\Builders\ProjectUGroupTestBuilder::aCustomUserGroup(556)
                    ->withName('ug02')
                    ->build(),
                null,
            );

        $user_01 = UserTestBuilder::aUser()->withLdapId('ldap_01')->withUserName('user_01')->withId(101)->build();
        $user_02 = UserTestBuilder::aUser()->withLdapId('ldap_02')->withUserName('user_02')->withId(102)->build();
        $user_04 = UserTestBuilder::aUser()->withUserName('user_04')->withId(104)->build();

        $this->user_manager->method('getUserByIdentifier')
            ->withConsecutive(
                ['ldapId:ldap_01'],
                ['ldapId:ldap_02'],
                ['user_04']
            )
            ->willReturnOnConsecutiveCalls($user_01, $user_02, $user_04);

        $this->ugroup_manager->method('createEmptyUgroup')
            ->withConsecutive(
                [122, 'ug01', 'descr01'],
                [122, 'ug03', 'descr03']
            )
            ->willReturnOnConsecutiveCalls(555, 557);
        $ug01 = $this->createMock(\ProjectUGroup::class);
        $ug02 = $this->createMock(\ProjectUGroup::class);
        $ug03 = $this->createMock(\ProjectUGroup::class);

        $this->ugroup_manager->method('getById')
            ->withConsecutive([555], [557], [556])
            ->willReturnOnConsecutiveCalls($ug01, $ug03, $ug02);
        $this->ugroup_manager->method('getDynamicUGoupIdByName');
        $this->ugroup_manager->method('getDynamicUGoupByName');

        $ug01->expects(self::exactly(3))->method('addUser');
        $ug01->method('getId')->willReturn(555);
        $ug02->expects(self::never())->method('addUser');
        $ug02->method('getId')->willReturn(556);
        $ug03->expects(self::never())->method('addUser');
        $ug03->method('getId')->willReturn(557);

        $this->event_manager->method('processEvent');

        $this->xml_importer->import($this->configuration, 122, $this->xml_file_path_with_ugroups);
    }

    public function testItImportsUGroups(): void
    {
        $this->project_manager->method('getValidProjectByShortNameOrId')->willReturn($this->project);

        $user_01 = UserTestBuilder::aUser()->withLdapId('ldap_01')->withUserName('user_01')->withId(101)->build();
        $this->user_manager->method('getUserByIdentifier')->with('ldapId:ldap_01')->willReturn($user_01);

        $this->ugroup_manager->method('getUGroupByName')->with($this->project, 'ug01')->willReturn(null);
        $this->ugroup_manager->method('createEmptyUgroup')->with(122, 'ug01', 'descr01')->willReturn(555);
        $this->ugroup_manager->method('getDynamicUGoupIdByName');
        $this->ugroup_manager->method('getDynamicUGoupByName');

        $ug01 = $this->createMock(\ProjectUGroup::class);
        $this->ugroup_manager->method('getById')->with(555)->willReturn($ug01);
        $ug01->expects(self::once())->method('addUser');
        $ug01->method('getId')->willReturn(555);

        $this->sync_members->expects(self::once())->method('enable')->with($this->project);

        $this->event_manager->method('processEvent');

        $this->xml_importer->import($this->configuration, 122, __DIR__ . '/_fixtures/ProjectXMLImporter/fake_project_with_ugroups_synchronized.xml');
    }

    public function testItDoesNotStopIfUserIsAlreadyProjectMember(): void
    {
        $user = UserTestBuilder::aUser()
            ->withId(101)
            ->withLdapId('ldap_01')
            ->withUserName('user_01')
            ->withMemberOf($this->project)
            ->build();

        $project_member_ugroup = ProjectUGroupTestBuilder::buildProjectMembersWith($user);

        $this->ugroup_manager->method('getDynamicUGoupByName')->with($this->project, 'project_members')->willReturn($project_member_ugroup);
        $this->ugroup_manager->method('getDynamicUGoupIdByName');
        $this->ugroup_manager->method('getUGroupByName')->willReturn($project_member_ugroup);
        $this->project_manager->method('getValidProjectByShortNameOrId')->willReturn($this->project);
        $this->user_manager->method('getUserByIdentifier')->with('ldapId:ldap_01')->willReturn($user);

        $this->event_manager->method('processEvent');

        //No exception must be raised --> nothing to assert
        self::expectNotToPerformAssertions();

        $this->xml_importer->import($this->configuration, 122, $this->xml_file_path_with_members);
    }
}
