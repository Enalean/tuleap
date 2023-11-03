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

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Tuleap\ForgeConfigSandbox;
use Tuleap\Git\Notifications\UgroupsToNotifyDao;
use Tuleap\Git\Notifications\UsersToNotifyDao;

//phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
final class GitGerritRouteTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;
    use ForgeConfigSandbox;
    use \Tuleap\GlobalLanguageMock;

    protected $repo_id           = 999;
    protected $group_id          = 101;
    protected $project_unix_name = 'gitproject';
    protected $repository;
    /**
     * @var PFUser&\Mockery\MockInterface
     */
    private $user;
    /**
     * @var PFUser&\Mockery\MockInterface
     */
    private $admin;
    /**
     * @var UserManager&\Mockery\MockInterface
     */
    private $user_manager;
    /**
     * @varProjectManager&\Mockery\MockInterface
     */
    private $project_manager;
    /**
     * @var Git_Driver_Gerrit_ProjectCreator&\Mockery\MockInterface
     */
    private $project_creator;
    /**
     * @var Git_Driver_Gerrit_Template_TemplateFactory&\Mockery\MockInterface
     */
    private $template_factory;
    /**
     * @var GitPermissionsManager&\Mockery\MockInterface
     */
    private $git_permissions_manager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user                    = \Mockery::spy(\PFUser::class);
        $this->admin                   = \Mockery::spy(\PFUser::class);
        $this->user_manager            = \Mockery::spy(\UserManager::class);
        $this->project_manager         = \Mockery::spy(\ProjectManager::class);
        $this->project_creator         = \Mockery::spy(\Git_Driver_Gerrit_ProjectCreator::class);
        $this->template_factory        = \Mockery::spy(\Git_Driver_Gerrit_Template_TemplateFactory::class);
        $this->git_permissions_manager = \Mockery::spy(\GitPermissionsManager::class);
        $this->repository              = Mockery::mock(GitRepository::class)
            ->shouldReceive('getProject')
            ->andReturn(\Mockery::spy(\Project::class))
            ->getMock();

        $this->template_factory->shouldReceive('getTemplatesAvailableForRepository')->andReturns([]);

        $project = \Mockery::spy(\Project::class);
        $project->shouldReceive('getId')->andReturns($this->group_id);
        $project->shouldReceive('getUnixNameLowerCase')->andReturns($this->project_unix_name);

        $this->project_manager->shouldReceive('getProject')->andReturns($project);
        $this->project_creator->shouldReceive('checkTemplateIsAvailableForProject')->andReturns(true);
        $this->git_permissions_manager->shouldReceive('userIsGitAdmin')->with($this->admin, $project)->andReturns(true);

        $_SERVER['REQUEST_URI']    = '/plugins/tests/';
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $GLOBALS['Response']       = Mockery::spy(\Response::class);

        $system_event_manager = Mockery::mock(SystemEventManager::class);
        $sys_dao              = Mockery::mock(SystemEventDao::class);
        $sys_dao->shouldReceive('searchWithParam')->andReturn([]);
        $system_event_manager->shouldReceive('_getDao')->andReturn($sys_dao);

        SystemEventManager::setInstance($system_event_manager);
    }

    protected function tearDown(): void
    {
        SystemEventManager::clearInstance();
        unset($_SERVER['REQUEST_URI'], $_SERVER['REQUEST_METHOD'], $GLOBALS['_SESSION']);
        unset($GLOBALS['Response']);

        parent::tearDown();
    }

    private function getGit($request, $factory, $template_factory = null): Git
    {
        $template_factory = $template_factory ? $template_factory : $this->template_factory;

        $git_plugin = \Mockery::mock(GitPlugin::class);
        $git_plugin->shouldReceive('areFriendlyUrlsActivated')->andReturns(false);
        $url_manager           = new Git_GitRepositoryUrlManager($git_plugin);
        $server                = \Mockery::spy(\Git_RemoteServer_GerritServer::class);
        $gerrit_server_factory = \Mockery::spy(\Git_RemoteServer_GerritServerFactory::class)->shouldReceive('getServerById')->andReturns($server)->getMock();
        $can_migrate_checker   = \Mockery::spy(\Tuleap\Git\RemoteServer\GerritCanMigrateChecker::class)->shouldReceive('canMigrate')->andReturns(true)->getMock();

        $gerrit_driver = Mockery::mock(Git_Driver_Gerrit::class);
        $gerrit_driver->shouldReceive('doesTheProjectExist')->andReturn(false);
        $gerrit_driver->shouldReceive('getGerritProjectName');

        $http_request         = new HTTPRequest();
        $http_request->params = ['group_id' => $this->group_id];
        $git                  = Mockery::mock(
            Git::class,
            [
                \Mockery::mock(GitPlugin::class),
                $gerrit_server_factory,
                \Mockery::spy(\Git_Driver_Gerrit_GerritDriverFactory::class)->shouldReceive('getDriver')->andReturns($gerrit_driver)->getMock(),
                \Mockery::spy(\GitRepositoryManager::class),
                \Mockery::spy(\Git_SystemEventManager::class),
                \Mockery::spy(\Git_Driver_Gerrit_UserAccountManager::class),
                $factory,
                $this->user_manager,
                $this->project_manager,
                $http_request,
                $this->project_creator,
                $template_factory,
                $this->git_permissions_manager,
                $url_manager,
                \Mockery::spy(\Psr\Log\LoggerInterface::class),
                \Mockery::spy(\Git_Driver_Gerrit_ProjectCreatorStatus::class),
                $can_migrate_checker,
                \Mockery::spy(\Tuleap\Git\Permissions\FineGrainedUpdater::class),
                \Mockery::spy(\Tuleap\Git\Permissions\FineGrainedPermissionFactory::class),
                \Mockery::spy(\Tuleap\Git\Permissions\FineGrainedRetriever::class),
                \Mockery::spy(\Tuleap\Git\Permissions\FineGrainedPermissionSaver::class),
                \Mockery::spy(\Tuleap\Git\Permissions\DefaultFineGrainedPermissionFactory::class),
                \Mockery::spy(\Tuleap\Git\Permissions\FineGrainedPermissionDestructor::class),
                \Mockery::spy(\Tuleap\Git\Permissions\FineGrainedRepresentationBuilder::class),
                \Mockery::spy(\Tuleap\Git\Permissions\HistoryValueFormatter::class),
                \Mockery::spy(\Tuleap\Git\Permissions\PermissionChangesDetector::class),
                \Mockery::spy(\Tuleap\Git\Permissions\TemplatePermissionsUpdater::class),
                \Mockery::spy(\ProjectHistoryDao::class),
                new \Tuleap\Git\DefaultBranch\DefaultBranchUpdater(new \Tuleap\Git\Tests\Stub\DefaultBranch\DefaultBranchUpdateExecutorStub()),
                \Mockery::spy(\Tuleap\Git\Repository\DescriptionUpdater::class),
                \Mockery::spy(\Tuleap\Git\Permissions\RegexpFineGrainedRetriever::class),
                \Mockery::spy(\Tuleap\Git\Permissions\RegexpFineGrainedEnabler::class),
                \Mockery::spy(\Tuleap\Git\Permissions\RegexpFineGrainedDisabler::class),
                \Mockery::spy(\Tuleap\Git\Permissions\RegexpPermissionFilter::class),
                Mockery::mock(UsersToNotifyDao::class),
                Mockery::mock(UgroupsToNotifyDao::class),
                \Mockery::spy(\UGroupManager::class),
                \Mockery::spy(\Tuleap\Git\GitViews\Header\HeaderRenderer::class),
                \Mockery::spy(\Tuleap\Git\Repository\Settings\ArtifactClosure\VerifyArtifactClosureIsAllowed::class),
                \Mockery::spy(\Tuleap\Git\Repository\Settings\ArtifactClosure\ConfigureAllowArtifactClosure::class),
            ]
        )
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();

        $git->setRequest($request);
        $git->setUserManager($this->user_manager);
        $git->setFactory($factory);

        return $git;
    }

    protected function assertItIsForbiddenForNonProjectAdmins($factory)
    {
        $this->user_manager->shouldReceive('getCurrentUser')->andReturns($this->user);
        $request         = new HTTPRequest();
        $request->params = ['repo_id' => $this->repo_id];

        $git = $this->getGitDisconnect($request, $factory);

        $git->shouldReceive('addError')->with(Mockery::any())->once();
        $git->shouldReceive('redirect')->with('/plugins/git/' . $this->project_unix_name . '/')->once();

        $git->request();
    }

    protected function assertItNeedsAValidRepoId($factory)
    {
        $this->user_manager->shouldReceive('getCurrentUser')->andReturns($this->admin);
        $request = new HTTPRequest();
        $repo_id = 999;
        $request->set('repo_id', $repo_id);

        $git = $this->getGitDisconnect($request, $factory);

        $git->shouldReceive('addError')->with(Mockery::any())->once();
        $git->shouldReceive('addAction')->never();

        $git->shouldReceive('redirect')->with('/plugins/git/' . $this->project_unix_name . '/')->once();
        $git->request();
    }

    //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    public function testItDispatchToDisconnectFromGerritWithRepoManagementView(): void
    {
        $this->user_manager->shouldReceive('getCurrentUser')->andReturns($this->admin);
        $request         = new HTTPRequest();
        $request->params = ['repo_id' => $this->repo_id];
        $repo            = \Mockery::spy(\GitRepository::class);
        $factory         = \Mockery::spy(\GitRepositoryFactory::class)->shouldReceive('getRepositoryById')->once()->andReturns($repo)->getMock();
        $git             = $this->getGitDisconnect($request, $factory);

        $git->shouldReceive('addAction')->with('disconnectFromGerrit', [$repo])->ordered();
        $git->shouldReceive('addAction')->with('redirectToRepoManagement', \Mockery::any())->ordered();
        $git->request();
    }

    public function testItIsForbiddenForNonProjectAdmins()
    {
        $factory = \Mockery::spy(\GitRepositoryFactory::class)->shouldReceive('getRepositoryById')->once()->andReturns(null)->getMock();

        $this->assertItIsForbiddenForNonProjectAdmins($factory);
    }

    public function testItNeedsAValidRepoId()
    {
        $factory = \Mockery::spy(\GitRepositoryFactory::class)->shouldReceive('getRepositoryById')->once()->andReturns(null)->getMock();

        $this->assertItNeedsAValidRepoId($factory);
    }

    protected function getGitDisconnect($request, $factory, $template_factory = null): Git
    {
        $git = $this->getGit($request, $factory);
        $git->setAction('disconnect_gerrit');
        return $git;
    }

    //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    public function testItDispatchesTo_migrateToGerrit_withRepoManagementView()
    {
        ForgeConfig::set('sys_auth_type', ForgeConfig::AUTH_TYPE_LDAP);
        $factory = \Mockery::spy(\GitRepositoryFactory::class)->shouldReceive('getRepositoryById')->once()->andReturns($this->repository)->getMock();

        $this->user_manager->shouldReceive('getCurrentUser')->andReturns($this->admin);
        $request            = new HTTPRequest();
        $repo_id            = 999;
        $server_id          = 111;
        $gerrit_template_id = 'default';

        $request->set('repo_id', $repo_id);
        $request->set('remote_server_id', $server_id);
        $request->set('gerrit_template_id', $gerrit_template_id);

        $git = $this->getGitMigrate($request, $factory);

        $this->repository->shouldReceive('getId')->andReturn($repo_id);

        $git->shouldReceive('addAction')->with('migrateToGerrit', [$this->repository, $server_id, $gerrit_template_id, $this->admin])->ordered();
        $git->shouldReceive('addAction')->with('redirectToRepoManagementWithMigrationAccessRightInformation', \Mockery::any())->ordered();

        $git->request();
    }

    public function testItNeedsAValidServerId()
    {
        ForgeConfig::set('sys_auth_type', ForgeConfig::AUTH_TYPE_LDAP);
        $factory = \Mockery::spy(\GitRepositoryFactory::class)->shouldReceive('getRepositoryById')->once()->andReturns($this->repository)->getMock();

        $this->user_manager->shouldReceive('getCurrentUser')->andReturns($this->admin);
        $request = new HTTPRequest();
        $repo_id = 999;
        $request->set('repo_id', $repo_id);
        $not_valid = 'a_string';
        $request->set('remote_server_id', $not_valid);

        $this->repository->shouldReceive('getId')->andReturn($repo_id);

        $git = $this->getGitMigrate($request, $factory);

        $git->shouldReceive('addError')->with(Mockery::any())->once();
        $git->shouldReceive('addAction')->never();
        $git->shouldReceive('redirect')->with('/plugins/git/' . $this->project_unix_name . '/')->once();

        $git->request();
    }

    public function testItForbidsGerritMigrationIfTuleapIsNotConnectedToLDAP()
    {
        $factory = \Mockery::spy(\GitRepositoryFactory::class)->shouldReceive('getRepositoryById')->once()->andReturns($this->repository)->getMock();
        ForgeConfig::set('sys_auth_type', 'not_ldap');
        $this->user_manager->shouldReceive('getCurrentUser')->andReturns($this->admin);
        $request   = new HTTPRequest();
        $repo_id   = 999;
        $server_id = 111;
        $request->set('repo_id', $repo_id);
        $request->set('remote_server_id', $server_id);
        $git = $this->getGitMigrate($request, $factory);

        $this->repository->shouldReceive('getId')->andReturn($repo_id);

        $git->shouldReceive('addAction')->never();
        $git->shouldReceive('redirect')->with('/plugins/git/' . $this->project_unix_name . '/')->once();

        $git->request();
    }

    public function testItAllowsGerritMigrationIfTuleapIsConnectedToLDAP()
    {
        ForgeConfig::set('sys_auth_type', ForgeConfig::AUTH_TYPE_LDAP);
        $factory = \Mockery::spy(\GitRepositoryFactory::class)->shouldReceive('getRepositoryById')->once()->andReturns($this->repository)->getMock();
        $this->user_manager->shouldReceive('getCurrentUser')->andReturns($this->admin);

        $template_id      = 3;
        $template         = \Mockery::spy(\Git_Driver_Gerrit_Template_Template::class)->shouldReceive('getId')->andReturns($template_id)->getMock();
        $template_factory = \Mockery::spy(\Git_Driver_Gerrit_Template_TemplateFactory::class)->shouldReceive('getTemplatesAvailableForRepository')->andReturns([$template])->getMock();
        $template_factory->shouldReceive('getTemplate')->with($template_id)->andReturns($template);

        $request   = new HTTPRequest();
        $repo_id   = 999;
        $server_id = 111;
        $request->set('repo_id', $repo_id);
        $request->set('remote_server_id', $server_id);
        $request->set('gerrit_template_id', $template_id);
        $request->set('group_id', $this->group_id);
        $git = $this->getGitMigrate($request, $factory, $template_factory);

        $this->repository->shouldReceive('getId')->andReturn($repo_id);

        $git->shouldReceive('addAction')->atLeast(1);
        $git->shouldNotReceive('redirect');

        $git->request();
    }

    private function getGitMigrate($request, $factory, $template_factory = null)
    {
        $git = $this->getGit($request, $factory, $template_factory);
        $git->setAction('migrate_to_gerrit');
        return $git;
    }
}
