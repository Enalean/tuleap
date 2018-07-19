<?php
/**
 * Copyright (c) Enalean, 2012 - 2018. All Rights Reserved.
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

require_once 'bootstrap.php';

Mock::generate('PFUser');
Mock::generate('UserManager');
Mock::generate('Project');
Mock::generate('ProjectManager');
Mock::generate('GitRepositoryFactory');

class GitTest extends TuleapTestCase  {

    public function testTheDelRouteExecutesDeleteRepositoryWithTheIndexView() {
        $usermanager = new MockUserManager();
        $request     = aRequest()->with('repo_id', 1)->build();

        $git = TestHelper::getPartialMock('Git', array('definePermittedActions', '_informAboutPendingEvents', 'addAction', 'addView', 'checkSynchronizerToken'));
        $git->setRequest($request);
        $git->setUserManager($usermanager);
        $git->setAction('del');
        $git->setPermittedActions(array('del'));

        $repository = mock('GitRepository');
        $factory    = stub('GitRepositoryFactory')->getRepositoryById()->returns($repository);
        $git->setFactory($factory);

        $git->expectOnce('addAction', array('deleteRepository', '*'));
        $git->expectOnce('addView', array('index'));

        $git->request();
    }

    public function testDispatchToForkRepositoriesIfRequestsPersonal() {
        $git = TestHelper::getPartialMock('Git', array('_doDispatchForkRepositories', 'addView'));
        $request = new Codendi_Request(array('choose_destination' => 'personal'));
        $git->setRequest($request);
        $git->expectOnce('_doDispatchForkRepositories');

        $factory = new MockGitRepositoryFactory();
        $git->setFactory($factory);

        $user = mock('PFUser');
        $user->setReturnValue('isMember', true);
        $git->user = $user;

        $git->_dispatchActionAndView('do_fork_repositories', null, null, null, null);

    }

    public function testDispatchToForkRepositoriesIfRequestsPersonalAndNonMember() {
        $git = TestHelper::getPartialMock('Git', array('_doDispatchForkRepositories', 'addView'));
        $request = new Codendi_Request(array('choose_destination' => 'personal'));
        $git->setRequest($request);
        $git->expectNever('_doDispatchForkRepositories');

        $factory = new MockGitRepositoryFactory();
        $git->setFactory($factory);

        $user = mock('PFUser');
        $user->setReturnValue('isMember', false);
        $git->user = $user;

        $git->_dispatchActionAndView('do_fork_repositories', null, null, null, null);

    }

    public function testDispatchToForkCrossProjectIfRequestsProject() {
        $git = TestHelper::getPartialMock('Git', array('_doDispatchForkCrossProject', 'addView'));
        $request = new Codendi_Request(array('choose_destination' => 'project'));
        $git->setRequest($request);

        $factory = new MockGitRepositoryFactory();
        $git->setFactory($factory);

        $user = mock('PFUser');
        $user->setReturnValue('isMember', true);
        $git->user = $user;

        $git->expectOnce('_doDispatchForkCrossProject');
        $git->_dispatchActionAndView('do_fork_repositories', null, null, null, null);

    }
}

abstract class Git_RouteBaseTestCase extends TuleapTestCase {

    protected $repo_id           = 999;
    protected $group_id          = 101;
    protected $project_unix_name = 'gitproject';
    protected $repository;

    public function setUp() {
        parent::setUp();
        $this->user         = mock('PFUser');
        $this->admin        = mock('PFUser');
        $this->user_manager = mock('UserManager');
        $this->project_manager  = mock('ProjectManager');
        $this->project_creator  = mock('Git_Driver_Gerrit_ProjectCreator');
        $this->template_factory = mock('Git_Driver_Gerrit_Template_TemplateFactory');
        $this->git_permissions_manager = mock('GitPermissionsManager');
        $this->repository = aGitRepository()->withProject(mock('Project'))->build();

        stub($this->template_factory)->getTemplatesAvailableForRepository()->returns(array());

        $this->previous_request_uri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '/plugins/tests/';

        $project = mock('Project');
        stub($project)->getId()->returns($this->group_id);
        stub($project)->getUnixNameLowerCase()->returns($this->project_unix_name);

        stub($this->project_manager)->getProject()->returns($project);
        stub($this->project_creator)->checkTemplateIsAvailableForProject()->returns(true);
        stub($this->git_permissions_manager)->userIsGitAdmin($this->admin, $project)->returns(true);

        $_SERVER['REQUEST_URI'] = '/plugins/tests/';
    }

    public function tearDown() {
        $_SERVER['REQUEST_URI'] = $this->previous_request_uri;
    }

    protected function getGit($request, $factory, $template_factory = null) {
        $template_factory = $template_factory?$template_factory:$this->template_factory;

        $git_plugin            = stub('GitPlugin')->areFriendlyUrlsActivated()->returns(false);
        $url_manager           = new Git_GitRepositoryUrlManager($git_plugin);
        $server                = mock('Git_RemoteServer_GerritServer');
        $gerrit_server_factory = stub('Git_RemoteServer_GerritServerFactory')->getServerById()->returns($server);
        $can_migrate_checker   = stub('Tuleap\Git\GerritCanMigrateChecker')->canMigrate()->returns(true);

        $git                   = partial_mock(
            'Git',
            array('_informAboutPendingEvents', 'addAction', 'addView', 'addError', 'checkSynchronizerToken', 'redirect'),
            array(
                mock('GitPlugin'),
                $gerrit_server_factory,
                stub('Git_Driver_Gerrit_GerritDriverFactory')->getDriver()->returns(mock('Git_Driver_Gerrit')),
                mock('GitRepositoryManager'),
                mock('Git_SystemEventManager'),
                mock('Git_Driver_Gerrit_UserAccountManager'),
                mock('GitRepositoryFactory'),
                $this->user_manager,
                $this->project_manager,
                aRequest()->with('group_id', $this->group_id)->build(),
                $this->project_creator,
                $template_factory,
                $this->git_permissions_manager,
                $url_manager,
                mock('Logger'),
                mock('Git_Mirror_MirrorDataMapper'),
                mock('Git_Driver_Gerrit_ProjectCreatorStatus'),
                $can_migrate_checker,
                mock('Tuleap\Git\Permissions\FineGrainedUpdater'),
                mock('Tuleap\Git\Permissions\FineGrainedPermissionFactory'),
                mock('Tuleap\Git\Permissions\FineGrainedRetriever'),
                mock('Tuleap\Git\Permissions\FineGrainedPermissionSaver'),
                mock('Tuleap\Git\Permissions\DefaultFineGrainedPermissionFactory'),
                mock('Tuleap\Git\Permissions\FineGrainedPermissionDestructor'),
                mock('Tuleap\Git\Permissions\FineGrainedRepresentationBuilder'),
                mock('Tuleap\Git\Permissions\HistoryValueFormatter'),
                mock('Tuleap\Git\Permissions\PermissionChangesDetector'),
                mock('Tuleap\Git\Permissions\TemplatePermissionsUpdater'),
                mock('ProjectHistoryDao'),
                mock('Tuleap\Git\Repository\DescriptionUpdater'),
                mock('Tuleap\Git\History\GitPhpAccessLogger'),
                mock('Tuleap\Git\Gitolite\VersionDetector'),
                mock('Tuleap\Git\Permissions\RegexpFineGrainedRetriever'),
                mock('Tuleap\Git\Permissions\RegexpFineGrainedEnabler'),
                mock('Tuleap\Git\Permissions\RegexpFineGrainedDisabler'),
                mock('Tuleap\Git\Permissions\RegexpPermissionFilter'),
                mock('Tuleap\Git\Notifications\UsersToNotifyDao'),
                mock('Tuleap\Git\Notifications\UgroupsToNotifyDao'),
                mock('UGroupManager')
            )
        );
        $git->setRequest($request);
        $git->setUserManager($this->user_manager);
        $git->setFactory($factory);

        return $git;
    }

    protected function assertItIsForbiddenForNonProjectAdmins($factory)
    {
        stub($this->user_manager)->getCurrentUser()->returns($this->user);
        $request = aRequest()->with('repo_id', $this->repo_id)->build();

        $git = $this->getGit($request, $factory);

        $git->expectOnce('addError', array('*'));
        $git->expectNever('addAction');
        $git->expectOnce('redirect', array('/plugins/git/' . $this->project_unix_name . '/'));

        $git->request();
    }

    protected function assertItNeedsAValidRepoId($factory) {
        stub($this->user_manager)->getCurrentUser()->returns($this->admin);
        $request     = new HTTPRequest();
        $repo_id     = 999;
        $request->set('repo_id', $repo_id);

        $git = $this->getGit($request, $factory);

        $git->expectOnce('addError', array('*'));
        $git->expectNever('addAction');

        $git->expectOnce('redirect', array('/plugins/git/' . $this->project_unix_name . '/'));
        $git->request();
    }
}

class Git_DisconnectFromGerritRouteTest extends Git_RouteBaseTestCase {

    public function itDispatchTo_disconnectFromGerrit_withRepoManagementView() {
        stub($this->user_manager)->getCurrentUser()->returns($this->admin);
        $request = aRequest()->with('repo_id', $this->repo_id)->build();
        $repo    = mock('GitRepository');
        $factory = stub('GitRepositoryFactory')->getRepositoryById()->once()->returns($repo);
        $git = $this->getGit($request, $factory);

        expect($git)->addAction('disconnectFromGerrit', array($repo))->at(0);
        expect($git)->addAction('redirectToRepoManagement', '*')->at(1);
        $git->request();
    }

    public function itIsForbiddenForNonProjectAdmins() {
        $factory = stub('GitRepositoryFactory')->getRepositoryById()->once()->returns(null);

        $this->assertItIsForbiddenForNonProjectAdmins($factory);
    }

    public function itNeedsAValidRepoId() {
        $factory = stub('GitRepositoryFactory')->getRepositoryById()->once()->returns(null);

        $this->assertItNeedsAValidRepoId($factory);
    }

    protected function getGit($request, $factory) {
        $git = parent::getGit($request, $factory);
        $git->setAction('disconnect_gerrit');
        return $git;
    }
}

class Gittest_MigrateToGerritRouteTest extends Git_RouteBaseTestCase
{
    public function setUp()
    {
        parent::setUp();
        ForgeConfig::store();
        ForgeConfig::set('sys_auth_type', ForgeConfig::AUTH_TYPE_LDAP);

        $this->factory = stub('GitRepositoryFactory')->getRepositoryById()->once()->returns($this->repository);
    }

    public function tearDown()
    {
        ForgeConfig::restore();
        parent::tearDown();
    }

    public function itDispatchesTo_migrateToGerrit_withRepoManagementView()
    {
        stub($this->user_manager)->getCurrentUser()->returns($this->admin);
        $request            = new HTTPRequest();
        $repo_id            = 999;
        $server_id          = 111;
        $gerrit_template_id = 'default';

        $request->set('repo_id', $repo_id);
        $request->set('remote_server_id', $server_id);
        $request->set('gerrit_template_id', $gerrit_template_id);

        $git     = $this->getGit($request, $this->factory);

        expect($git)->addAction('migrateToGerrit', array($this->repository, $server_id, $gerrit_template_id, $this->admin))->at(0);
        expect($git)->addAction('redirectToRepoManagementWithMigrationAccessRightInformation', '*')->at(1);

        $git->request();
    }

    public function itIsForbiddenForNonProjectAdmins() {
        $this->assertItIsForbiddenForNonProjectAdmins($this->factory);
    }

    public function itNeedsAValidRepoId() {
        $this->assertItNeedsAValidRepoId($this->factory);
    }

    public function itNeedsAValidServerId()
    {
        stub($this->user_manager)->getCurrentUser()->returns($this->admin);
        $request     = new HTTPRequest();
        $repo_id     = 999;
        $request->set('repo_id', $repo_id);
        $not_valid   = 'a_string';
        $request->set('remote_server_id', $not_valid);

        $git = $this->getGit($request, $this->factory);

        $git->expectOnce('addError', array('*'));
        $git->expectNever('addAction');

        $git->expectOnce('redirect', array('/plugins/git/' . $this->project_unix_name . '/'));

        $git->request();
    }

    public function itForbidsGerritMigrationIfTuleapIsNotConnectedToLDAP()
    {
        ForgeConfig::set('sys_auth_type', 'not_ldap');
        stub($this->user_manager)->getCurrentUser()->returns($this->admin);
        $request     = new HTTPRequest();
        $repo_id     = 999;
        $server_id   = 111;
        $request->set('repo_id', $repo_id);
        $request->set('remote_server_id', $server_id);
        $git = $this->getGit($request, $this->factory);
        $git->expectNever('addAction');
        $git->expectOnce('redirect', array('/plugins/git/' . $this->project_unix_name . '/'));

        $git->request();
    }

    public function itAllowsGerritMigrationIfTuleapIsConnectedToLDAP()
    {
        ForgeConfig::set('sys_auth_type', ForgeConfig::AUTH_TYPE_LDAP);
        stub($this->user_manager)->getCurrentUser()->returns($this->admin);

        $template_id      = 3;
        $template         = stub('Git_Driver_Gerrit_Template_Template')->getId()->returns($template_id);
        $template_factory = stub('Git_Driver_Gerrit_Template_TemplateFactory')->getTemplatesAvailableForRepository()->returns(array($template));
        stub($template_factory)->getTemplate($template_id)->returns($template);

        $request     = aRequest()->with('group_id', $this->group_id)->build();
        $repo_id     = 999;
        $server_id   = 111;
        $request->set('repo_id', $repo_id);
        $request->set('remote_server_id', $server_id);
        $request->set('gerrit_template_id', $template_id);
        $git = $this->getGit($request, $this->factory, $template_factory);
        $git->expectAtLeastOnce('addAction');
        $git->expectNever('redirect');

        $git->request();
    }

    protected function getGit($request, $factory, $template_factory = null) {
        $git = parent::getGit($request, $factory, $template_factory);
        $git->setAction('migrate_to_gerrit');
        return $git;
    }
}
