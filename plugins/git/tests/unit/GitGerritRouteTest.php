<?php
/**
 * Copyright (c) Enalean, 2012 - Present. All Rights Reserved.
 *
 * This file is a part of Tuleap.
 *
 * Codendi is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Codendi is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Codendi. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace Tuleap\Git;

use ForgeConfig;
use Git;
use Git_Driver_Gerrit;
use Git_Driver_Gerrit_GerritDriverFactory;
use Git_Driver_Gerrit_ProjectCreator;
use Git_Driver_Gerrit_ProjectCreatorStatus;
use Git_Driver_Gerrit_Template_Template;
use Git_Driver_Gerrit_Template_TemplateFactory;
use Git_Driver_Gerrit_UserAccountManager;
use Git_GitRepositoryUrlManager;
use Git_RemoteServer_GerritServer;
use Git_RemoteServer_GerritServerFactory;
use Git_SystemEventManager;
use GitPermissionsManager;
use GitPlugin;
use GitRepository;
use GitRepositoryFactory;
use GitRepositoryManager;
use HTTPRequest;
use PFUser;
use PHPUnit\Framework\MockObject\MockObject;
use ProjectHistoryDao;
use ProjectManager;
use Psr\Log\NullLogger;
use SystemEventDao;
use SystemEventManager;
use Tuleap\ForgeConfigSandbox;
use Tuleap\Git\DefaultBranch\DefaultBranchUpdater;
use Tuleap\Git\GitViews\Header\HeaderRenderer;
use Tuleap\Git\Notifications\UgroupsToNotifyDao;
use Tuleap\Git\Notifications\UsersToNotifyDao;
use Tuleap\Git\Permissions\DefaultFineGrainedPermissionFactory;
use Tuleap\Git\Permissions\FineGrainedPermissionDestructor;
use Tuleap\Git\Permissions\FineGrainedPermissionFactory;
use Tuleap\Git\Permissions\FineGrainedPermissionSaver;
use Tuleap\Git\Permissions\FineGrainedRepresentationBuilder;
use Tuleap\Git\Permissions\FineGrainedRetriever;
use Tuleap\Git\Permissions\FineGrainedUpdater;
use Tuleap\Git\Permissions\HistoryValueFormatter;
use Tuleap\Git\Permissions\PermissionChangesDetector;
use Tuleap\Git\Permissions\RegexpFineGrainedDisabler;
use Tuleap\Git\Permissions\RegexpFineGrainedEnabler;
use Tuleap\Git\Permissions\RegexpFineGrainedRetriever;
use Tuleap\Git\Permissions\RegexpPermissionFilter;
use Tuleap\Git\Permissions\TemplatePermissionsUpdater;
use Tuleap\Git\RemoteServer\GerritCanMigrateChecker;
use Tuleap\Git\Repository\DescriptionUpdater;
use Tuleap\Git\Repository\Settings\ArtifactClosure\ConfigureAllowArtifactClosure;
use Tuleap\Git\Repository\Settings\ArtifactClosure\VerifyArtifactClosureIsAllowed;
use Tuleap\Git\Tests\Builders\GitRepositoryTestBuilder;
use Tuleap\Git\Tests\Stub\DefaultBranch\DefaultBranchUpdateExecutorStub;
use Tuleap\GlobalLanguageMock;
use Tuleap\GlobalResponseMock;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use UGroupManager;
use UserManager;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class GitGerritRouteTest extends TestCase
{
    use ForgeConfigSandbox;
    use GlobalLanguageMock;
    use GlobalResponseMock;

    private int $repo_id              = 999;
    private int $group_id             = 101;
    private string $project_unix_name = 'gitproject';
    private GitRepository $repository;
    private PFUser $user;
    private PFUser $admin;
    private UserManager&MockObject $user_manager;
    private ProjectManager&MockObject $project_manager;
    private Git_Driver_Gerrit_ProjectCreator&MockObject $project_creator;
    private Git_Driver_Gerrit_Template_TemplateFactory&MockObject $template_factory;
    private GitPermissionsManager&MockObject $git_permissions_manager;

    protected function setUp(): void
    {
        $this->user                    = UserTestBuilder::aUser()->withoutSiteAdministrator()->build();
        $this->admin                   = UserTestBuilder::aUser()->withoutSiteAdministrator()->build();
        $this->user_manager            = $this->createMock(UserManager::class);
        $this->project_manager         = $this->createMock(ProjectManager::class);
        $this->project_creator         = $this->createMock(Git_Driver_Gerrit_ProjectCreator::class);
        $this->template_factory        = $this->createMock(Git_Driver_Gerrit_Template_TemplateFactory::class);
        $this->git_permissions_manager = $this->createMock(GitPermissionsManager::class);
        $project                       = ProjectTestBuilder::aProject()->withId($this->group_id)->withUnixName($this->project_unix_name)->build();
        $this->repository              = GitRepositoryTestBuilder::aProjectRepository()->withId($this->repo_id)->inProject($project)->build();

        $this->template_factory->method('getTemplatesAvailableForRepository')->willReturn([]);


        $this->project_manager->method('getProject')->willReturn($project);
        $this->project_creator->method('checkTemplateIsAvailableForProject')->willReturn(true);
        $this->git_permissions_manager->method('userIsGitAdmin')->with($this->admin, $project)->willReturn(true);

        $_SERVER['REQUEST_URI']    = '/plugins/tests/';
        $_SERVER['REQUEST_METHOD'] = 'POST';

        $system_event_manager = $this->createMock(SystemEventManager::class);
        $sys_dao              = $this->createMock(SystemEventDao::class);
        $sys_dao->method('searchWithParam')->willReturn([]);
        $system_event_manager->method('_getDao')->willReturn($sys_dao);

        SystemEventManager::setInstance($system_event_manager);
    }

    protected function tearDown(): void
    {
        SystemEventManager::clearInstance();
        unset($_SERVER['REQUEST_URI'], $_SERVER['REQUEST_METHOD'], $GLOBALS['_SESSION']);
    }

    private function getGit(
        HTTPRequest $request,
        GitRepositoryFactory $factory,
        ?Git_Driver_Gerrit_Template_TemplateFactory $template_factory = null,
    ): Git&MockObject {
        $template_factory = $template_factory ?? $this->template_factory;

        $git_plugin = $this->createMock(GitPlugin::class);
        $git_plugin->method('areFriendlyUrlsActivated')->willReturn(false);
        $url_manager           = new Git_GitRepositoryUrlManager($git_plugin);
        $server                = $this->createMock(Git_RemoteServer_GerritServer::class);
        $gerrit_server_factory = $this->createMock(Git_RemoteServer_GerritServerFactory::class);
        $gerrit_server_factory->method('getServerById')->willReturn($server);
        $can_migrate_checker = $this->createMock(GerritCanMigrateChecker::class);
        $can_migrate_checker->method('canMigrate')->willReturn(true);

        $gerrit_driver = $this->createMock(Git_Driver_Gerrit::class);
        $gerrit_driver->method('doesTheProjectExist')->willReturn(false);
        $gerrit_driver->method('getGerritProjectName');

        $http_request         = new HTTPRequest();
        $http_request->params = ['group_id' => $this->group_id];
        $driver_factory       = $this->createMock(Git_Driver_Gerrit_GerritDriverFactory::class);
        $driver_factory->method('getDriver')->willReturn($gerrit_driver);
        $git = $this->getMockBuilder(Git::class)
            ->setConstructorArgs([
                $git_plugin,
                $gerrit_server_factory,
                $driver_factory,
                $this->createMock(GitRepositoryManager::class),
                $this->createMock(Git_SystemEventManager::class),
                $this->createMock(Git_Driver_Gerrit_UserAccountManager::class),
                $factory,
                $this->user_manager,
                $this->project_manager,
                $http_request,
                $this->project_creator,
                $template_factory,
                $this->git_permissions_manager,
                $url_manager,
                new NullLogger(),
                $this->createMock(Git_Driver_Gerrit_ProjectCreatorStatus::class),
                $can_migrate_checker,
                $this->createMock(FineGrainedUpdater::class),
                $this->createMock(FineGrainedPermissionFactory::class),
                $this->createMock(FineGrainedRetriever::class),
                $this->createMock(FineGrainedPermissionSaver::class),
                $this->createMock(DefaultFineGrainedPermissionFactory::class),
                $this->createMock(FineGrainedPermissionDestructor::class),
                $this->createMock(FineGrainedRepresentationBuilder::class),
                $this->createMock(HistoryValueFormatter::class),
                $this->createMock(PermissionChangesDetector::class),
                $this->createMock(TemplatePermissionsUpdater::class),
                $this->createMock(ProjectHistoryDao::class),
                new DefaultBranchUpdater(new DefaultBranchUpdateExecutorStub()),
                $this->createMock(DescriptionUpdater::class),
                $this->createMock(RegexpFineGrainedRetriever::class),
                $this->createMock(RegexpFineGrainedEnabler::class),
                $this->createMock(RegexpFineGrainedDisabler::class),
                $this->createMock(RegexpPermissionFilter::class),
                $this->createMock(UsersToNotifyDao::class),
                $this->createMock(UgroupsToNotifyDao::class),
                $this->createMock(UGroupManager::class),
                $this->createMock(HeaderRenderer::class),
                $this->createMock(VerifyArtifactClosureIsAllowed::class),
                $this->createMock(ConfigureAllowArtifactClosure::class),
            ])
            ->onlyMethods(['addAction', 'addError', 'redirect'])
            ->getMock();

        $git->setRequest($request);
        $git->setUserManager($this->user_manager);
        $git->setFactory($factory);

        return $git;
    }

    private function assertItIsForbiddenForNonProjectAdmins(GitRepositoryFactory $factory): void
    {
        $this->user_manager->method('getCurrentUser')->willReturn($this->user);
        $request         = new HTTPRequest();
        $request->params = ['repo_id' => $this->repo_id];

        $git = $this->getGitDisconnect($request, $factory);

        $git->expects($this->once())->method('addError');
        $git->expects($this->once())->method('redirect')->with('/plugins/git/' . $this->project_unix_name . '/');

        $git->request();
    }

    private function assertItNeedsAValidRepoId(GitRepositoryFactory $factory): void
    {
        $this->user_manager->method('getCurrentUser')->willReturn($this->admin);
        $request = new HTTPRequest();
        $repo_id = 999;
        $request->set('repo_id', $repo_id);

        $git = $this->getGitDisconnect($request, $factory);

        $git->expects($this->once())->method('addError');
        $git->expects(self::never())->method('addAction');

        $git->expects($this->once())->method('redirect')->with('/plugins/git/' . $this->project_unix_name . '/');
        $git->request();
    }

    public function testItDispatchToDisconnectFromGerritWithRepoManagementView(): void
    {
        $this->user_manager->method('getCurrentUser')->willReturn($this->admin);
        $request         = new HTTPRequest();
        $request->params = ['repo_id' => $this->repo_id];
        $repo            = GitRepositoryTestBuilder::aProjectRepository()->build();
        $factory         = $this->createMock(GitRepositoryFactory::class);
        $factory->expects($this->once())->method('getRepositoryById')->willReturn($repo);
        $git     = $this->getGitDisconnect($request, $factory);
        $matcher = $this->exactly(2);

        $git->expects($matcher)->method('addAction')->willReturnCallback(function (...$parameters) use ($matcher, $repo) {
            if ($matcher->numberOfInvocations() === 1) {
                self::assertSame('disconnectFromGerrit', $parameters[0]);
                self::assertSame([$repo], $parameters[1]);
            }
            if ($matcher->numberOfInvocations() === 2) {
                self::assertSame('redirectToRepoManagement', $parameters[0]);
            }
        });
        $git->request();
    }

    public function testItIsForbiddenForNonProjectAdmins(): void
    {
        $factory = $this->createMock(GitRepositoryFactory::class);
        $factory->expects($this->once())->method('getRepositoryById')->willReturn(null);

        $this->assertItIsForbiddenForNonProjectAdmins($factory);
    }

    public function testItNeedsAValidRepoId(): void
    {
        $factory = $this->createMock(GitRepositoryFactory::class);
        $factory->expects($this->once())->method('getRepositoryById')->willReturn(null);

        $this->assertItNeedsAValidRepoId($factory);
    }

    private function getGitDisconnect(HTTPRequest $request, GitRepositoryFactory $factory): Git&MockObject
    {
        $git = $this->getGit($request, $factory);
        $git->setAction('disconnect_gerrit');
        return $git;
    }

    public function testItDispatchesToMigrateToGerritWithRepoManagementView(): void
    {
        ForgeConfig::set('sys_auth_type', ForgeConfig::AUTH_TYPE_LDAP);
        $factory = $this->createMock(GitRepositoryFactory::class);
        $factory->expects($this->once())->method('getRepositoryById')->willReturn($this->repository);

        $this->user_manager->method('getCurrentUser')->willReturn($this->admin);
        $request            = new HTTPRequest();
        $server_id          = 111;
        $gerrit_template_id = 'default';

        $request->set('repo_id', $this->repo_id);
        $request->set('remote_server_id', $server_id);
        $request->set('gerrit_template_id', $gerrit_template_id);

        $git     = $this->getGitMigrate($request, $factory);
        $matcher = $this->exactly(2);

        $git->expects($matcher)->method('addAction')->willReturnCallback(function (...$parameters) use ($matcher, $server_id, $gerrit_template_id) {
            if ($matcher->numberOfInvocations() === 1) {
                self::assertSame('migrateToGerrit', $parameters[0]);
                self::assertSame([$this->repository, $server_id, $gerrit_template_id, $this->admin], $parameters[1]);
            }
            if ($matcher->numberOfInvocations() === 2) {
                self::assertSame('redirectToRepoManagementWithMigrationAccessRightInformation', $parameters[0]);
            }
        });

        $git->request();
    }

    public function testItNeedsAValidServerId(): void
    {
        ForgeConfig::set('sys_auth_type', ForgeConfig::AUTH_TYPE_LDAP);
        $factory = $this->createMock(GitRepositoryFactory::class);
        $factory->expects($this->once())->method('getRepositoryById')->willReturn($this->repository);

        $this->user_manager->method('getCurrentUser')->willReturn($this->admin);
        $request = new HTTPRequest();
        $request->set('repo_id', $this->repo_id);
        $not_valid = 'a_string';
        $request->set('remote_server_id', $not_valid);

        $git = $this->getGitMigrate($request, $factory);

        $git->expects($this->once())->method('addError');
        $git->expects(self::never())->method('addAction');
        $git->expects($this->once())->method('redirect')->with('/plugins/git/' . $this->project_unix_name . '/');

        $git->request();
    }

    public function testItForbidsGerritMigrationIfTuleapIsNotConnectedToLDAP(): void
    {
        $factory = $this->createMock(GitRepositoryFactory::class);
        $factory->expects($this->once())->method('getRepositoryById')->willReturn($this->repository);
        ForgeConfig::set('sys_auth_type', 'not_ldap');
        $this->user_manager->method('getCurrentUser')->willReturn($this->admin);
        $request   = new HTTPRequest();
        $server_id = 111;
        $request->set('repo_id', $this->repo_id);
        $request->set('remote_server_id', $server_id);
        $git = $this->getGitMigrate($request, $factory);

        $git->expects(self::never())->method('addAction');
        $git->method('addError');
        $git->expects($this->once())->method('redirect')->with('/plugins/git/' . $this->project_unix_name . '/');

        $git->request();
    }

    public function testItAllowsGerritMigrationIfTuleapIsConnectedToLDAP(): void
    {
        ForgeConfig::set('sys_auth_type', ForgeConfig::AUTH_TYPE_LDAP);
        $factory = $this->createMock(GitRepositoryFactory::class);
        $factory->expects($this->once())->method('getRepositoryById')->willReturn($this->repository);
        $this->user_manager->method('getCurrentUser')->willReturn($this->admin);

        $template_id = 3;
        $template    = $this->createMock(Git_Driver_Gerrit_Template_Template::class);
        $template->method('getId')->willReturn($template_id);
        $template_factory = $this->createMock(Git_Driver_Gerrit_Template_TemplateFactory::class);
        $template_factory->method('getTemplatesAvailableForRepository')->willReturn([$template]);
        $template_factory->method('getTemplate')->with($template_id)->willReturn($template);

        $request   = new HTTPRequest();
        $server_id = 111;
        $request->set('repo_id', $this->repo_id);
        $request->set('remote_server_id', $server_id);
        $request->set('gerrit_template_id', $template_id);
        $request->set('group_id', $this->group_id);
        $git = $this->getGitMigrate($request, $factory, $template_factory);

        $git->expects(self::atLeastOnce())->method('addAction');
        $git->expects(self::never())->method('redirect');

        $git->request();
    }

    private function getGitMigrate(
        HTTPRequest $request,
        GitRepositoryFactory $factory,
        ?Git_Driver_Gerrit_Template_TemplateFactory $template_factory = null,
    ): Git&MockObject {
        $git = $this->getGit($request, $factory, $template_factory);
        $git->setAction('migrate_to_gerrit');
        return $git;
    }
}
