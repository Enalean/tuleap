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

declare(strict_types=1);

namespace Tuleap\Git\Driver\Gerrit;

use ForgeConfig;
use Git_Driver_Gerrit;
use Git_Driver_Gerrit_GerritDriverFactory;
use Git_Driver_Gerrit_MembershipManager;
use Git_Driver_Gerrit_ProjectCreator;
use Git_Driver_Gerrit_Template_Template;
use Git_Driver_Gerrit_Template_TemplateFactory;
use Git_Driver_Gerrit_Template_TemplateProcessor;
use Git_Driver_Gerrit_UmbrellaProjectManager;
use Git_Driver_Gerrit_UserFinder;
use Git_Exec;
use Git_RemoteServer_GerritServer;
use GitRepository;
use LogicException;
use PHPUnit\Framework\MockObject\MockObject;
use Project;
use ProjectManager;
use Tuleap\ForgeConfigSandbox;
use Tuleap\TemporaryTestDirectory;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\ProjectUGroupTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use UGroupManager;
use ZipArchive;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class ProjectCreatorCallToGerritTest extends TestCase
{
    use ForgeConfigSandbox;
    use TemporaryTestDirectory;

    private const CONTRIBUTORS = 'tuleap-localhost-mozilla/firefox-contributors';
    private const INTEGRATORS  = 'tuleap-localhost-mozilla/firefox-integrators';
    private const SUPERMEN     = 'tuleap-localhost-mozilla/firefox-supermen';
    private const OWNERS       = 'tuleap-localhost-mozilla/firefox-owners';
    private const REPLICATION  = 'tuleap.example.com-replication';

    private const CONTRIBUTORS_UUID = '8bd90045412f95ff348f41fa63606171f2328db3';
    private const INTEGRATORS_UUID  = '19b1241e78c8355c5c3d8a7e856ce3c55f555c22';
    private const SUPERMEN_UUID     = '8a7e856ce3c55f555c228bd90045412f95ff348';
    private const OWNERS_UUID       = 'f9427648913e6ff14190d81b7b0abc60fa325d3a';
    private const REPLICATION_UUID  = '2ce5c45e3b88415e51ce7e0d3a1ba0526dce6424';

    private const PROJECT_ADMINS_GERRIT_NAME = 'mozilla/project_admins';

    private string $tmpdir;

    private Git_RemoteServer_GerritServer&MockObject $server;

    private Project $project;
    private const PROJECT_ID        = 103;
    private const PROJECT_UNIX_NAME = 'mozilla';

    private UGroupManager&MockObject $ugroup_manager;

    private Git_Driver_Gerrit_MembershipManager&MockObject $membership_manager;

    private ProjectManager&MockObject $project_manager;

    private Git_Driver_Gerrit_UmbrellaProjectManager&MockObject $umbrella_manager;

    private string $gerrit_project = 'tuleap-localhost-mozilla/firefox';
    private string $gerrit_git_url;
    private string $gerrit_admin_instance = 'admin-tuleap.example.com';
    private string $tuleap_instance       = 'tuleap.example.com';
    private string $gitolite_project      = 'gitolite_firefox.git';

    private string $template_id = 'default';

    private true $migrate_access_rights;
    private GitRepository&MockObject $repository;
    private GitRepository&MockObject $repository_in_a_private_project;
    private GitRepository&MockObject $repository_without_registered;
    private GitRepository&MockObject $repository_with_registered;
    private Git_Driver_Gerrit&MockObject $driver;
    private Git_Driver_Gerrit_ProjectCreator $project_creator;

    protected function setUp(): void
    {
        ForgeConfig::set('sys_default_domain', $this->tuleap_instance);
        ForgeConfig::set('tmp_dir', $this->getTmpDir());
        $fixtures = dirname(__FILE__) . '/_fixtures';
        do {
            $this->tmpdir = ForgeConfig::get('tmp_dir') . '/' . bin2hex(random_bytes(16));
        } while (is_dir($this->tmpdir));
        $zip_archive = new ZipArchive();
        $zip_archive->open("$fixtures/firefox.zip");
        $zip_archive->extractTo($this->tmpdir);
        shell_exec("tar --no-same-owner -xzf $fixtures/gitolite_firefox.git.tgz --directory $this->tmpdir");

        $host         = $this->tmpdir;
        $login        = $this->gerrit_admin_instance;
        $id           = $ssh_port = $http_port = $identity_file = $replication_key = $use_ssl = $gerrit_version = $http_password = 0;
        $this->server = $this->getMockBuilder(Git_RemoteServer_GerritServer::class)
            ->setConstructorArgs([
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
            ])
            ->onlyMethods(['getCloneSSHUrl'])
            ->getMock();

        $this->gerrit_git_url = "$host/$this->gerrit_project";
        $this->server->method('getCloneSSHUrl')->with($this->gerrit_project)->willReturn($this->gerrit_git_url);

        $this->migrate_access_rights = true;
        $this->project               = ProjectTestBuilder::aProject()->withId(self::PROJECT_ID)->withUnixName(self::PROJECT_UNIX_NAME)->withAccessPublic()->build();
        $private_project             = ProjectTestBuilder::aProject()->withAccessPrivate()->build();

        $this->repository                      = $this->createMock(GitRepository::class);
        $this->repository_in_a_private_project = $this->createMock(GitRepository::class);
        $this->repository_without_registered   = $this->createMock(GitRepository::class);
        $this->repository_with_registered      = $this->createMock(GitRepository::class);
        $this->repository->method('getFullPath')->willReturn($this->tmpdir . '/' . $this->gitolite_project);
        $this->repository_in_a_private_project->method('getFullPath')->willReturn($this->tmpdir . '/' . $this->gitolite_project);
        $this->repository_without_registered->method('getFullPath')->willReturn($this->tmpdir . '/' . $this->gitolite_project);
        $this->repository_with_registered->method('getFullPath')->willReturn($this->tmpdir . '/' . $this->gitolite_project);
        $this->repository->method('getId');
        $this->repository_in_a_private_project->method('getId');
        $this->repository_without_registered->method('getId');
        $this->repository_with_registered->method('getId');

        $this->driver = $this->createMock(Git_Driver_Gerrit::class);
        $this->driver->method('createProjectWithPermissionsOnly')->with($this->server, $this->project, self::PROJECT_ADMINS_GERRIT_NAME)
            ->willReturn(self::PROJECT_UNIX_NAME);
        $this->driver->method('doesTheProjectExist')->willReturn(false);
        $this->driver->method('getGerritProjectName')->willReturn($this->gerrit_project);

        $gerrit_driver_factory = $this->createMock(Git_Driver_Gerrit_GerritDriverFactory::class);
        $gerrit_driver_factory->method('getDriver')->willReturn($this->driver);

        $this->membership_manager = $this->createMock(Git_Driver_Gerrit_MembershipManager::class);
        $this->membership_manager->method('getGroupUUIDByNameOnServer')
            ->willReturnCallback(fn(Git_RemoteServer_GerritServer $server, string $group) => match ($group) {
                self::CONTRIBUTORS => self::CONTRIBUTORS_UUID,
                self::INTEGRATORS  => self::INTEGRATORS_UUID,
                self::SUPERMEN     => self::SUPERMEN_UUID,
                self::OWNERS       => self::OWNERS_UUID,
                self::REPLICATION  => self::REPLICATION_UUID,
                default            => throw new LogicException("Should not be called with $group"),
            });

        $userfinder           = $this->createMock(Git_Driver_Gerrit_UserFinder::class);
        $this->ugroup_manager = $this->createMock(UGroupManager::class);

        $this->project_manager = $this->createMock(ProjectManager::class);

        $this->umbrella_manager = $this->createMock(Git_Driver_Gerrit_UmbrellaProjectManager::class);

        $template = $this->createMock(Git_Driver_Gerrit_Template_Template::class);
        $template->method('getId')->willReturn(12);
        $template_processor = new Git_Driver_Gerrit_Template_TemplateProcessor();
        $template_factory   = $this->createMock(Git_Driver_Gerrit_Template_TemplateFactory::class);
        $template_factory->method('getTemplate')->with(12)->willReturn($template);
        $template_factory->method('getTemplatesAvailableForRepository')->willReturn([$template]);

        $gerrit_tmpdir = $this->tmpdir . '/gerrit_tbd';

        $this->project_creator = new Git_Driver_Gerrit_ProjectCreator(
            $gerrit_tmpdir,
            $gerrit_driver_factory,
            $userfinder,
            $this->ugroup_manager,
            $this->membership_manager,
            $this->umbrella_manager,
            $template_factory,
            $template_processor,
            $this->getGitExec($gerrit_tmpdir)
        );

        $this->repository->method('getProject')->willReturn($this->project);
        $this->repository_in_a_private_project->method('getProject')->willReturn($private_project);
        $this->repository_without_registered->method('getProject')->willReturn($this->project);
        $this->repository_with_registered->method('getProject')->willReturn($this->project);

        $userfinder->method('areRegisteredUsersAllowedTo')
            ->willReturnCallback(fn(string $permission, GitRepository $repository) => match ($repository) {
                $this->repository,
                $this->repository_in_a_private_project,
                $this->repository_with_registered    => true,
                $this->repository_without_registered => false,
            });

        $userfinder->method('getUgroups')->willReturn([]);
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
    }

    public function testItCreatesAProjectAndExportGitBranchesAndTagsWithoutCreateParentProject(): void
    {
        //ssh gerrit gerrit create tuleap.net-Firefox/all/mobile

        $project_admins = ProjectUGroupTestBuilder::buildProjectAdmins();

        $this->ugroup_manager->method('getUGroups')->willReturn([$project_admins]);
        $this->driver->method('doesTheParentProjectExist')->willReturn(true);

        $this->membership_manager->method('createArrayOfGroupsForServer')->willReturn([$project_admins]);

        $this->umbrella_manager->expects($this->once())->method('recursivelyCreateUmbrellaProjects')->with([$this->server], $this->project);
        $this->driver->expects($this->once())->method('createProject')->with($this->server, $this->repository, self::PROJECT_UNIX_NAME)
            ->willReturn($this->gerrit_project);

        $project_name = $this->project_creator->createGerritProject($this->server, $this->repository, $this->migrate_access_rights);
        $this->project_creator->finalizeGerritProjectCreation($this->server, $this->repository, $this->template_id);

        self::assertEquals($project_name, $this->gerrit_project);

        $this->assertAllGitBranchesPushedToTheServer();
        $this->assertAllGitTagsPushedToTheServer();
    }

    public function testItCreatesProjectMembersGroup(): void
    {
        $ugroup_project_members = ProjectUGroupTestBuilder::buildProjectMembers();
        $ugroup_project_admins  = ProjectUGroupTestBuilder::buildProjectAdmins();

        $this->ugroup_manager->expects($this->once())->method('getUGroups')
            ->with($this->project)
            ->willReturn([$ugroup_project_members, $ugroup_project_admins]);

        $this->membership_manager->expects($this->once())->method('createArrayOfGroupsForServer')
            ->with($this->server, [$ugroup_project_members, $ugroup_project_admins])
            ->willReturn([$ugroup_project_members, $ugroup_project_admins]);

        $this->driver->expects($this->once())->method('createProject')->with($this->server, $this->repository, self::PROJECT_UNIX_NAME)
            ->willReturn($this->gerrit_project);

        $this->umbrella_manager->method('recursivelyCreateUmbrellaProjects');

        $this->project_creator->createGerritProject($this->server, $this->repository, $this->migrate_access_rights);
        $this->project_creator->finalizeGerritProjectCreation($this->server, $this->repository, $this->template_id);
    }

    public function testItCreatesAllGroups(): void
    {
        $ugroup_project_members = ProjectUGroupTestBuilder::buildProjectMembers();
        $ugroup_project_admins  = ProjectUGroupTestBuilder::buildProjectAdmins();
        $ugroup_another_group   = ProjectUGroupTestBuilder::aCustomUserGroup(120)->withName('another_group')->build();

        $this->ugroup_manager->method('getUGroups')->willReturn([$ugroup_project_members, $ugroup_another_group, $ugroup_project_admins]);

        $this->membership_manager->expects($this->once())->method('createArrayOfGroupsForServer')
            ->with($this->server, [$ugroup_project_members, $ugroup_another_group, $ugroup_project_admins])
            ->willReturn([$ugroup_project_members, $ugroup_another_group, $ugroup_project_admins]);

        $this->driver->expects($this->once())->method('createProject')->with($this->server, $this->repository, self::PROJECT_UNIX_NAME)
            ->willReturn($this->gerrit_project);

        $this->umbrella_manager->method('recursivelyCreateUmbrellaProjects');

        $this->project_creator->createGerritProject($this->server, $this->repository, $this->migrate_access_rights);
        $this->project_creator->finalizeGerritProjectCreation($this->server, $this->repository, $this->template_id);
    }

    private function assertAllGitBranchesPushedToTheServer(): void
    {
        $cwd = getcwd();
        chdir("$this->tmpdir/$this->gitolite_project");

        exec(Git_Exec::getGitCommand() . ' show-ref --heads', $refs_cmd, $ret_val);

        $expected_result = ["To $this->gerrit_git_url"];

        foreach ($refs_cmd as $ref) {
            $ref               = substr($ref, strpos($ref, ' ') + 1);
            $expected_result[] = "=\t$ref:$ref\t[up to date]";
        }

        $expected_result[] = 'Done';

        exec(Git_Exec::getGitCommand() . " push $this->gerrit_git_url refs/heads/*:refs/heads/* --porcelain", $output, $ret_val);
        chdir($cwd);

        self::assertEquals($expected_result, $output);
        self::assertEquals(0, $ret_val);
    }

    private function assertAllGitTagsPushedToTheServer(): void
    {
        $cwd = getcwd();
        chdir("$this->tmpdir/$this->gitolite_project");

        exec(Git_Exec::getGitCommand() . ' show-ref --tags', $refs_cmd, $ret_val);
        $expected_result = ["To $this->gerrit_git_url"];

        foreach ($refs_cmd as $ref) {
            $ref               = substr($ref, strpos($ref, ' ') + 1);
            $expected_result[] = "=\t$ref:$ref\t[up to date]";
        }

        $expected_result[] = 'Done';

        exec(Git_Exec::getGitCommand() . " push $this->gerrit_git_url refs/tags/*:refs/tags/* --porcelain", $output, $ret_val);
        chdir($cwd);

        self::assertEquals($expected_result, $output);
        self::assertEquals(0, $ret_val);
    }
}
