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

use Mockery as M;
use PHPUnit\Framework\TestCase;
use Tuleap\Project\Admin\Service\ProjectServiceActivator;
use Tuleap\Project\DefaultProjectVisibilityRetriever;
use Tuleap\Project\UGroups\Membership\DynamicUGroups\ProjectMemberAdder;
use Tuleap\Project\XML\XMLFileContentRetriever;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\XML\MappingsRegistry;
use Tuleap\Project\XML\Import;

class ProjectXMLImporterTest extends TestCase
{
    use M\Adapter\Phpunit\MockeryPHPUnitIntegration;

    private $event_manager;
    /**
     * @var M\MockInterface
     */
    private $project_manager;
    private $project;
    /**
     * @var M\MockInterface
     */
    private $ugroup_manager;
    private $xml_file_path = __DIR__ . '/_fixtures/ProjectXMLImporter/fake_project.xml';
    private $xml_file_path_with_ugroups = __DIR__ . '/_fixtures/ProjectXMLImporter/fake_project_with_ugroups.xml';
    /**
     * @var M\MockInterface
     */
    private $user_manager;
    private $logger;

    private $configuration;
    /** @var ProjectXMLImporter */
    private $xml_importer;
    /**
     * @var XMLImportHelper
     */
    private $user_finder;
    /**
     * @var a|\Mockery\MockInterface|Tuleap\Project\UgroupDuplicator
     */
    private $ugroup_duplicator;
    /**
     * @var string
     */
    private $xml_file_path_with_members = __DIR__ . '/_fixtures/ProjectXMLImporter/fake_project_with_project_members.xml';
    /**
     * @var M\MockInterface|\Tuleap\Project\UGroups\SynchronizedProjectMembershipDao
     */
    private $sync_members;

    protected function setUp() : void
    {
        $this->event_manager     = M::spy(EventManager::class);
        $this->project_manager   = M::spy(\ProjectManager::class);
        $this->project           = M::spy(\Project::class, ['getID' => 122]);
        $this->ugroup_manager    = M::spy(\UGroupManager::class);
        $this->user_manager      = M::spy(\UserManager::class, ['getCurrentUser' => M::spy(\PFUser::class)]);
        $this->user_finder       = new XMLImportHelper($this->user_manager);
        $this->logger            = M::spy(\Psr\Log\LoggerInterface::class);
        $this->ugroup_duplicator = M::spy(\Tuleap\Project\UgroupDuplicator::class);
        $this->sync_members      = M::mock(\Tuleap\Project\UGroups\SynchronizedProjectMembershipDao::class);
        $frs_permissions_creator = M::spy(\Tuleap\FRS\FRSPermissionCreator::class);
        $user_removal            = M::spy(\Tuleap\Project\UserRemover::class);

        $project_creator = new ProjectCreator(
            $this->project_manager,
            M::spy(\ReferenceManager::class),
            $this->user_manager,
            $this->ugroup_duplicator,
            false,
            $frs_permissions_creator,
            M::spy(\Tuleap\FRS\LicenseAgreement\LicenseAgreementFactory::class),
            M::spy(\Tuleap\Dashboard\Project\ProjectDashboardDuplicator::class),
            M::spy(\Tuleap\Project\Label\LabelDao::class),
            new DefaultProjectVisibilityRetriever(),
            M::spy(\Tuleap\Project\UGroups\SynchronizedProjectMembershipDuplicator::class),
            new \Rule_ProjectName(),
            new \Rule_ProjectFullName(),
            Mockery::mock(EventManager::class),
            Mockery::mock(\Tuleap\Project\Admin\DescriptionFields\FieldUpdator::class),
            Mockery::mock(ProjectServiceActivator::class),
            false
        );

        $this->xml_importer = new ProjectXMLImporter(
            $this->event_manager,
            $this->project_manager,
            $this->user_manager,
            new XML_RNGValidator(),
            $this->ugroup_manager,
            $this->user_finder,
            M::spy(\ServiceManager::class),
            $this->logger,
            $frs_permissions_creator,
            $user_removal,
            M::mock(ProjectMemberAdder::class),
            $project_creator,
            M::spy(\Tuleap\FRS\UploadedLinksUpdater::class),
            M::spy(\Tuleap\Dashboard\Project\ProjectDashboardXMLImporter::class),
            $this->sync_members,
            new XMLFileContentRetriever()
        );

        $this->configuration = new Import\ImportConfig();
    }

