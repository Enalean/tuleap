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
use Git;
use Git_Driver_Gerrit;
use Git_Driver_Gerrit_GerritDriverFactory;
use Git_Driver_Gerrit_MembershipManager;
use Git_Driver_Gerrit_ProjectCreator;
use Git_Driver_Gerrit_ProjectCreator_ProjectAlreadyExistsException;
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
use ProjectManager;
use ProjectUGroup;
use Tuleap\ForgeConfigSandbox;
use Tuleap\TemporaryTestDirectory;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\ProjectUGroupTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use UGroupManager;
use ZipArchive;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class ProjectCreatorTest extends TestCase
{
    use ForgeConfigSandbox;
    use TemporaryTestDirectory;

    private string $contributors = 'tuleap-localhost-mozilla/firefox-contributors';
    private string $integrators  = 'tuleap-localhost-mozilla/firefox-integrators';
    private string $supermen     = 'tuleap-localhost-mozilla/firefox-supermen';
    private string $owners       = 'tuleap-localhost-mozilla/firefox-owners';
    private string $replication  = 'tuleap.example.com-replication';

    private string $contributors_uuid = '8bd90045412f95ff348f41fa63606171f2328db3';
    private string $integrators_uuid  = '19b1241e78c8355c5c3d8a7e856ce3c55f555c22';
    private string $supermen_uuid     = '8a7e856ce3c55f555c228bd90045412f95ff348';
    private string $owners_uuid       = 'f9427648913e6ff14190d81b7b0abc60fa325d3a';
    private string $replication_uuid  = '2ce5c45e3b88415e51ce7e0d3a1ba0526dce6424';

    private string $project_members_uuid        = '8bd90045412f95ff348f41fa63606171f2328db3';
    private string $another_ugroup_uuid         = '19b1241e78c8355c5c3d8a7e856ce3c55f555c22';
    private string $project_admins_uuid         = '8a7e856ce3c55f555c228bd90045412f95ff348';
    private string $project_members_gerrit_name = 'mozilla/project_members';
    private string $another_ugroup_gerrit_name  = 'mozilla/another_ugroup';
    private string $project_admins_gerrit_name  = 'mozilla/project_admins';

    private string $tmpdir;
    private string $gerrit_tmpdir;
    private string $fixtures;

    private Git_RemoteServer_GerritServer&MockObject $server;

    private int $project_id           = 103;
    private string $project_unix_name = 'mozilla';

    private UGroupManager&MockObject $ugroup_manager;
    private Git_Driver_Gerrit_MembershipManager&MockObject $membership_manager;
    private ProjectManager&MockObject $project_manager;
    private Git_Driver_Gerrit_UmbrellaProjectManager $umbrella_manager;

    private string $gerrit_project = 'tuleap-localhost-mozilla/firefox';
    private string $gerrit_git_url;
    private string $gerrit_admin_instance = 'admin-tuleap.example.com';
    private string $tuleap_instance       = 'tuleap.example.com';
    private string $gitolite_project      = 'gitolite_firefox.git';

    private Git_Driver_Gerrit_Template_TemplateFactory&MockObject $template_factory;

    private string $template_id = 'default';

    private Git_Driver_Gerrit_GerritDriverFactory&MockObject $gerrit_driver_factory;
    private Git_Driver_Gerrit_Template_TemplateProcessor $template_processor;
    private true $migrate_access_rights;
    private GitRepository&MockObject $repository;
    private Git_Driver_Gerrit_UserFinder&MockObject $userfinder;
    private GitRepository&MockObject $repository_in_a_private_project;
    private Git_Driver_Gerrit_ProjectCreator $project_creator;
    private GitRepository&MockObject $repository_without_registered;
    private GitRepository&MockObject $repository_with_registered;

    private function getGitExec($dir): Git_Exec
    {
        $git_exec = new Git_Exec($dir);
        $git_exec->allowUsageOfFileProtocol();
        return $git_exec;
    }

    #[\Override]
    protected function setUp(): void
    {
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
        $project                     = ProjectTestBuilder::aProject()
            ->withUnixName($this->project_unix_name)
            ->withAccessPublic()
            ->withId($this->project_id)->build();
        $private_project             = ProjectTestBuilder::aProject()->withUnixName($this->project_unix_name)->withAccessPrivate()->build();

        $this->repository                      = $this->createMock(GitRepository::class);
        $this->repository_in_a_private_project = $this->createMock(GitRepository::class);
        $this->repository_without_registered   = $this->createMock(GitRepository::class);
        $this->repository_with_registered      = $this->createMock(GitRepository::class);
        $this->repository->method('getId');
        $this->repository_in_a_private_project->method('getId');
        $this->repository_without_registered->method('getId');
        $this->repository_with_registered->method('getId');
        $this->repository->method('getFullPath')->willReturn($this->tmpdir . '/' . $this->gitolite_project);
        $this->repository_in_a_private_project->method('getFullPath')->willReturn($this->tmpdir . '/' . $this->gitolite_project);
        $this->repository_without_registered->method('getFullPath')->willReturn($this->tmpdir . '/' . $this->gitolite_project);
        $this->repository_with_registered->method('getFullPath')->willReturn($this->tmpdir . '/' . $this->gitolite_project);

        $driver = $this->createMock(Git_Driver_Gerrit::class);
        $driver->method('createProject')->with(
            $this->server,
            self::callback(fn(GitRepository $repository) => $repository === $this->repository
                                                            || $repository === $this->repository_in_a_private_project
                                                            || $repository === $this->repository_without_registered
                                                            || $repository === $this->repository_with_registered),
            $this->project_unix_name,
        )->willReturn($this->gerrit_project);
        $driver->method('createProjectWithPermissionsOnly')->with($this->server, $project, $this->project_admins_gerrit_name)->willReturn($this->project_unix_name);
        $driver->method('doesTheProjectExist')->willReturn(false);
        $driver->method('getGerritProjectName')->willReturn($this->gerrit_project);

        $this->gerrit_driver_factory = $this->createMock(Git_Driver_Gerrit_GerritDriverFactory::class);
        $this->gerrit_driver_factory->method('getDriver')->willReturn($driver);

        $this->membership_manager = $this->createMock(Git_Driver_Gerrit_MembershipManager::class);
        $this->membership_manager->method('getGroupUUIDByNameOnServer')->with($this->server, self::anything())
            ->willReturnCallback(fn($server, string $name) => match ($name) {
                $this->contributors                => $this->contributors_uuid,
                $this->integrators                 => $this->integrators_uuid,
                $this->supermen                    => $this->supermen_uuid,
                $this->owners                      => $this->owners_uuid,
                $this->replication                 => $this->replication_uuid,
                $this->project_members_gerrit_name => $this->project_members_uuid,
                $this->another_ugroup_gerrit_name  => $this->another_ugroup_uuid,
                $this->project_admins_gerrit_name  => $this->project_admins_uuid,
                default                            => throw new LogicException("Should not be called with $name"),
            });

        $this->userfinder     = $this->createMock(Git_Driver_Gerrit_UserFinder::class);
        $this->ugroup_manager = $this->createMock(UGroupManager::class);

        $this->project_manager = $this->createMock(ProjectManager::class);

        $this->umbrella_manager = $this->createMock(Git_Driver_Gerrit_UmbrellaProjectManager::class);
        $this->umbrella_manager->method('recursivelyCreateUmbrellaProjects');

        $template = $this->createMock(Git_Driver_Gerrit_Template_Template::class);
        $template->method('getId')->willReturn(12);
        $this->template_processor = new Git_Driver_Gerrit_Template_TemplateProcessor();
        $this->template_factory   = $this->createMock(Git_Driver_Gerrit_Template_TemplateFactory::class);
        $this->template_factory->method('getTemplate')->with(12)->willReturn($template);
        $this->template_factory->method('getTemplatesAvailableForRepository')->willReturn([$template]);

        $this->gerrit_tmpdir = $this->tmpdir . '/gerrit_tbd';

        $git_exec = $this->getGitExec($this->gerrit_tmpdir);

        $this->project_creator = new Git_Driver_Gerrit_ProjectCreator(
            $this->gerrit_tmpdir,
            $this->gerrit_driver_factory,
            $this->userfinder,
            $this->ugroup_manager,
            $this->membership_manager,
            $this->umbrella_manager,
            $this->template_factory,
            $this->template_processor,
            $git_exec
        );

        $this->repository->method('getProject')->willReturn($project);
        $this->repository_in_a_private_project->method('getProject')->willReturn($private_project);
        $this->repository_without_registered->method('getProject')->willReturn($project);
        $this->repository_with_registered->method('getProject')->willReturn($project);

        $this->userfinder->method('areRegisteredUsersAllowedTo')
            ->willReturnCallback(fn(string $perm, GitRepository $repository) => match ($repository) {
                $this->repository, $this->repository_in_a_private_project, $this->repository_with_registered => true,
                $this->repository_without_registered                                                         => false,
            });

        $project_members = ProjectUGroupTestBuilder::buildProjectMembers();
        $another_ugroup  = ProjectUGroupTestBuilder::aCustomUserGroup(120)->withName('another_ugroup')->build();
        $project_admins  = ProjectUGroupTestBuilder::buildProjectAdmins();

        $this->ugroup_manager->method('getUGroups')->willReturn([$project_members, $another_ugroup, $project_admins]);

        $this->membership_manager->method('createArrayOfGroupsForServer')->willReturn([$project_members, $another_ugroup, $project_admins]);
    }

    #[\Override]
    protected function tearDown(): void
    {
        $this->recurseDeleteInDir($this->tmpdir);
        rmdir($this->tmpdir);
    }

    public function testItPushesTheUpdatedConfigToTheServer(): void
    {
        $this->userfinder->method('getUgroups')->with($this->repository->getId(), self::isString())
            ->willReturnCallback(static fn($id, string $perm) => match ($perm) {
                Git::PERM_READ  => [ProjectUGroup::REGISTERED],
                Git::PERM_WRITE => [ProjectUGroup::PROJECT_MEMBERS, 120],
                Git::PERM_WPLUS => [ProjectUGroup::PROJECT_ADMIN],
            });

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
        $this->userfinder->method('getUgroups')->with($this->repository->getId(), self::isString())
            ->willReturnCallback(static fn($id, string $perm) => match ($perm) {
                Git::PERM_READ  => [ProjectUGroup::REGISTERED],
                Git::PERM_WRITE => [ProjectUGroup::PROJECT_MEMBERS, 120],
                Git::PERM_WPLUS => [ProjectUGroup::PROJECT_ADMIN],
            });

        $driver = $this->createMock(Git_Driver_Gerrit::class);
        $driver->method('doesTheProjectExist')->willReturn(true);
        $driver->method('getGerritProjectName');
        $gerrit_driver_factory = $this->createMock(Git_Driver_Gerrit_GerritDriverFactory::class);
        $gerrit_driver_factory->method('getDriver')->willReturn($driver);

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
        $this->expectException(Git_Driver_Gerrit_ProjectCreator_ProjectAlreadyExistsException::class);

        $project_creator->createGerritProject($this->server, $this->repository, $this->migrate_access_rights);
        $project_creator->finalizeGerritProjectCreation($this->server, $this->repository, $this->template_id);
    }

    public function testItDoesNotSetPermsIfMigrateAccessRightIsFalse(): void
    {
        $project_creator = $this->getMockBuilder(Git_Driver_Gerrit_ProjectCreator::class)
            ->setConstructorArgs([
                $this->gerrit_tmpdir,
                $this->gerrit_driver_factory,
                $this->userfinder,
                $this->ugroup_manager,
                $this->membership_manager,
                $this->umbrella_manager,
                $this->template_factory,
                $this->template_processor,
                $this->getGitExec($this->gerrit_tmpdir),
            ])
            ->onlyMethods(['exportGitBranches'])
            ->getMock();

        $project_creator->method('exportGitBranches');

        $project_creator->createGerritProject($this->server, $this->repository_in_a_private_project, false);

        self::assertFileExists("$this->gerrit_tmpdir/project.config");
        self::assertDoesNotMatchRegularExpression('/group mozilla\//', file_get_contents("$this->gerrit_tmpdir/project.config"));
        self::assertMatchesRegularExpression('/group Administrators/', file_get_contents("$this->gerrit_tmpdir/project.config"));
    }

    public function testItDoesNotSetPermsOnRegisteredUsersIfProjectIsPrivate(): void
    {
        $project_creator = $this->getMockBuilder(Git_Driver_Gerrit_ProjectCreator::class)
            ->setConstructorArgs([
                $this->gerrit_tmpdir,
                $this->gerrit_driver_factory,
                $this->userfinder,
                $this->ugroup_manager,
                $this->membership_manager,
                $this->umbrella_manager,
                $this->template_factory,
                $this->template_processor,
                $this->getGitExec($this->gerrit_tmpdir),
            ])
            ->onlyMethods(['exportGitBranches'])
            ->getMock();

        $project_creator->method('exportGitBranches');

        $this->userfinder->method('getUgroups')->with($this->repository->getId(), self::isString())
            ->willReturnCallback(static fn($id, string $perm) => match ($perm) {
                Git::PERM_READ  => [ProjectUGroup::REGISTERED],
                Git::PERM_WRITE,
                Git::PERM_WPLUS => [],
            });

        $project_creator->createGerritProject($this->server, $this->repository_in_a_private_project, $this->migrate_access_rights);
        $project_creator->finalizeGerritProjectCreation($this->server, $this->repository, $this->template_id);

        self::assertDoesNotMatchRegularExpression('/Registered Users/', file_get_contents("$this->gerrit_tmpdir/project.config"));
    }

    public function testItDoesNotSetPermsOnRegisteredUsersIfRepoHasNoPermsForRegisteredOrAnonymous(): void
    {
        $groups = [
            ProjectUGroup::REGISTERED,
            ProjectUGroup::ANONYMOUS,
        ];
        $this->userfinder->method('getUgroups')->with($this->repository->getId(), self::isString())
            ->willReturnCallback(static fn($id, string $perm) => match ($perm) {
                Git::PERM_READ,
                Git::PERM_WRITE,
                Git::PERM_WPLUS => $groups,
            });

        $this->project_creator->createGerritProject($this->server, $this->repository_without_registered, $this->migrate_access_rights);
        $this->project_creator->finalizeGerritProjectCreation($this->server, $this->repository, $this->template_id);

        self::assertDoesNotMatchRegularExpression('/Registered Users/', file_get_contents("$this->gerrit_tmpdir/project.config"));
    }

    public function testItSetsPermsLabelCodeReviewOnceIfUserCanReadANdWrite(): void
    {
        $this->userfinder->method('getUgroups')->with($this->repository->getId(), self::isString())
            ->willReturnCallback(static fn($id, string $perm) => match ($perm) {
                Git::PERM_READ,
                Git::PERM_WRITE => [ProjectUGroup::PROJECT_MEMBERS],
                Git::PERM_WPLUS => [],
            });

        $this->project_creator->createGerritProject($this->server, $this->repository_with_registered, $this->migrate_access_rights);
        $this->project_creator->finalizeGerritProjectCreation($this->server, $this->repository, $this->template_id);

        self::assertDoesNotMatchRegularExpression('/label-Code-Review = -1..+1/', file_get_contents("$this->gerrit_tmpdir/project.config"));
        self::assertMatchesRegularExpression('/label-Code-Review = -2..+2/', file_get_contents("$this->gerrit_tmpdir/project.config"));
    }

    public function testItSetsPermsOnRegisteredUsersIfRepoHasReadForRegistered(): void
    {
        $this->userfinder->method('getUgroups')->with($this->repository->getId(), self::isString())
            ->willReturnCallback(static fn($id, string $perm) => match ($perm) {
                Git::PERM_READ  => [ProjectUGroup::REGISTERED],
                Git::PERM_WRITE,
                Git::PERM_WPLUS => [],
            });

        $this->project_creator->createGerritProject($this->server, $this->repository_with_registered, $this->migrate_access_rights);
        $this->project_creator->finalizeGerritProjectCreation($this->server, $this->repository, $this->template_id);

        self::assertMatchesRegularExpression('/Registered Users/', file_get_contents("$this->gerrit_tmpdir/project.config"));
    }

    public function testItSetsPermsOnRegisteredUsersIfRepoHasWriteForRegistered(): void
    {
        $this->userfinder->method('getUgroups')->with($this->repository->getId(), self::isString())
            ->willReturnCallback(static fn($id, string $perm) => match ($perm) {
                Git::PERM_READ  => [],
                Git::PERM_WRITE => [ProjectUGroup::REGISTERED],
                Git::PERM_WPLUS => [],
            });

        $this->project_creator->createGerritProject($this->server, $this->repository_with_registered, $this->migrate_access_rights);
        $this->project_creator->finalizeGerritProjectCreation($this->server, $this->repository, $this->template_id);

        self::assertMatchesRegularExpression('/Registered Users/', file_get_contents("$this->gerrit_tmpdir/project.config"));
    }

    public function testItSetsPermsOnRegisteredUsersIfRepoHasExecuteForRegistered(): void
    {
        $this->userfinder->method('getUgroups')->with($this->repository->getId(), self::isString())
            ->willReturnCallback(static fn($id, string $perm) => match ($perm) {
                Git::PERM_READ,
                Git::PERM_WRITE => [],
                Git::PERM_WPLUS => [ProjectUGroup::REGISTERED],
            });

        $this->project_creator->createGerritProject($this->server, $this->repository_with_registered, $this->migrate_access_rights);
        $this->project_creator->finalizeGerritProjectCreation($this->server, $this->repository, $this->template_id);

        self::assertMatchesRegularExpression('/Registered Users/', file_get_contents("$this->gerrit_tmpdir/project.config"));
    }

    public function isSetsPermsOnRegisteredUsersIfRepoHasReadForAuthenticated(): void
    {
        $this->userfinder->method('getUgroups')->with($this->repository->getId(), self::isString())
            ->willReturnCallback(static fn($id, string $perm) => match ($perm) {
                Git::PERM_READ  => [ProjectUGroup::AUTHENTICATED],
                Git::PERM_WRITE,
                Git::PERM_WPLUS => [],
            });

        $this->project_creator->createGerritProject($this->server, $this->repository_with_registered, $this->migrate_access_rights);
        $this->project_creator->finalizeGerritProjectCreation($this->server, $this->repository, $this->template_id);

        self::assertMatchesRegularExpression('/Registered Users/', file_get_contents("$this->gerrit_tmpdir/project.config"));
    }

    public function testItSetsPermsOnRegisteredUsersIfRepoHasWriteForAuthenticated(): void
    {
        $this->userfinder->method('getUgroups')->with($this->repository->getId(), self::isString())
            ->willReturnCallback(static fn($id, string $perm) => match ($perm) {
                Git::PERM_READ  => [],
                Git::PERM_WRITE => [ProjectUGroup::AUTHENTICATED],
                Git::PERM_WPLUS => [],
            });

        $this->project_creator->createGerritProject($this->server, $this->repository_with_registered, $this->migrate_access_rights);
        $this->project_creator->finalizeGerritProjectCreation($this->server, $this->repository, $this->template_id);

        self::assertMatchesRegularExpression('/Registered Users/', file_get_contents("$this->gerrit_tmpdir/project.config"));
    }

    public function testItSetsPermsOnRegisteredUsersIfRepoHasExecuteForAuthenticated(): void
    {
        $this->userfinder->method('getUgroups')->with($this->repository->getId(), self::isString())
            ->willReturnCallback(static fn($id, string $perm) => match ($perm) {
                Git::PERM_READ,
                Git::PERM_WRITE => [],
                Git::PERM_WPLUS => [ProjectUGroup::AUTHENTICATED],
            });

        $this->project_creator->createGerritProject($this->server, $this->repository_with_registered, $this->migrate_access_rights);
        $this->project_creator->finalizeGerritProjectCreation($this->server, $this->repository, $this->template_id);

        self::assertMatchesRegularExpression('/Registered Users/', file_get_contents("$this->gerrit_tmpdir/project.config"));
    }

    public function testItSetsPermsOnRegisteredUsersIfRepoHasReadForAnonymous(): void
    {
        $this->userfinder->method('getUgroups')->with($this->repository->getId(), self::isString())
            ->willReturnCallback(static fn($id, string $perm) => match ($perm) {
                Git::PERM_READ  => [ProjectUGroup::ANONYMOUS],
                Git::PERM_WRITE,
                Git::PERM_WPLUS => [],
            });

        $this->project_creator->createGerritProject($this->server, $this->repository_with_registered, $this->migrate_access_rights);
        $this->project_creator->finalizeGerritProjectCreation($this->server, $this->repository, $this->template_id);

        self::assertMatchesRegularExpression('/Registered Users/', file_get_contents("$this->gerrit_tmpdir/project.config"));
    }

    public function testItSetsPermsOnRegisteredUsersIfRepoHasWriteForAnonymous(): void
    {
        $this->userfinder->method('getUgroups')->with($this->repository->getId(), self::isString())
            ->willReturnCallback(static fn($id, string $perm) => match ($perm) {
                Git::PERM_READ  => [],
                Git::PERM_WRITE => [ProjectUGroup::ANONYMOUS],
                Git::PERM_WPLUS => [],
            });

        $this->project_creator->createGerritProject($this->server, $this->repository_with_registered, $this->migrate_access_rights);
        $this->project_creator->finalizeGerritProjectCreation($this->server, $this->repository, $this->template_id);

        self::assertMatchesRegularExpression('/Registered Users/', file_get_contents("$this->gerrit_tmpdir/project.config"));
    }

    public function testItSetsPermsOnRegisteredUsersIfRepoHasExecuteForAnonymous(): void
    {
        $this->userfinder->method('getUgroups')->with($this->repository->getId(), self::isString())
            ->willReturnCallback(static fn($id, string $perm) => match ($perm) {
                Git::PERM_READ,
                Git::PERM_WRITE => [],
                Git::PERM_WPLUS => [ProjectUGroup::ANONYMOUS],
            });

        $this->project_creator->createGerritProject($this->server, $this->repository_with_registered, $this->migrate_access_rights);
        $this->project_creator->finalizeGerritProjectCreation($this->server, $this->repository, $this->template_id);

        self::assertMatchesRegularExpression('/Registered Users/', file_get_contents("$this->gerrit_tmpdir/project.config"));
    }

    private function assertItClonesTheDistantRepo(): void
    {
        $groups_file = "$this->gerrit_tmpdir/groups";
        $config_file = "$this->gerrit_tmpdir/project.config";

        self::assertTrue(is_file($groups_file));
        self::assertTrue(is_file($config_file));
    }

    private function assertCommitterIsConfigured(): void
    {
        self::assertEquals(trim(shell_exec("cd $this->gerrit_tmpdir; " . Git_Exec::getGitCommand() . ' config --get user.name')), $this->gerrit_admin_instance);
        self::assertEquals(trim(shell_exec("cd $this->gerrit_tmpdir; " . Git_Exec::getGitCommand() . ' config --get user.email')), 'codendiadm@' . $this->tuleap_instance);
    }

    private function assertTheRemoteOriginIsConfigured(): void
    {
        $cwd = getcwd();
        chdir("$this->gerrit_tmpdir");
        exec(Git_Exec::getGitCommand() . ' remote -v', $output, $ret_val);
        chdir($cwd);

        self::assertEquals([
            "origin\t$this->gerrit_git_url (fetch)",
            "origin\t$this->gerrit_git_url (push)",
        ], $output);
        self::assertEquals(0, $ret_val);
    }

    private function assertEverythingIsPushedToTheServer(): void
    {
        $cwd = getcwd();
        chdir("$this->gerrit_tmpdir");
        exec(Git_Exec::getGitCommand() . ' push origin HEAD:refs/meta/config --porcelain', $output, $ret_val);
        chdir($cwd);
        self::assertEquals([
            "To $this->gerrit_git_url",
            "=\tHEAD:refs/meta/config\t[up to date]",
            'Done',
        ], $output);
        self::assertEquals(0, $ret_val);
    }

    private function assertEverythingIsCommitted(): void
    {
        $cwd = getcwd();
        chdir("$this->gerrit_tmpdir");
        exec(Git_Exec::getGitCommand() . ' status --porcelain', $output, $ret_val);
        chdir($cwd);
        self::assertEquals([], $output);
        self::assertEquals(0, $ret_val);
    }

    private function assertPermissionsFileHasEverything(): void
    {
        $config_file_contents = file_get_contents("$this->gerrit_tmpdir/project.config");

        $expected_contents = file_get_contents("$this->fixtures/expected_access_rights.config");

        self::assertEquals($expected_contents, $config_file_contents);
    }

    private function assertGroupsFileHasEverything(): void
    {
        $groups_file         = "$this->gerrit_tmpdir/groups";
        $group_file_contents = file_get_contents($groups_file);

        self::assertMatchesRegularExpression("%$this->project_members_uuid\t$this->project_members_gerrit_name\n%", $group_file_contents);
        self::assertMatchesRegularExpression("%$this->another_ugroup_uuid\t$this->another_ugroup_gerrit_name\n%", $group_file_contents);
        self::assertMatchesRegularExpression("%$this->replication_uuid\t$this->replication\n%", $group_file_contents);
        self::assertMatchesRegularExpression("%global:Registered-Users\tRegistered Users\n%", $group_file_contents);
    }
}
