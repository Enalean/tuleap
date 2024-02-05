<?php
/**
 * Copyright (c) Enalean, 2012 - Present. All Rights Reserved.
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

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Tuleap\ForgeConfigSandbox;
use Tuleap\TemporaryTestDirectory;

//phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotCamelCaps
class Git_Driver_Gerrit_ProjectCreator_InitiatePermissionsTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;
    use ForgeConfigSandbox;
    use TemporaryTestDirectory;

    protected $contributors = 'tuleap-localhost-mozilla/firefox-contributors';
    protected $integrators  = 'tuleap-localhost-mozilla/firefox-integrators';
    protected $supermen     = 'tuleap-localhost-mozilla/firefox-supermen';
    protected $owners       = 'tuleap-localhost-mozilla/firefox-owners';
    protected $replication  = 'tuleap.example.com-replication';

    protected $contributors_uuid = '8bd90045412f95ff348f41fa63606171f2328db3';
    protected $integrators_uuid  = '19b1241e78c8355c5c3d8a7e856ce3c55f555c22';
    protected $supermen_uuid     = '8a7e856ce3c55f555c228bd90045412f95ff348';
    protected $owners_uuid       = 'f9427648913e6ff14190d81b7b0abc60fa325d3a';
    protected $replication_uuid  = '2ce5c45e3b88415e51ce7e0d3a1ba0526dce6424';

    protected $project_members;
    protected $another_ugroup;
    protected $project_admins;
    protected $project_members_uuid        = '8bd90045412f95ff348f41fa63606171f2328db3';
    protected $another_ugroup_uuid         = '19b1241e78c8355c5c3d8a7e856ce3c55f555c22';
    protected $project_admins_uuid         = '8a7e856ce3c55f555c228bd90045412f95ff348';
    protected $project_members_gerrit_name = 'mozilla/project_members';
    protected $another_ugroup_gerrit_name  = 'mozilla/another_ugroup';
    protected $project_admins_gerrit_name  = 'mozilla/project_admins';

    protected $tmpdir;
    protected $gerrit_tmpdir;
    protected $fixtures;

    /** @var Git_RemoteServer_GerritServer */
    protected $server;

    /** @var Project */
    protected $project;
    protected $project_id        = 103;
    protected $project_unix_name = 'mozilla';

    /** @var UGroupManager */
    protected $ugroup_manager;

    /** @var Git_Driver_Gerrit_MembershipManager */
    protected $membership_manager;

    /** @var ProjectManager */
    protected $project_manager;

    /** @var Git_Driver_Gerrit_UmbrellaProjectManager */
    protected $umbrella_manager;

    protected $gerrit_project = 'tuleap-localhost-mozilla/firefox';
    protected $gerrit_git_url;
    protected $gerrit_admin_instance = 'admin-tuleap.example.com';
    protected $tuleap_instance       = 'tuleap.example.com';
    protected $gitolite_project      = 'gitolite_firefox.git';

    /** @var Git_Driver_Gerrit_Template_TemplateFactory */
    protected $template_factory;

    protected $template_id = 'default';

    protected $template;
    protected $gerrit_driver_factory;

    /** @var Git_Driver_Gerrit_Template_TemplateProcessor */
    protected $template_processor;
    private true $migrate_access_rights;
    /**
     * @var GitRepository&\Mockery\MockInterface
     */
    private $repository;
    /**
     * @var Git_Driver_Gerrit&\Mockery\MockInterface
     */
    private $driver;
    /**
     * @var Git_Driver_Gerrit_UserFinder&\Mockery\MockInterface
     */
    private $userfinder;
    private Git_Exec $git_exec;
    /**
     * @var GitRepository&\Mockery\MockInterface
     */
    private $repository_in_a_private_project;
    private Git_Driver_Gerrit_ProjectCreator $project_creator;
    /**
     * @var GitRepository&\Mockery\MockInterface
     */
    private $repository_without_registered;
    /**
     * @var GitRepository&\Mockery\MockInterface
     */
    private $repository_with_registered;

    private function getGitExec($dir)
    {
        $git_exec = new Git_Exec($dir);
        $git_exec->allowUsageOfFileProtocol();
        return $git_exec;
    }

    protected function setUp(): void
    {
        parent::setUp();

        ForgeConfig::set('sys_default_domain', $this->tuleap_instance);
        ForgeConfig::set('tmp_dir', $this->getTmpDir());
        $this->fixtures = dirname(__FILE__) . '/_fixtures';
        do {
            $this->tmpdir = ForgeConfig::get('tmp_dir') . '/' . bin2hex(random_bytes(16));
        } while (is_dir($this->tmpdir));
        $zip_archive = new ZipArchive();
        $zip_archive->open("$this->fixtures/firefox.zip");
        $zip_archive->extractTo($this->tmpdir);
        shell_exec("tar --no-same-owner -xzf $this->fixtures/gitolite_firefox.git.tgz --directory $this->tmpdir");

        $host         = $this->tmpdir;
        $login        = $this->gerrit_admin_instance;
        $id           = $ssh_port = $http_port = $identity_file = $replication_key = $use_ssl = $gerrit_version = $http_password = 0;
        $this->server = \Mockery::mock(
            \Git_RemoteServer_GerritServer::class,
            [
                $id,
                $host,
                $ssh_port,
                $http_port,
                $login,
                $identity_file,
                $replication_key,
                $use_ssl,
                $gerrit_version,
                $http_password,
                '',
            ]
        )
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();

        $this->gerrit_git_url = "$host/$this->gerrit_project";
        $this->server->shouldReceive('getCloneSSHUrl')->with($this->gerrit_project)->andReturns($this->gerrit_git_url);

        $this->migrate_access_rights = true;
        $this->project               = \Mockery::spy(\Project::class);
        $this->project->shouldReceive('getUnixName')->andReturns($this->project_unix_name);
        $this->project->shouldReceive('isPublic')->andReturns(true);
        $this->project->shouldReceive('getID')->andReturns($this->project_id);
        $private_project = \Mockery::spy(\Project::class)->shouldReceive('isPublic')->andReturns(false)->getMock();

        $this->repository = \Mockery::spy(\GitRepository::class);
        $this->repository->shouldReceive('getFullPath')->andReturns($this->tmpdir . '/' . $this->gitolite_project);
        $this->repository_in_a_private_project = \Mockery::spy(\GitRepository::class);
        $this->repository_in_a_private_project->shouldReceive('getFullPath')->andReturns($this->tmpdir . '/' . $this->gitolite_project);
        $this->repository_without_registered = \Mockery::spy(\GitRepository::class);
        $this->repository_without_registered->shouldReceive('getFullPath')->andReturns($this->tmpdir . '/' . $this->gitolite_project);
        $this->repository_with_registered = \Mockery::spy(\GitRepository::class);
        $this->repository_with_registered->shouldReceive('getFullPath')->andReturns($this->tmpdir . '/' . $this->gitolite_project);

        $this->driver = \Mockery::spy(\Git_Driver_Gerrit::class);
        $this->driver->shouldReceive('createProject')->with($this->server, $this->repository, $this->project_unix_name)->andReturns($this->gerrit_project);
        $this->driver->shouldReceive('createProject')->with($this->server, $this->repository_in_a_private_project, $this->project_unix_name)->andReturns($this->gerrit_project);
        $this->driver->shouldReceive('createProject')->with($this->server, $this->repository_without_registered, $this->project_unix_name)->andReturns($this->gerrit_project);
        $this->driver->shouldReceive('createProject')->with($this->server, $this->repository_with_registered, $this->project_unix_name)->andReturns($this->gerrit_project);
        $this->driver->shouldReceive('createProjectWithPermissionsOnly')->with($this->server, $this->project, $this->project_admins_gerrit_name)->andReturns($this->project_unix_name);
        $this->driver->shouldReceive('doesTheProjectExist')->andReturns(false);
        $this->driver->shouldReceive('getGerritProjectName')->andReturns($this->gerrit_project);

        $this->gerrit_driver_factory = \Mockery::spy(\Git_Driver_Gerrit_GerritDriverFactory::class)->shouldReceive('getDriver')->andReturns($this->driver)->getMock();

        $this->membership_manager = \Mockery::spy(\Git_Driver_Gerrit_MembershipManager::class);
        $this->membership_manager->shouldReceive('getGroupUUIDByNameOnServer')->with($this->server, $this->contributors)->andReturns($this->contributors_uuid);
        $this->membership_manager->shouldReceive('getGroupUUIDByNameOnServer')->with($this->server, $this->integrators)->andReturns($this->integrators_uuid);
        $this->membership_manager->shouldReceive('getGroupUUIDByNameOnServer')->with($this->server, $this->supermen)->andReturns($this->supermen_uuid);
        $this->membership_manager->shouldReceive('getGroupUUIDByNameOnServer')->with($this->server, $this->owners)->andReturns($this->owners_uuid);
        $this->membership_manager->shouldReceive('getGroupUUIDByNameOnServer')->with($this->server, $this->replication)->andReturns($this->replication_uuid);

        $this->userfinder     = \Mockery::spy(\Git_Driver_Gerrit_UserFinder::class);
        $this->ugroup_manager = \Mockery::spy(\UGroupManager::class);

        $this->project_manager = \Mockery::spy(\ProjectManager::class);

        $this->umbrella_manager = \Mockery::spy(\Git_Driver_Gerrit_UmbrellaProjectManager::class);

        $this->template           = \Mockery::spy(\Git_Driver_Gerrit_Template_Template::class)->shouldReceive('getId')->andReturns(12)->getMock();
        $this->template_processor = new Git_Driver_Gerrit_Template_TemplateProcessor();
        $this->template_factory   = \Mockery::spy(\Git_Driver_Gerrit_Template_TemplateFactory::class)->shouldReceive('getTemplate')->with(12)->andReturns($this->template)->getMock();
        $this->template_factory->shouldReceive('getTemplatesAvailableForRepository')->andReturns([$this->template]);

        $this->gerrit_tmpdir = $this->tmpdir . '/gerrit_tbd';

        $this->git_exec = $this->getGitExec($this->gerrit_tmpdir);

        $this->project_creator = new Git_Driver_Gerrit_ProjectCreator(
            $this->gerrit_tmpdir,
            $this->gerrit_driver_factory,
            $this->userfinder,
            $this->ugroup_manager,
            $this->membership_manager,
            $this->umbrella_manager,
            $this->template_factory,
            $this->template_processor,
            $this->git_exec
        );

        $this->repository->shouldReceive('getProject')->andReturns($this->project);
        $this->repository_in_a_private_project->shouldReceive('getProject')->andReturns($private_project);
        $this->repository_without_registered->shouldReceive('getProject')->andReturns($this->project);
        $this->repository_with_registered->shouldReceive('getProject')->andReturns($this->project);

        $this->userfinder->shouldReceive('areRegisteredUsersAllowedTo')->with(Git::PERM_READ, $this->repository)->andReturns(true);
        $this->userfinder->shouldReceive('areRegisteredUsersAllowedTo')->with(Git::PERM_READ, $this->repository_in_a_private_project)->andReturns(true);
        $this->userfinder->shouldReceive('areRegisteredUsersAllowedTo')->with(Git::PERM_READ, $this->repository_without_registered)->andReturns(false);
        $this->userfinder->shouldReceive('areRegisteredUsersAllowedTo')->with(Git::PERM_READ, $this->repository_with_registered)->andReturns(true);
        $this->userfinder->shouldReceive('areRegisteredUsersAllowedTo')->with(Git::PERM_WRITE, $this->repository_with_registered)->andReturns(true);
        $this->userfinder->shouldReceive('areRegisteredUsersAllowedTo')->with(Git::PERM_WPLUS, $this->repository_with_registered)->andReturns(true);

        $this->project_members = \Mockery::spy(\ProjectUGroup::class);
        $this->project_members->shouldReceive('getNormalizedName')->andReturns('project_members');
        $this->project_members->shouldReceive('getId')->andReturns(ProjectUGroup::PROJECT_MEMBERS);

        $this->another_ugroup = \Mockery::spy(\ProjectUGroup::class);
        $this->another_ugroup->shouldReceive('getNormalizedName')->andReturns('another_ugroup');
        $this->another_ugroup->shouldReceive('getId')->andReturns(120);

        $this->project_admins = \Mockery::spy(\ProjectUGroup::class);
        $this->project_admins->shouldReceive('getNormalizedName')->andReturns('project_admins');
        $this->project_admins->shouldReceive('getId')->andReturns(ProjectUGroup::PROJECT_ADMIN);

        $this->ugroup_manager->shouldReceive('getUGroups')->andReturns([$this->project_members, $this->another_ugroup, $this->project_admins]);

        $this->membership_manager->shouldReceive('getGroupUUIDByNameOnServer')->with($this->server, $this->project_members_gerrit_name)->andReturns($this->project_members_uuid);
        $this->membership_manager->shouldReceive('getGroupUUIDByNameOnServer')->with($this->server, $this->another_ugroup_gerrit_name)->andReturns($this->another_ugroup_uuid);
        $this->membership_manager->shouldReceive('getGroupUUIDByNameOnServer')->with($this->server, $this->project_admins_gerrit_name)->andReturns($this->project_admins_uuid);

        $this->membership_manager->shouldReceive('createArrayOfGroupsForServer')->andReturns([$this->project_members, $this->another_ugroup, $this->project_admins]);
    }

    protected function tearDown(): void
    {
        $this->recurseDeleteInDir($this->tmpdir);
        rmdir($this->tmpdir);
        parent::tearDown();
    }

    public function testItPushesTheUpdatedConfigToTheServer(): void
    {
        $this->userfinder->shouldReceive('getUgroups')->with($this->repository->getId(), Git::PERM_READ)->andReturns([ProjectUGroup::REGISTERED]);
        $this->userfinder->shouldReceive('getUgroups')->with($this->repository->getId(), Git::PERM_WRITE)->andReturns([ProjectUGroup::PROJECT_MEMBERS, 120]);
        $this->userfinder->shouldReceive('getUgroups')->with($this->repository->getId(), Git::PERM_WPLUS)->andReturns([ProjectUGroup::PROJECT_ADMIN]);

        $this->project_creator->createGerritProject($this->server, $this->repository, $this->template_id);
        $this->project_creator->finalizeGerritProjectCreation($this->server, $this->repository, $this->template_id);

        $this->assertItClonesTheDistantRepo();
        $this->assertCommitterIsConfigured();
        $this->assertTheRemoteOriginIsConfigured();
        $this->assertGroupsFileHasEverything();
        $this->assertPermissionsFileHasEverything();
        $this->assertEverythingIsCommitted();
        $this->assertEverythingIsPushedToTheServer();
    }

    public function testItThrowsAnExceptionIfProjectAlreadyExists(): void
    {
        $this->userfinder->shouldReceive('getUgroups')->with($this->repository->getId(), Git::PERM_READ)->andReturns([ProjectUGroup::REGISTERED]);
        $this->userfinder->shouldReceive('getUgroups')->with($this->repository->getId(), Git::PERM_WRITE)->andReturns([ProjectUGroup::PROJECT_MEMBERS, 120]);
        $this->userfinder->shouldReceive('getUgroups')->with($this->repository->getId(), Git::PERM_WPLUS)->andReturns([ProjectUGroup::PROJECT_ADMIN]);

        $driver = \Mockery::spy(\Git_Driver_Gerrit::class);
        $driver->shouldReceive('doesTheProjectExist')->andReturns(true);
        $gerrit_driver_factory = \Mockery::spy(\Git_Driver_Gerrit_GerritDriverFactory::class)->shouldReceive('getDriver')->andReturns($driver)->getMock();

        $project_creator = new Git_Driver_Gerrit_ProjectCreator(
            $this->gerrit_tmpdir,
            $gerrit_driver_factory,
            $this->userfinder,
            $this->ugroup_manager,
            $this->membership_manager,
            $this->umbrella_manager,
            $this->template_factory,
            $this->template_processor,
            $this->getGitExec($this->gerrit_tmpdir)
        );
        $this->expectException(\Git_Driver_Gerrit_ProjectCreator_ProjectAlreadyExistsException::class);

        $project_creator->createGerritProject($this->server, $this->repository, $this->migrate_access_rights);
        $project_creator->finalizeGerritProjectCreation($this->server, $this->repository, $this->template_id);
    }

    public function testItDoesNotSetPermsIfMigrateAccessRightIsFalse(): void
    {
        $project_creator = Mockery::mock(
            Git_Driver_Gerrit_ProjectCreator::class,
            [
                $this->gerrit_tmpdir,
                $this->gerrit_driver_factory,
                $this->userfinder,
                $this->ugroup_manager,
                $this->membership_manager,
                $this->umbrella_manager,
                $this->template_factory,
                $this->template_processor,
                $this->getGitExec($this->gerrit_tmpdir),
            ]
        )
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();

        $project_creator->shouldReceive('exportGitBranches');

        $project_creator->createGerritProject($this->server, $this->repository_in_a_private_project, false);

        $this->assertFileExists("$this->gerrit_tmpdir/project.config");
        $this->assertDoesNotMatchRegularExpression('/group mozilla\//', file_get_contents("$this->gerrit_tmpdir/project.config"));
        $this->assertMatchesRegularExpression('/group Administrators/', file_get_contents("$this->gerrit_tmpdir/project.config"));
    }

    public function testItDoesNotSetPermsOnRegisteredUsersIfProjectIsPrivate(): void
    {
        $project_creator = Mockery::mock(
            Git_Driver_Gerrit_ProjectCreator::class,
            [
                $this->gerrit_tmpdir,
                $this->gerrit_driver_factory,
                $this->userfinder,
                $this->ugroup_manager,
                $this->membership_manager,
                $this->umbrella_manager,
                $this->template_factory,
                $this->template_processor,
                $this->getGitExec($this->gerrit_tmpdir),
            ]
        )
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();

        $project_creator->shouldReceive('exportGitBranches');

        $this->userfinder->shouldReceive('getUgroups')->with($this->repository->getId(), Git::PERM_READ)->andReturns([ProjectUGroup::REGISTERED]);
        $this->userfinder->shouldReceive('getUgroups')->with($this->repository->getId(), Git::PERM_WRITE)->andReturns([]);
        $this->userfinder->shouldReceive('getUgroups')->with($this->repository->getId(), Git::PERM_WPLUS)->andReturns([]);

        $project_creator->createGerritProject($this->server, $this->repository_in_a_private_project, $this->migrate_access_rights);
        $project_creator->finalizeGerritProjectCreation($this->server, $this->repository, $this->template_id);

        $this->assertDoesNotMatchRegularExpression('/Registered Users/', file_get_contents("$this->gerrit_tmpdir/project.config"));
    }

    public function testItDoesNotSetPermsOnRegisteredUsersIfRepoHasNoPermsForRegisteredOrAnonymous(): void
    {
        $groups = [
            ProjectUGroup::REGISTERED,
            ProjectUGroup::ANONYMOUS,
        ];
        $this->userfinder->shouldReceive('getUgroups')->with($this->repository->getId(), Git::PERM_READ)->andReturns($groups);
        $this->userfinder->shouldReceive('getUgroups')->with($this->repository->getId(), Git::PERM_WRITE)->andReturns($groups);
        $this->userfinder->shouldReceive('getUgroups')->with($this->repository->getId(), Git::PERM_WPLUS)->andReturns($groups);

        $this->project_creator->createGerritProject($this->server, $this->repository_without_registered, $this->migrate_access_rights);
        $this->project_creator->finalizeGerritProjectCreation($this->server, $this->repository, $this->template_id);

        $this->assertDoesNotMatchRegularExpression('/Registered Users/', file_get_contents("$this->gerrit_tmpdir/project.config"));
    }

    public function testItSetsPermsLabelCodeReviewOnceIfUserCanReadANdWrite(): void
    {
        $this->userfinder->shouldReceive('getUgroups')->with($this->repository->getId(), Git::PERM_READ)->andReturns([ProjectUGroup::PROJECT_MEMBERS]);
        $this->userfinder->shouldReceive('getUgroups')->with($this->repository->getId(), Git::PERM_WRITE)->andReturns([ProjectUGroup::PROJECT_MEMBERS]);
        $this->userfinder->shouldReceive('getUgroups')->with($this->repository->getId(), Git::PERM_WPLUS)->andReturns([]);

        $this->project_creator->createGerritProject($this->server, $this->repository_with_registered, $this->migrate_access_rights);
        $this->project_creator->finalizeGerritProjectCreation($this->server, $this->repository, $this->template_id);

        $this->assertDoesNotMatchRegularExpression('/label-Code-Review = -1..+1/', file_get_contents("$this->gerrit_tmpdir/project.config"));
        $this->assertMatchesRegularExpression('/label-Code-Review = -2..+2/', file_get_contents("$this->gerrit_tmpdir/project.config"));
    }

    public function testItSetsPermsOnRegisteredUsersIfRepoHasReadForRegistered()
    {
        $this->userfinder->shouldReceive('getUgroups')->with($this->repository->getId(), Git::PERM_READ)->andReturns([ProjectUGroup::REGISTERED]);
        $this->userfinder->shouldReceive('getUgroups')->with($this->repository->getId(), Git::PERM_WRITE)->andReturns([]);
        $this->userfinder->shouldReceive('getUgroups')->with($this->repository->getId(), Git::PERM_WPLUS)->andReturns([]);

        $this->project_creator->createGerritProject($this->server, $this->repository_with_registered, $this->migrate_access_rights);
        $this->project_creator->finalizeGerritProjectCreation($this->server, $this->repository, $this->template_id);

        $this->assertMatchesRegularExpression('/Registered Users/', file_get_contents("$this->gerrit_tmpdir/project.config"));
    }

    public function testItSetsPermsOnRegisteredUsersIfRepoHasWriteForRegistered()
    {
        $this->userfinder->shouldReceive('getUgroups')->with($this->repository->getId(), Git::PERM_READ)->andReturns([]);
        $this->userfinder->shouldReceive('getUgroups')->with($this->repository->getId(), Git::PERM_WRITE)->andReturns([ProjectUGroup::REGISTERED]);
        $this->userfinder->shouldReceive('getUgroups')->with($this->repository->getId(), Git::PERM_WPLUS)->andReturns([]);

        $this->project_creator->createGerritProject($this->server, $this->repository_with_registered, $this->migrate_access_rights);
        $this->project_creator->finalizeGerritProjectCreation($this->server, $this->repository, $this->template_id);

        $this->assertMatchesRegularExpression('/Registered Users/', file_get_contents("$this->gerrit_tmpdir/project.config"));
    }

    public function testItSetsPermsOnRegisteredUsersIfRepoHasExecuteForRegistered()
    {
        $this->userfinder->shouldReceive('getUgroups')->with($this->repository->getId(), Git::PERM_READ)->andReturns([]);
        $this->userfinder->shouldReceive('getUgroups')->with($this->repository->getId(), Git::PERM_WRITE)->andReturns([]);
        $this->userfinder->shouldReceive('getUgroups')->with($this->repository->getId(), Git::PERM_WPLUS)->andReturns([ProjectUGroup::REGISTERED]);

        $this->project_creator->createGerritProject($this->server, $this->repository_with_registered, $this->migrate_access_rights);
        $this->project_creator->finalizeGerritProjectCreation($this->server, $this->repository, $this->template_id);

        $this->assertMatchesRegularExpression('/Registered Users/', file_get_contents("$this->gerrit_tmpdir/project.config"));
    }

    public function isSetsPermsOnRegisteredUsersIfRepoHasReadForAuthenticated()
    {
        $this->userfinder->shouldReceive('getUgroups')->with($this->repository->getId(), Git::PERM_READ)->andReturns([ProjectUGroup::AUTHENTICATED]);
        $this->userfinder->shouldReceive('getUgroups')->with($this->repository->getId(), Git::PERM_WRITE)->andReturns([]);
        $this->userfinder->shouldReceive('getUgroups')->with($this->repository->getId(), Git::PERM_WPLUS)->andReturns([]);

        $this->project_creator->createGerritProject($this->server, $this->repository_with_registered, $this->migrate_access_rights);
        $this->project_creator->finalizeGerritProjectCreation($this->server, $this->repository, $this->template_id);

        $this->assertMatchesRegularExpression('/Registered Users/', file_get_contents("$this->gerrit_tmpdir/project.config"));
    }

    public function testItSetsPermsOnRegisteredUsersIfRepoHasWriteForAuthenticated()
    {
        $this->userfinder->shouldReceive('getUgroups')->with($this->repository->getId(), Git::PERM_READ)->andReturns([]);
        $this->userfinder->shouldReceive('getUgroups')->with($this->repository->getId(), Git::PERM_WRITE)->andReturns([ProjectUGroup::AUTHENTICATED]);
        $this->userfinder->shouldReceive('getUgroups')->with($this->repository->getId(), Git::PERM_WPLUS)->andReturns([]);

        $this->project_creator->createGerritProject($this->server, $this->repository_with_registered, $this->migrate_access_rights);
        $this->project_creator->finalizeGerritProjectCreation($this->server, $this->repository, $this->template_id);

        $this->assertMatchesRegularExpression('/Registered Users/', file_get_contents("$this->gerrit_tmpdir/project.config"));
    }

    public function testItSetsPermsOnRegisteredUsersIfRepoHasExecuteForAuthenticated()
    {
        $this->userfinder->shouldReceive('getUgroups')->with($this->repository->getId(), Git::PERM_READ)->andReturns([]);
        $this->userfinder->shouldReceive('getUgroups')->with($this->repository->getId(), Git::PERM_WRITE)->andReturns([]);
        $this->userfinder->shouldReceive('getUgroups')->with($this->repository->getId(), Git::PERM_WPLUS)->andReturns([ProjectUGroup::AUTHENTICATED]);

        $this->project_creator->createGerritProject($this->server, $this->repository_with_registered, $this->migrate_access_rights);
        $this->project_creator->finalizeGerritProjectCreation($this->server, $this->repository, $this->template_id);

        $this->assertMatchesRegularExpression('/Registered Users/', file_get_contents("$this->gerrit_tmpdir/project.config"));
    }

    public function testItSetsPermsOnRegisteredUsersIfRepoHasReadForAnonymous()
    {
        $this->userfinder->shouldReceive('getUgroups')->with($this->repository->getId(), Git::PERM_READ)->andReturns([ProjectUGroup::ANONYMOUS]);
        $this->userfinder->shouldReceive('getUgroups')->with($this->repository->getId(), Git::PERM_WRITE)->andReturns([]);
        $this->userfinder->shouldReceive('getUgroups')->with($this->repository->getId(), Git::PERM_WPLUS)->andReturns([]);

        $this->project_creator->createGerritProject($this->server, $this->repository_with_registered, $this->migrate_access_rights);
        $this->project_creator->finalizeGerritProjectCreation($this->server, $this->repository, $this->template_id);

        $this->assertMatchesRegularExpression('/Registered Users/', file_get_contents("$this->gerrit_tmpdir/project.config"));
    }

    public function testItSetsPermsOnRegisteredUsersIfRepoHasWriteForAnonymous()
    {
        $this->userfinder->shouldReceive('getUgroups')->with($this->repository->getId(), Git::PERM_READ)->andReturns([]);
        $this->userfinder->shouldReceive('getUgroups')->with($this->repository->getId(), Git::PERM_WRITE)->andReturns([ProjectUGroup::ANONYMOUS]);
        $this->userfinder->shouldReceive('getUgroups')->with($this->repository->getId(), Git::PERM_WPLUS)->andReturns([]);

        $this->project_creator->createGerritProject($this->server, $this->repository_with_registered, $this->migrate_access_rights);
        $this->project_creator->finalizeGerritProjectCreation($this->server, $this->repository, $this->template_id);

        $this->assertMatchesRegularExpression('/Registered Users/', file_get_contents("$this->gerrit_tmpdir/project.config"));
    }

    public function testItSetsPermsOnRegisteredUsersIfRepoHasExecuteForAnonymous()
    {
        $this->userfinder->shouldReceive('getUgroups')->with($this->repository->getId(), Git::PERM_READ)->andReturns([]);
        $this->userfinder->shouldReceive('getUgroups')->with($this->repository->getId(), Git::PERM_WRITE)->andReturns([]);
        $this->userfinder->shouldReceive('getUgroups')->with($this->repository->getId(), Git::PERM_WPLUS)->andReturns([ProjectUGroup::ANONYMOUS]);

        $this->project_creator->createGerritProject($this->server, $this->repository_with_registered, $this->migrate_access_rights);
        $this->project_creator->finalizeGerritProjectCreation($this->server, $this->repository, $this->template_id);

        $this->assertMatchesRegularExpression('/Registered Users/', file_get_contents("$this->gerrit_tmpdir/project.config"));
    }

    private function assertItClonesTheDistantRepo(): void
    {
        $groups_file = "$this->gerrit_tmpdir/groups";
        $config_file = "$this->gerrit_tmpdir/project.config";

        $this->assertTrue(is_file($groups_file));
        $this->assertTrue(is_file($config_file));
    }

    private function assertCommitterIsConfigured(): void
    {
        $this->assertEquals(trim(shell_exec("cd $this->gerrit_tmpdir; " . Git_Exec::getGitCommand() . " config --get user.name")), $this->gerrit_admin_instance);
        $this->assertEquals(trim(shell_exec("cd $this->gerrit_tmpdir; " . Git_Exec::getGitCommand() . " config --get user.email")), 'codendiadm@' . $this->tuleap_instance);
    }

    private function assertTheRemoteOriginIsConfigured(): void
    {
        $cwd = getcwd();
        chdir("$this->gerrit_tmpdir");
        exec(Git_Exec::getGitCommand() . ' remote -v', $output, $ret_val);
        chdir($cwd);

        $this->assertEquals(
            [
                "origin\t$this->gerrit_git_url (fetch)",
                "origin\t$this->gerrit_git_url (push)",
            ],
            $output
        );
        $this->assertEquals(0, $ret_val);
    }

    private function assertEverythingIsPushedToTheServer(): void
    {
        $cwd = getcwd();
        chdir("$this->gerrit_tmpdir");
        exec(Git_Exec::getGitCommand() . ' push origin HEAD:refs/meta/config --porcelain', $output, $ret_val);
        chdir($cwd);
        $this->assertEquals(
            [
                "To $this->gerrit_git_url",
                "=\tHEAD:refs/meta/config\t[up to date]",
                "Done",
            ],
            $output
        );
        $this->assertEquals(0, $ret_val);
    }

    private function assertEverythingIsCommitted(): void
    {
        $cwd = getcwd();
        chdir("$this->gerrit_tmpdir");
        exec(Git_Exec::getGitCommand() . ' status --porcelain', $output, $ret_val);
        chdir($cwd);
        $this->assertEquals([], $output);
        $this->assertEquals(0, $ret_val);
    }

    private function assertPermissionsFileHasEverything(): void
    {
        $config_file_contents = file_get_contents("$this->gerrit_tmpdir/project.config");

        $expected_contents = file_get_contents("$this->fixtures/expected_access_rights.config");

        $this->assertEquals($expected_contents, $config_file_contents);
    }

    private function assertGroupsFileHasEverything(): void
    {
        $groups_file         = "$this->gerrit_tmpdir/groups";
        $group_file_contents = file_get_contents($groups_file);

        $this->assertMatchesRegularExpression("%$this->project_members_uuid\t$this->project_members_gerrit_name\n%", $group_file_contents);
        $this->assertMatchesRegularExpression("%$this->another_ugroup_uuid\t$this->another_ugroup_gerrit_name\n%", $group_file_contents);
        $this->assertMatchesRegularExpression("%$this->replication_uuid\t$this->replication\n%", $group_file_contents);
        $this->assertMatchesRegularExpression("%global:Registered-Users\tRegistered Users\n%", $group_file_contents);
    }
}