    public function testItAsksToPluginToImportInformationsFromTheGivenXml()
    {
        $this->project_manager->shouldReceive('getValidProjectByShortNameOrId')->andReturns($this->project);

        $this->event_manager->shouldReceive('processEvent')->once()->with(Event::IMPORT_XML_PROJECT, M::on(function ($args) {
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

    public function testItAsksProjectManagerForTheProject()
    {
        $this->project_manager->shouldReceive('getValidProjectByShortNameOrId')->with(122)->once()->andReturns($this->project);

        $this->xml_importer->import($this->configuration, 122, $this->xml_file_path);
    }

    public function testItStopsIfNoProjectIsFound()
    {
        $this->project_manager->shouldReceive('getValidProjectByShortNameOrId')->andThrow(new Project_NotFoundException());
        $this->expectException('Project_NotFoundException');

        $this->xml_importer->import($this->configuration, 122, $this->xml_file_path);
    }

    public function testItImportsProjectDataWithUgroups()
    {
        $this->project_manager->shouldReceive('getValidProjectByShortNameOrId')->andReturns($this->project);
        $this->ugroup_manager->shouldReceive('getUGroupByName')->andReturns(false);

        $user_01 = UserTestBuilder::aUser()->withLdapId('ldap_01')->withUserName('user_01')->withId(101)->build();
        $user_02 = UserTestBuilder::aUser()->withLdapId('ldap_02')->withUserName('user_02')->withId(102)->build();
        $user_03 = UserTestBuilder::aUser()->withLdapId('ldap_03')->withUserName('user_03')->withId(103)->build();
        $user_04 = UserTestBuilder::aUser()->withUserName('user_04')->withId(104)->build();

        $this->user_manager->shouldReceive('getUserByIdentifier')->with('ldapId:ldap_01')->andReturns($user_01);
        $this->user_manager->shouldReceive('getUserByIdentifier')->with('ldapId:ldap_02')->andReturns($user_02);
        $this->user_manager->shouldReceive('getUserByIdentifier')->with('ldapId:ldap_03')->andReturns($user_03);
        $this->user_manager->shouldReceive('getUserByIdentifier')->with('user_04')->andReturns($user_04);

        $this->ugroup_manager->shouldReceive('createEmptyUgroup')->with(122, 'ug01', 'descr01')->andReturns(555)->ordered();
        $this->ugroup_manager->shouldReceive('createEmptyUgroup')->with(122, 'ug02', 'descr02')->andReturns(556)->ordered();
        $this->ugroup_manager->shouldReceive('createEmptyUgroup')->with(122, 'ug03', 'descr03')->andReturns(557)->ordered();

        $ug01 = M::spy(\ProjectUGroup::class);
        $ug02 = M::spy(\ProjectUGroup::class);
        $ug03 = M::spy(\ProjectUGroup::class);

        $this->ugroup_manager->shouldReceive('getById')->with(555)->andReturns($ug01);
        $this->ugroup_manager->shouldReceive('getById')->with(556)->andReturns($ug02);
        $this->ugroup_manager->shouldReceive('getById')->with(557)->andReturns($ug03);

        $ug01->shouldReceive('addUser')->times(3);
        $ug02->shouldReceive('addUser')->once();
        $ug03->shouldReceive('addUser')->never();

        $this->xml_importer->import($this->configuration, 122, $this->xml_file_path_with_ugroups);
    }

    public function testItDoesNotImportsExistingUgroups()
    {
        $this->project_manager->shouldReceive('getValidProjectByShortNameOrId')->andReturns($this->project);
        $this->ugroup_manager->shouldReceive('getUGroupByName')->with($this->project, 'ug01')->andReturns(false);
        $this->ugroup_manager->shouldReceive('getUGroupByName')->with($this->project, 'ug02')->andReturns(true);
        $this->ugroup_manager->shouldReceive('getUGroupByName')->with($this->project, 'ug03')->andReturns(false);

        $user_01 = UserTestBuilder::aUser()->withLdapId('ldap_01')->withUserName('user_01')->withId(101)->build();
        $user_02 = UserTestBuilder::aUser()->withLdapId('ldap_02')->withUserName('user_02')->withId(102)->build();
        $user_04 = UserTestBuilder::aUser()->withUserName('user_04')->withId(104)->build();

        $this->user_manager->shouldReceive('getUserByIdentifier')->with('ldapId:ldap_01')->andReturns($user_01);
        $this->user_manager->shouldReceive('getUserByIdentifier')->with('ldapId:ldap_02')->andReturns($user_02);
        $this->user_manager->shouldReceive('getUserByIdentifier')->with('user_04')->andReturns($user_04);

        $this->ugroup_manager->shouldReceive('createEmptyUgroup')->with(122, 'ug01', 'descr01')->andReturns(555)->ordered();
        $this->ugroup_manager->shouldReceive('createEmptyUgroup')->with(122, 'ug03', 'descr03')->andReturns(557)->ordered();
        $ug01 = M::spy(\ProjectUGroup::class);
        $ug02 = M::spy(\ProjectUGroup::class);
        $ug03 = M::spy(\ProjectUGroup::class);

        $this->ugroup_manager->shouldReceive('getById')->with(555)->andReturns($ug01);
        $this->ugroup_manager->shouldReceive('getById')->with(556)->andReturns($ug02);
        $this->ugroup_manager->shouldReceive('getById')->with(557)->andReturns($ug03);

        $ug01->shouldReceive('addUser')->times(3);
        $ug02->shouldReceive('addUser')->never();
        $ug03->shouldReceive('addUser')->never();

        $this->xml_importer->import($this->configuration, 122, $this->xml_file_path_with_ugroups);
    }

    public function testItImportsUGroups()
    {
        $this->project_manager->shouldReceive('getValidProjectByShortNameOrId')->andReturns($this->project);

        $user_01 = UserTestBuilder::aUser()->withLdapId('ldap_01')->withUserName('user_01')->withId(101)->build();
        $this->user_manager->shouldReceive('getUserByIdentifier')->with('ldapId:ldap_01')->andReturns($user_01);

        $this->ugroup_manager->shouldReceive('getUGroupByName')->with($this->project, 'ug01')->andReturns(false);
        $this->ugroup_manager->shouldReceive('createEmptyUgroup')->with(122, 'ug01', 'descr01')->andReturns(555);

        $ug01 = M::spy(\ProjectUGroup::class);
        $this->ugroup_manager->shouldReceive('getById')->with(555)->andReturns($ug01);
        $ug01->shouldReceive('addUser')->once();

        $this->sync_members->shouldReceive('enable')->with($this->project)->once();

        $this->xml_importer->import($this->configuration, 122, __DIR__ . '/_fixtures/ProjectXMLImporter/fake_project_with_ugroups_synchronized.xml');
    }

    public function testItDoesNotStopIfUserIsAlreadyProjectMember()
    {
        $user = M::spy(
            \PFUser::class,
            [
                'getId' => 101,
                'getLdapId' => 'ldap_01',
                'getUserName' => 'user_01',
                'isMember' => true,
            ]
        );

        $project_member_ugroup = M::spy(\ProjectUGroup::class);

        $this->ugroup_manager->shouldReceive('getDynamicUGoupByName')->with($this->project, 'project_members')->andReturns($project_member_ugroup);
        $this->project_manager->shouldReceive('getValidProjectByShortNameOrId')->andReturns($this->project);
        $this->user_manager->shouldReceive('getUserByIdentifier')->with('ldapId:ldap_01')->andReturns($user);

        //No exception must be raised --> nothing to assert

        $this->xml_importer->import($this->configuration, 122, $this->xml_file_path_with_members);
    }
}
