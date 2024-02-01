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

require_once __DIR__ . '/../../../bootstrap.php';

//phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
final class ProjectCreatorCallToGerritTest extends \Tuleap\Test\PHPUnit\TestCase
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
    private $repository;
    private $repository_in_a_private_project;
    private $repository_without_registered;
    private $repository_with_registered;
    private $driver;
    private $userfinder;
    private Git_Driver_Gerrit_ProjectCreator $project_creator;

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

        $this->project_creator = new Git_Driver_Gerrit_ProjectCreator(
            $this->gerrit_tmpdir,
            $this->gerrit_driver_factory,
            $this->userfinder,
            $this->ugroup_manager,
            $this->membership_manager,
            $this->umbrella_manager,
            $this->template_factory,
            $this->template_processor,
            $this->getGitExec($this->gerrit_tmpdir)
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

        $this->userfinder->shouldReceive('getUgroups')->andReturns([]);
    }

    private function getGitExec($dir): Git_Exec
    {
        $git_exec = new Git_Exec($dir);
        $git_exec->allowUsageOfFileProtocol();
        return $git_exec;
    }

    protected function tearDown(): void
    {
        $this->recurseDeleteInDir($this->tmpdir);
        rmdir($this->tmpdir);
        parent::tearDown();
    }

    public function testItCreatesAProjectAndExportGitBranchesAndTagsWithoutCreateParentProject(): void
    {
        //ssh gerrit gerrit create tuleap.net-Firefox/all/mobile

        $this->project_admins = \Mockery::spy(\ProjectUGroup::class);
        $this->project_admins->shouldReceive('getNormalizedName')->andReturns('project_admins');
        $this->project_admins->shouldReceive('getId')->andReturns(ProjectUGroup::PROJECT_ADMIN);

        $this->ugroup_manager->shouldReceive('getUGroups')->andReturns([$this->project_admins]);
        $this->driver->shouldReceive('doesTheParentProjectExist')->andReturns(true);

        $this->membership_manager->shouldReceive('createArrayOfGroupsForServer')->andReturns([$this->project_admins]);

        $this->umbrella_manager->shouldReceive('recursivelyCreateUmbrellaProjects')->with([$this->server], $this->project)->once();
        $this->driver->shouldReceive('createProject')->with($this->server, $this->repository, $this->project_unix_name)
            ->once()
            ->andReturns($this->gerrit_project);

        $project_name = $this->project_creator->createGerritProject($this->server, $this->repository, $this->migrate_access_rights);
        $this->project_creator->finalizeGerritProjectCreation($this->server, $this->repository, $this->template_id);

        $this->assertEquals($project_name, $this->gerrit_project);

        $this->assertAllGitBranchesPushedToTheServer();
        $this->assertAllGitTagsPushedToTheServer();
    }

    public function testItCreatesProjectMembersGroup()
    {
        $ugroup = \Mockery::spy(\ProjectUGroup::class);
        $ugroup->shouldReceive('getNormalizedName')->andReturns('project_members');
        $ugroup->shouldReceive('getId')->andReturns(ProjectUGroup::PROJECT_MEMBERS);

        $ugroup_project_admins = \Mockery::spy(\ProjectUGroup::class);
        $ugroup_project_admins->shouldReceive('getNormalizedName')->andReturns('project_admins');
        $ugroup_project_admins->shouldReceive('getId')->andReturns(ProjectUGroup::PROJECT_ADMIN);

        $this->ugroup_manager->shouldReceive('getUGroups')
            ->with($this->project)
            ->once()
            ->andReturns([$ugroup, $ugroup_project_admins]);

        $this->membership_manager->shouldReceive('createArrayOfGroupsForServer')
            ->with($this->server, [$ugroup, $ugroup_project_admins])
            ->once()
            ->andReturns([$ugroup, $ugroup_project_admins]);

        $this->driver->shouldReceive('createProject')->with($this->server, $this->repository, $this->project_unix_name)
            ->once()
            ->andReturns($this->gerrit_project);

        $this->project_creator->createGerritProject($this->server, $this->repository, $this->migrate_access_rights);
        $this->project_creator->finalizeGerritProjectCreation($this->server, $this->repository, $this->template_id);
    }

    public function testItCreatesAllGroups()
    {
        $ugroup_project_members = \Mockery::spy(\ProjectUGroup::class);
        $ugroup_project_members->shouldReceive('getNormalizedName')->andReturns('project_members');
        $ugroup_project_members->shouldReceive('getId')->andReturns(ProjectUGroup::PROJECT_MEMBERS);

        $ugroup_project_admins = \Mockery::spy(\ProjectUGroup::class);
        $ugroup_project_admins->shouldReceive('getNormalizedName')->andReturns('project_admins');
        $ugroup_project_admins->shouldReceive('getId')->andReturns(ProjectUGroup::PROJECT_ADMIN);

        $ugroup_another_group = \Mockery::spy(\ProjectUGroup::class);
        $ugroup_another_group->shouldReceive('getNormalizedName')->andReturns('another_group');
        $ugroup_another_group->shouldReceive('getId')->andReturns(120);

        $this->ugroup_manager->shouldReceive('getUGroups')->andReturns([$ugroup_project_members, $ugroup_another_group, $ugroup_project_admins]);

        $this->membership_manager->shouldReceive('createArrayOfGroupsForServer')
            ->with($this->server, [$ugroup_project_members, $ugroup_another_group, $ugroup_project_admins])
            ->once()
            ->andReturns([$ugroup_project_members, $ugroup_another_group, $ugroup_project_admins]);

        $this->driver->shouldReceive('createProject')->with($this->server, $this->repository, $this->project_unix_name)
            ->once()
            ->andReturns($this->gerrit_project);

        $this->project_creator->createGerritProject($this->server, $this->repository, $this->migrate_access_rights);
        $this->project_creator->finalizeGerritProjectCreation($this->server, $this->repository, $this->template_id);
    }

    private function assertAllGitBranchesPushedToTheServer(): void
    {
        $cwd = getcwd();
        chdir("$this->tmpdir/$this->gitolite_project");

        exec(Git_Exec::getGitCommand() . " show-ref --heads", $refs_cmd, $ret_val);

        $expected_result = ["To $this->gerrit_git_url"];

        foreach ($refs_cmd as $ref) {
            $ref               = substr($ref, strpos($ref, ' ') + 1);
            $expected_result[] = "=\t$ref:$ref\t[up to date]";
        }

        $expected_result[] = "Done";

        exec(Git_Exec::getGitCommand() . " push $this->gerrit_git_url refs/heads/*:refs/heads/* --porcelain", $output, $ret_val);
        chdir($cwd);

        $this->assertEquals($expected_result, $output);
        $this->assertEquals(0, $ret_val);
    }

    private function assertAllGitTagsPushedToTheServer(): void
    {
        $cwd = getcwd();
        chdir("$this->tmpdir/$this->gitolite_project");

        exec(Git_Exec::getGitCommand() . " show-ref --tags", $refs_cmd, $ret_val);
        $expected_result = ["To $this->gerrit_git_url"];

        foreach ($refs_cmd as $ref) {
            $ref               = substr($ref, strpos($ref, ' ') + 1);
            $expected_result[] = "=\t$ref:$ref\t[up to date]";
        }

        $expected_result[] = "Done";

        exec(Git_Exec::getGitCommand() . " push $this->gerrit_git_url refs/tags/*:refs/tags/* --porcelain", $output, $ret_val);
        chdir($cwd);

        $this->assertEquals($expected_result, $output);
        $this->assertEquals(0, $ret_val);
    }
}
