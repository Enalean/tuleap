<?php
/**
 * Copyright (c) Enalean, 2012 - 2018. All Rights Reserved.
 * Copyright (c) STMicroelectronics, 2011. All Rights Reserved.
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

require_once 'bootstrap.php';
require_once (__DIR__.'/../include/GitActions.class.php');
require_once (__DIR__.'/../include/Git.class.php');
require_once (__DIR__.'/../include/GitRepository.class.php');
require_once('common/language/BaseLanguage.class.php');
require_once('common/include/Response.class.php');

require_once 'builders/aGitRepository.php';

class GitActionsTest extends TuleapTestCase {

    function setUp() {
        parent::setUp();
        $this->setUpGlobalsMockery();
        $GLOBALS['Language']->shouldReceive('getText')->with('plugin_git', 'actions_no_repository_forked', '*')->andReturns('actions_no_repository_forked');
        $GLOBALS['Language']->shouldReceive('getText')->with('plugin_git', 'successfully_forked', '*')->andReturns('successfully_forked');

        $git_plugin        = mockery_stub(\GitPlugin::class)->areFriendlyUrlsActivated()->returns(false);
        $url_manager       = new Git_GitRepositoryUrlManager($git_plugin);

        $this->gitAction = \Mockery::mock(\GitActions::class, array(
            mock('Git'),
            mock('Git_SystemEventManager'),
            mock('GitRepositoryFactory'),
            mock('GitRepositoryManager'),
            mock('Git_RemoteServer_GerritServerFactory'),
            stub('Git_Driver_Gerrit_GerritDriverFactory')->getDriver()->returns(mock('Git_Driver_Gerrit')),
            mock('Git_Driver_Gerrit_UserAccountManager'),
            mock('Git_Driver_Gerrit_ProjectCreator'),
            mock('Git_Driver_Gerrit_Template_TemplateFactory'),
            mock('ProjectManager'),
            mock('GitPermissionsManager'),
            $url_manager,
            mock('Logger'),
            mock('Git_Mirror_MirrorDataMapper'),
            Mockery::spy(ProjectHistoryDao::class),
            mock('GitRepositoryMirrorUpdater'),
            mock('Tuleap\Git\RemoteServer\Gerrit\MigrationHandler'),
            mock('Tuleap\Git\GerritCanMigrateChecker'),
            mock('Tuleap\Git\Permissions\FineGrainedUpdater'),
            mock('Tuleap\Git\Permissions\FineGrainedPermissionSaver'),
            mock('Tuleap\Git\Permissions\FineGrainedRetriever'),
            mock('Tuleap\Git\Permissions\HistoryValueFormatter'),
            mock('Tuleap\Git\Permissions\PermissionChangesDetector'),
            mock('Tuleap\Git\Permissions\RegexpFineGrainedEnabler'),
            mock('Tuleap\Git\Permissions\RegexpFineGrainedDisabler'),
            mock('Tuleap\Git\Permissions\RegexpPermissionFilter'),
            mock('Tuleap\Git\Permissions\RegexpFineGrainedRetriever'),
            mock('Tuleap\Git\Notifications\UsersToNotifyDao'),
            mock('Tuleap\Git\Notifications\UgroupsToNotifyDao'),
            mock('UGroupManager')
        ))
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();
    }

    function testNotificationUpdatePrefixFail() {
        $this->gitAction->shouldReceive('getText')->with('actions_params_error')->andReturns('actions_params_error');
        $git = \Mockery::spy(\Git::class);
        $this->gitAction->setController($git);
        $gitRepository = \Mockery::spy(\GitRepository::class);
        $this->gitAction->shouldReceive('getGitRepository')->andReturns($gitRepository);

        $git->shouldReceive('addError')->with('actions_params_error')->once();
        $git->shouldReceive('addInfo')->never();
        $gitRepository->shouldReceive('setMailPrefix')->never();
        $gitRepository->shouldReceive('changeMailPrefix')->never();
        $this->gitAction->shouldReceive('addData')->never();

        $this->assertFalse($this->gitAction->notificationUpdatePrefix(1, null, '[new prefix]', 'a_pane'));
    }

    function testNotificationUpdatePrefixPass() {
        $this->gitAction->shouldReceive('getText')->with('mail_prefix_updated')->andReturns('mail_prefix_updated');
        $git = \Mockery::spy(\Git::class);
        $this->gitAction->setController($git);
        $gitRepository = \Mockery::spy(\GitRepository::class);
        $this->gitAction->shouldReceive('getGitRepository')->andReturns($gitRepository);

        $git->shouldReceive('addError')->never();
        $git->shouldReceive('addInfo')->with('mail_prefix_updated')->once();
        $gitRepository->shouldReceive('setMailPrefix')->once();
        $gitRepository->shouldReceive('changeMailPrefix')->once();
        $this->gitAction->shouldReceive('addData')->times(2);

        $this->assertTrue($this->gitAction->notificationUpdatePrefix(1, 1, '[new prefix]', 'a_pane'));
    }

    function testNotificationAddMailFailNoRepoId() {
        $this->gitAction->shouldReceive('getText')->with('actions_params_error')->andReturns('actions_params_error');
        $git = \Mockery::spy(\Git::class);
        $this->gitAction->setController($git);
        $gitRepository = \Mockery::spy(\GitRepository::class);
        $this->gitAction->shouldReceive('getGitRepository')->andReturns($gitRepository);

        $git->shouldReceive('addError')->with('actions_params_error')->once();
        $git->shouldReceive('addInfo')->never();

        $mails = array('john.doe@acme.com');
        $this->assertFalse($this->gitAction->notificationAddMail(1, null, $mails, 'a_pane'));
    }

    function testNotificationAddMailFailNoMails() {
        $this->gitAction->shouldReceive('getText')->with('actions_params_error')->andReturns('actions_params_error');
        $git = \Mockery::spy(\Git::class);
        $this->gitAction->setController($git);
        $gitRepository = \Mockery::spy(\GitRepository::class);
        $this->gitAction->shouldReceive('getGitRepository')->andReturns($gitRepository);

        $git->shouldReceive('addError')->with('actions_params_error')->once();
        $git->shouldReceive('addInfo')->never();

        $this->assertFalse($this->gitAction->notificationAddMail(1, 1, null, 'a_pane'));
    }

    function testNotificationAddMailFailAlreadyNotified() {
        $this->gitAction->shouldReceive('getText')->with('mail_existing', array('john.doe@acme.com'))->andReturns('mail_existing john.doe@acme.com');
        $this->gitAction->shouldReceive('getText')->with('mail_existing', array('jane.doe@acme.com'))->andReturns('mail_existing jane.doe@acme.com');
        $this->gitAction->shouldReceive('getText')->with('mail_existing', array('john.smith@acme.com'))->andReturns('mail_existing john.smith@acme.com');
        $git = \Mockery::spy(\Git::class);
        $this->gitAction->setController($git);
        $gitRepository = \Mockery::spy(\GitRepository::class);
        $gitRepository->shouldReceive('isAlreadyNotified')->andReturns(true);
        $gitRepository->shouldReceive('notificationAddMail')->with('john.doe@acme.com')->andReturns(false);
        $gitRepository->shouldReceive('notificationAddMail')->with('jane.doe@acme.com')->andReturns(false);
        $gitRepository->shouldReceive('notificationAddMail')->with('john.smith@acme.com')->andReturns(false);
        $this->gitAction->shouldReceive('getGitRepository')->andReturns($gitRepository);

        $git->shouldReceive('addError')->never();
        $git->shouldReceive('addInfo')->times(3);
        $git->shouldReceive('addInfo')->with('mail_existing john.doe@acme.com')->ordered();
        $git->shouldReceive('addInfo')->with('mail_existing jane.doe@acme.com')->ordered();
        $git->shouldReceive('addInfo')->with('mail_existing john.smith@acme.com')->ordered();

        $mails = array('john.doe@acme.com',
                       'jane.doe@acme.com',
                       'john.smith@acme.com');
        $this->assertTrue($this->gitAction->notificationAddMail(1, 1, $mails, 'a_pane'));
    }

    function testNotificationAddMailPartialPass() {
        $this->gitAction->shouldReceive('getText')->with('mail_not_added', array('john.doe@acme.com'))->andReturns('mail_not_added john.doe@acme.com');
        $this->gitAction->shouldReceive('getText')->with('mail_not_added', array('john.smith@acme.com'))->andReturns('mail_not_added john.smith@acme.com');
        $git = \Mockery::spy(\Git::class);
        $this->gitAction->setController($git);
        $gitRepository = \Mockery::spy(\GitRepository::class);
        $gitRepository->shouldReceive('isAlreadyNotified')->andReturns(false);
        $gitRepository->shouldReceive('notificationAddMail')->with('john.doe@acme.com')->andReturns(false);
        $gitRepository->shouldReceive('notificationAddMail')->with('jane.doe@acme.com')->andReturns(true);
        $gitRepository->shouldReceive('notificationAddMail')->with('john.smith@acme.com')->andReturns(false);
        $this->gitAction->shouldReceive('getGitRepository')->andReturns($gitRepository);

        $git->shouldReceive('addError')->times(2);
        $git->shouldReceive('addError')->with('mail_not_added john.doe@acme.com')->ordered();
        $git->shouldReceive('addError')->with('mail_not_added john.smith@acme.com')->ordered();
        $git->shouldReceive('addInfo')->never();

        $mails = array('john.doe@acme.com',
                       'jane.doe@acme.com',
                       'john.smith@acme.com');
        $this->assertTrue($this->gitAction->notificationAddMail(1, 1, $mails, 'a_pane'));
    }

    function testNotificationAddMailPass() {
        $this->gitAction->shouldReceive('getText')->with('mail_added')->andReturns('mail_added');
        $git = \Mockery::spy(\Git::class);
        $this->gitAction->setController($git);
        $gitRepository = \Mockery::spy(\GitRepository::class);
        $gitRepository->shouldReceive('isAlreadyNotified')->andReturns(false);
        $gitRepository->shouldReceive('notificationAddMail')->with('john.doe@acme.com')->andReturns(true);
        $gitRepository->shouldReceive('notificationAddMail')->with('jane.doe@acme.com')->andReturns(true);
        $gitRepository->shouldReceive('notificationAddMail')->with('john.smith@acme.com')->andReturns(true);
        $this->gitAction->shouldReceive('getGitRepository')->andReturns($gitRepository);

        $git->shouldReceive('addError')->never();
        $git->shouldReceive('addInfo')->with('mail_added')->once();

        $mails = array('john.doe@acme.com',
                       'jane.doe@acme.com',
                       'john.smith@acme.com');
        $this->assertTrue($this->gitAction->notificationAddMail(1, 1, $mails, 'a_pane'));
    }

    function testNotificationRemoveMailFailNoRepoId() {
        $this->gitAction->shouldReceive('getText')->with('actions_params_error')->andReturns('actions_params_error');
        $git = \Mockery::spy(\Git::class);
        $this->gitAction->setController($git);
        $gitRepository = \Mockery::spy(\GitRepository::class);
        $this->gitAction->shouldReceive('getGitRepository')->andReturns($gitRepository);

        $git->shouldReceive('addError')->with('actions_params_error')->once();
        $git->shouldReceive('addInfo')->never();

        $this->assertFalse($this->gitAction->notificationRemoveMail(1, null, 'john.doe@acme.com', 'a_pane'));
    }

    function testNotificationRemoveMailFailNoMail() {
        $this->gitAction->shouldReceive('getText')->with('actions_params_error')->andReturns('actions_params_error');
        $git = \Mockery::spy(\Git::class);
        $this->gitAction->setController($git);
        $gitRepository = \Mockery::spy(\GitRepository::class);
        $this->gitAction->shouldReceive('getGitRepository')->andReturns($gitRepository);

        $git->shouldReceive('addError')->with('actions_params_error')->once();
        $git->shouldReceive('addInfo')->never();

        $this->assertFalse($this->gitAction->notificationRemoveMail(1, 1, null, 'a_pane'));
    }

    function testNotificationRemoveMailFailMailNotRemoved() {
        $this->gitAction->shouldReceive('getText')->with('mail_not_removed', array('john.doe@acme.com'))->andReturns('mail_not_removed john.doe@acme.com');
        $git = \Mockery::spy(\Git::class);
        $this->gitAction->setController($git);
        $gitRepository = \Mockery::spy(\GitRepository::class);
        $gitRepository->shouldReceive('notificationRemoveMail')->andReturns(false);
        $this->gitAction->shouldReceive('getGitRepository')->andReturns($gitRepository);

        $git->shouldReceive('addError')->with('mail_not_removed john.doe@acme.com')->once();
        $git->shouldReceive('addInfo')->never();

        $this->assertFalse($this->gitAction->notificationRemoveMail(1, 1, array('john.doe@acme.com'), 'a_pane'));
    }

    function testNotificationRemoveMailFailMailPass() {
        $this->gitAction->shouldReceive('getText')->with('mail_removed', array('john.doe@acme.com'))->andReturns('mail_removed john.doe@acme.com');
        $git = \Mockery::spy(\Git::class);
        $this->gitAction->setController($git);
        $gitRepository = \Mockery::spy(\GitRepository::class);
        $gitRepository->shouldReceive('notificationRemoveMail')->andReturns(True);
        $this->gitAction->shouldReceive('getGitRepository')->andReturns($gitRepository);

        $git->shouldReceive('addError')->never();
        $git->shouldReceive('addInfo')->with('mail_removed john.doe@acme.com')->once();

        $this->assertTrue($this->gitAction->notificationRemoveMail(1, 1, array('john.doe@acme.com'), 'a_pane'));
    }

    function testConfirmPrivateFailNoRepoId() {
        $this->gitAction->shouldReceive('getText')->with('actions_params_error')->andReturns('actions_params_error');
        $git = \Mockery::spy(\Git::class);
        $this->gitAction->setController($git);
        $gitRepository = \Mockery::spy(\GitRepository::class);
        $this->gitAction->shouldReceive('getGitRepository')->andReturns($gitRepository);

        $git->shouldReceive('addError')->with('actions_params_error')->once();
        $git->shouldReceive('addWarn')->never();
        $gitRepository->shouldReceive('getNonMemberMails')->never();
        $gitRepository->shouldReceive('setDescription')->never();
        $gitRepository->shouldReceive('save')->never();
        $this->gitAction->shouldReceive('save')->never();

        $this->assertFalse($this->gitAction->confirmPrivate(1, null, 'private', 'desc'));
    }

    function testConfirmPrivateFailNoAccess() {
        $gitAction = \Mockery::mock(\GitActions::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $gitAction->shouldReceive('getText')->with('actions_params_error')->andReturns('actions_params_error');
        $git = \Mockery::spy(\Git::class);
        $gitAction->setController($git);
        $gitRepository = \Mockery::spy(\GitRepository::class);
        $gitAction->shouldReceive('getGitRepository')->andReturns($gitRepository);

        $git->shouldReceive('addError')->with('actions_params_error')->once();
        $git->shouldReceive('addWarn')->never();
        $gitRepository->shouldReceive('getNonMemberMails')->never();
        $gitRepository->shouldReceive('setDescription')->never();
        $gitRepository->shouldReceive('save')->never();
        $gitAction->shouldReceive('save')->never();

        $this->assertFalse($gitAction->confirmPrivate(1, 1, null, 'desc'));
    }

    function testConfirmPrivateFailNoDesc() {
        $gitAction = \Mockery::mock(\GitActions::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $gitAction->shouldReceive('getText')->with('actions_params_error')->andReturns('actions_params_error');
        $git = \Mockery::spy(\Git::class);
        $gitAction->setController($git);
        $gitRepository = \Mockery::spy(\GitRepository::class);
        $gitAction->shouldReceive('getGitRepository')->andReturns($gitRepository);

        $git->shouldReceive('addError')->with('actions_params_error')->once();
        $git->shouldReceive('addWarn')->never();
        $gitRepository->shouldReceive('getNonMemberMails')->never();
        $gitRepository->shouldReceive('setDescription')->never();
        $gitRepository->shouldReceive('save')->never();
        $gitAction->shouldReceive('save')->never();

        $this->assertFalse($gitAction->confirmPrivate(1, 1, 'private', null));
    }

    function testConfirmPrivateNotSettingToPrivate() {
        $gitAction = \Mockery::mock(\GitActions::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $git = \Mockery::spy(\Git::class);
        $gitAction->setController($git);
        $gitRepository = \Mockery::spy(\GitRepository::class);
        $gitRepository->shouldReceive('getAccess')->andReturns('public');
        $gitAction->shouldReceive('getGitRepository')->andReturns($gitRepository);

        $git->shouldReceive('addError')->never();
        $git->shouldReceive('addWarn')->never();
        $gitRepository->shouldReceive('getNonMemberMails')->never();
        $gitRepository->shouldReceive('setDescription')->never();
        $gitRepository->shouldReceive('save')->never();
        $gitAction->shouldReceive('save')->once();

        $this->assertTrue($gitAction->confirmPrivate(1, 1, 'public', 'desc'));
    }

    function testConfirmPrivateAlreadyPrivate() {
        $gitAction = \Mockery::mock(\GitActions::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $git = \Mockery::spy(\Git::class);
        $gitAction->setController($git);
        $gitRepository = \Mockery::spy(\GitRepository::class);
        $gitRepository->shouldReceive('getAccess')->andReturns('private');
        $gitAction->shouldReceive('getGitRepository')->andReturns($gitRepository);

        $git->shouldReceive('addError')->never();
        $git->shouldReceive('addWarn')->never();
        $gitRepository->shouldReceive('getNonMemberMails')->never();
        $gitRepository->shouldReceive('setDescription')->never();
        $gitRepository->shouldReceive('save')->never();
        $gitAction->shouldReceive('save')->once();

        $this->assertTrue($gitAction->confirmPrivate(1, 1, 'private', 'desc'));
    }

    function testConfirmPrivateNoMailsToDelete() {
        $gitAction = \Mockery::mock(\GitActions::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $git = \Mockery::spy(\Git::class);
        $gitAction->setController($git);
        $gitRepository = \Mockery::spy(\GitRepository::class);
        $gitRepository->shouldReceive('getAccess')->andReturns('public');
        $gitAction->shouldReceive('getGitRepository')->andReturns($gitRepository);

        $git->shouldReceive('addError')->never();
        $git->shouldReceive('addWarn')->never();
        $gitRepository->shouldReceive('getNonMemberMails')->once()->andReturns(array());
        $gitRepository->shouldReceive('setDescription')->never();
        $gitRepository->shouldReceive('save')->never();
        $gitAction->shouldReceive('save')->once();

        $this->assertTrue($gitAction->confirmPrivate(1, 1, 'private', 'desc'));
    }

    function testConfirmPrivate() {
        $gitAction = \Mockery::mock(\GitActions::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $gitAction->shouldReceive('getText')->andReturns('set_private_warn');
        $git = \Mockery::spy(\Git::class);
        $gitAction->setController($git);
        $gitRepository = \Mockery::spy(\GitRepository::class);
        $gitRepository->shouldReceive('getAccess')->andReturns('public');
        $gitAction->shouldReceive('getGitRepository')->andReturns($gitRepository);

        $git->shouldReceive('addError')->never();
        $git->shouldReceive('addWarn')->with('set_private_warn')->once();
        $gitRepository->shouldReceive('getNonMemberMails')->once()->andReturns(array('john.doe@acme.com'));
        $gitRepository->shouldReceive('setDescription')->once();
        $gitRepository->shouldReceive('save')->once();
        $gitAction->shouldReceive('save')->never();
        $gitAction->shouldReceive('addData')->times(3);

        $this->assertTrue($gitAction->confirmPrivate(1, 1, 'private', 'desc'));
    }

    function testGetProjectRepositoryListShouldReturnProjectRepositories() {
        $projectId = 42;
        $userId    = 24;

        $project_repos = array(
            array(
                'id'   => '1',
                'name' => 'a',
            ),
            array(
                'id'   => '2',
                'name' => 'b',
            ),
        );

        $sandra_repos = array(
            array(
                'id'   => '3',
                'name' => 'c',
            )
        );

        $repo_owners = TestHelper::arrayToDar(
            array(
                array(
                    'id' => '123',
                ),
                array(
                    'id' => '456',
                ),
            )
        );

        $dao    = \Mockery::spy(\GitDao::class);
        $dao->shouldReceive('getProjectRepositoryList')->with($projectId, false, true, null)->andReturns($project_repos);
        $dao->shouldReceive('getProjectRepositoryList')->with($projectId, false, true, $userId)->andReturns($sandra_repos);
        $dao->shouldReceive('getProjectRepositoriesOwners')->with($projectId)->andReturns($repo_owners);

        $controller = \Mockery::spy(\Git::class);
        $controller->shouldReceive('addData')->with(array('repository_list' => $project_repos, 'repositories_owners' => $repo_owners))->ordered();
        $controller->shouldReceive('addData')->with(array('repository_list' => $sandra_repos, 'repositories_owners' => $repo_owners))->ordered();

        $action = \Mockery::mock(\GitActions::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $action->setController($controller);
        $action->shouldReceive('getDao')->andReturns($dao);

        $action->getProjectRepositoryList($projectId);
        $action->getProjectRepositoryList($projectId, $userId);
    }

}

class GitActions_Delete_Tests extends TuleapTestCase {
    protected $git_actions;
    protected $project_id;
    protected $repository_id;
    protected $repository;
    protected $git_system_event_manager;

    public function setUp() {
        parent::setUp();
        $this->setUpGlobalsMockery();

        $this->project_id    = 101;
        $this->repository_id = 69;

        $this->repository = \Mockery::spy(\GitRepository::class);
        stub($this->repository)->getId()->returns($this->repository_id);
        stub($this->repository)->getProjectId()->returns($this->project_id);

        $this->git_system_event_manager = \Mockery::spy(\Git_SystemEventManager::class);
        $controler                  = mockery_stub(\Git::class)->getPlugin()->returns(\Mockery::spy(\gitPlugin::class));
        $git_repository_factory     = \Mockery::spy(\GitRepositoryFactory::class);

        stub($git_repository_factory)->getRepositoryById($this->repository_id)->returns($this->repository);

        $git_plugin  = mockery_stub(\GitPlugin::class)->areFriendlyUrlsActivated()->returns(false);
        $url_manager = new Git_GitRepositoryUrlManager($git_plugin);

        $this->git_actions = new GitActions(
            $controler,
            $this->git_system_event_manager,
            $git_repository_factory,
            \Mockery::spy(\GitRepositoryManager::class),
            \Mockery::spy(\Git_RemoteServer_GerritServerFactory::class),
            mockery_stub(\Git_Driver_Gerrit_GerritDriverFactory::class)->getDriver()->returns(\Mockery::spy(\Git_Driver_Gerrit::class)),
            \Mockery::spy(\Git_Driver_Gerrit_UserAccountManager::class),
            \Mockery::spy(\Git_Driver_Gerrit_ProjectCreator::class),
            \Mockery::spy(\Git_Driver_Gerrit_Template_TemplateFactory::class),
            \Mockery::spy(\ProjectManager::class),
            \Mockery::spy(\GitPermissionsManager::class),
            $url_manager,
            \Mockery::spy(\Logger::class),
            \Mockery::spy(\Git_Mirror_MirrorDataMapper::class),
            \Mockery::spy(\ProjectHistoryDao::class),
            \Mockery::spy(\GitRepositoryMirrorUpdater::class),
            \Mockery::spy(\Tuleap\Git\RemoteServer\Gerrit\MigrationHandler::class),
            \Mockery::spy(\Tuleap\Git\GerritCanMigrateChecker::class),
            \Mockery::spy(\Tuleap\Git\Permissions\FineGrainedUpdater::class),
            \Mockery::spy(\Tuleap\Git\Permissions\FineGrainedPermissionSaver::class),
            \Mockery::spy(\Tuleap\Git\Permissions\FineGrainedRetriever::class),
            \Mockery::spy(\Tuleap\Git\Permissions\HistoryValueFormatter::class),
            \Mockery::spy(\Tuleap\Git\Permissions\PermissionChangesDetector::class),
            \Mockery::spy(\Tuleap\Git\Permissions\RegexpFineGrainedEnabler::class),
            \Mockery::spy(\Tuleap\Git\Permissions\RegexpFineGrainedDisabler::class),
            \Mockery::spy(\Tuleap\Git\Permissions\RegexpPermissionFilter::class),
            \Mockery::spy(\Tuleap\Git\Permissions\RegexpFineGrainedRetriever::class),
            \Mockery::spy(\Tuleap\Git\Notifications\UsersToNotifyDao::class),
            \Mockery::spy(\Tuleap\Git\Notifications\UgroupsToNotifyDao::class),
            \Mockery::spy(\UGroupManager::class)
        );
    }

    public function itMarksRepositoryAsDeleted() {
        stub($this->repository)->canBeDeleted()->returns(true);

        $this->repository->shouldReceive('markAsDeleted')->once();

        $this->git_actions->deleteRepository($this->project_id, $this->repository_id);
    }

    public function itTriggersASystemEventForPhysicalRemove() {
        stub($this->repository)->canBeDeleted()->returns(true);

        stub($this->repository)->getBackend()->returns(\Mockery::spy(\Git_Backend_Gitolite::class));

        expect($this->git_system_event_manager)->queueRepositoryDeletion($this->repository)->once();

        $this->git_actions->deleteRepository($this->project_id, $this->repository_id);
    }

    public function itDoesntDeleteWhenRepositoryCannotBeDeleted() {
        stub($this->repository)->canBeDeleted()->returns(false);

        $this->repository->shouldReceive('markAsDeleted')->never();
        expect($this->git_system_event_manager)->queueRepositoryDeletion()->never();
        $this->git_actions->deleteRepository($this->project_id, $this->repository_id);
    }
}

class GitActions_ForkTests extends TuleapTestCase {
    private $actions;

    public function setUp() {
        parent::setUp();
        $this->setUpGlobalsMockery();
        $this->manager = \Mockery::spy(\GitRepositoryManager::class);

        $git_plugin  = mockery_stub(\GitPlugin::class)->areFriendlyUrlsActivated()->returns(false);
        $url_manager = new Git_GitRepositoryUrlManager($git_plugin);

        $this->actions = new GitActions(
            \Mockery::spy(\Git::class),
            \Mockery::spy(\Git_SystemEventManager::class),
            \Mockery::spy(\GitRepositoryFactory::class),
            $this->manager,
            \Mockery::spy(\Git_RemoteServer_GerritServerFactory::class),
            mockery_stub(\Git_Driver_Gerrit_GerritDriverFactory::class)->getDriver()->returns(\Mockery::spy(\Git_Driver_Gerrit::class)),
            \Mockery::spy(\Git_Driver_Gerrit_UserAccountManager::class),
            \Mockery::spy(\Git_Driver_Gerrit_ProjectCreator::class),
            \Mockery::spy(\Git_Driver_Gerrit_Template_TemplateFactory::class),
            \Mockery::spy(\ProjectManager::class),
            \Mockery::spy(\GitPermissionsManager::class),
            $url_manager,
            \Mockery::spy(\Logger::class),
            \Mockery::spy(\Git_Mirror_MirrorDataMapper::class),
            \Mockery::spy(\ProjectHistoryDao::class),
            \Mockery::spy(\GitRepositoryMirrorUpdater::class),
            \Mockery::spy(\Tuleap\Git\RemoteServer\Gerrit\MigrationHandler::class),
            \Mockery::spy(\Tuleap\Git\GerritCanMigrateChecker::class),
            \Mockery::spy(\Tuleap\Git\Permissions\FineGrainedUpdater::class),
            \Mockery::spy(\Tuleap\Git\Permissions\FineGrainedPermissionSaver::class),
            \Mockery::spy(\Tuleap\Git\Permissions\FineGrainedRetriever::class),
            \Mockery::spy(\Tuleap\Git\Permissions\HistoryValueFormatter::class),
            \Mockery::spy(\Tuleap\Git\Permissions\PermissionChangesDetector::class),
            \Mockery::spy(\Tuleap\Git\Permissions\RegexpFineGrainedEnabler::class),
            \Mockery::spy(\Tuleap\Git\Permissions\RegexpFineGrainedDisabler::class),
            \Mockery::spy(\Tuleap\Git\Permissions\RegexpPermissionFilter::class),
            \Mockery::spy(\Tuleap\Git\Permissions\RegexpFineGrainedRetriever::class),
            \Mockery::spy(\Tuleap\Git\Notifications\UsersToNotifyDao::class),
            \Mockery::spy(\Tuleap\Git\Notifications\UgroupsToNotifyDao::class),
            \Mockery::spy(\UGroupManager::class)
        );
    }

    public function itDelegatesForkToGitManager() {
        $repositories = array(aGitRepository()->build(), aGitRepository()->build());
        $to_project   = \Mockery::spy(\Project::class);
        $namespace    = 'namespace';
        $scope        = GitRepository::REPO_SCOPE_INDIVIDUAL;
        $user         = \Mockery::spy(\PFUser::class);
        $response     = \Mockery::spy(\Layout::class);
        $redirect_url = '/stuff';
        $forkPermissions = array();

        $this->manager->shouldReceive('forkRepositories')->with($repositories, $to_project, $user, $namespace, $scope, $forkPermissions)->once();

        $this->actions->fork($repositories, $to_project, $namespace, $scope, $user, $response, $redirect_url, $forkPermissions);
    }
}


class GitActions_ProjectPrivacyTest extends TuleapTestCase {
    public function setUp() {
        parent::setUp();
        $this->setUpGlobalsMockery();
        $this->dao = \Mockery::spy(\GitDao::class);
        $this->factory = \Mockery::spy(\GitRepositoryFactory::class);
    }

    public function itDoesNothingWhenThereAreNoRepositories() {
        $project_id = 99;
        stub($this->dao)->getProjectRepositoryList($project_id)->returns(array());
        $this->changeProjectRepositoriesAccess($project_id, true);
        $this->changeProjectRepositoriesAccess($project_id, false);
    }

    public function itDoesNothingWeAreMakingItTheProjectPublic() {
        $project_id = 99;
        $is_private = false;
        $repo_id = 333;
        $repo = mockery_stub(\GitRepository::class)->setAccess()->never()->returns("whatever");
        stub($this->dao)->getProjectRepositoryList($project_id)->returns(array($repo_id => null));
        stub($this->factory)->getRepositoryById($repo_id)->returns($repo);
        $this->changeProjectRepositoriesAccess($project_id, $is_private);
    }

    public function itMakesRepositoriesPrivateWhenProjectBecomesPrivate() {
        $project_id = 99;
        $is_private = true;
        $repo_id = 333;
        $repo = mockery_stub(\GitRepository::class)->setAccess(GitRepository::PRIVATE_ACCESS)->once()->returns("whatever");
        stub($this->dao)->getProjectRepositoryList($project_id)->returns(array($repo_id => null));
        stub($this->factory)->getRepositoryById($repo_id)->returns($repo);
        $this->changeProjectRepositoriesAccess($project_id, $is_private);

    }

    public function itDoesNothingIfThePermissionsAreAlreadyCorrect() {
        $project_id = 99;
        $is_private = true;
        $repo_id = 333;
        $repo = mockery_stub(\GitRepository::class)->setAccess()->never()->returns("whatever");
        stub($repo)->getAccess()->returns(GitRepository::PRIVATE_ACCESS);
        stub($repo)->changeAccess()->returns("whatever");
        stub($this->dao)->getProjectRepositoryList($project_id)->returns(array($repo_id => null));
        stub($this->factory)->getRepositoryById($repo_id)->returns($repo);
        $this->changeProjectRepositoriesAccess($project_id, $is_private);
    }

    public function itHandlesAllRepositoriesOfTheProject() {
        $project_id = 99;
        $is_private = true;
        $repo_id1 = 333;
        $repo_id2 = 444;
        $repo1 = mockery_stub(\GitRepository::class)->setAccess(GitRepository::PRIVATE_ACCESS)->once()->returns("whatever");
        $repo2 = mockery_stub(\GitRepository::class)->setAccess(GitRepository::PRIVATE_ACCESS)->once()->returns("whatever");
        stub($this->dao)->getProjectRepositoryList($project_id)->returns(array($repo_id1 => null, $repo_id2 => null));
        stub($this->factory)->getRepositoryById($repo_id1)->returns($repo1);
        stub($this->factory)->getRepositoryById($repo_id2)->returns($repo2);
        $this->changeProjectRepositoriesAccess($project_id, $is_private);
    }

    private function changeProjectRepositoriesAccess($project_id, $is_private) {
        return GitActions::changeProjectRepositoriesAccess($project_id, $is_private, $this->dao, $this->factory);
    }
}

class GitActions_fetchGitConfig extends TuleapTestCase {

    /**
     * @var GitActions
     */
    private $actions;

     public function setUp() {
        parent::setUp();
        $this->setUpGlobalsMockery();

        $this->project_id = 458;
        $this->project    = \Mockery::spy(\Project::class);
        stub($this->project)->getId()->returns($this->project_id);

        $this->repo_id = 14;
        $this->repo    = \Mockery::spy(\GitRepository::class);
        stub($this->repo)->getId()->returns($this->repo_id);
        stub($this->repo)->belongsToProject($this->project)->returns(true);

        $this->user    = \Mockery::spy(\PFUser::class);

        $this->request = \Mockery::spy(\Codendi_Request::class);
        $this->system_event_manager = \Mockery::spy(\Git_SystemEventManager::class);
        $this->controller = \Mockery::spy(\Git::class);
        $this->driver = \Mockery::spy(\Git_Driver_Gerrit::class);

        $gerrit_server = \Mockery::spy(\Git_RemoteServer_GerritServer::class);

        $this->gerrit_server_factory = \Mockery::spy(\Git_RemoteServer_GerritServerFactory::class);
        stub($this->gerrit_server_factory)->getServerById()->returns($gerrit_server);

        $this->factory = mockery_stub(\GitRepositoryFactory::class)->getRepositoryById(14)->returns($this->repo);

        $this->project_creator = \Mockery::spy(\Git_Driver_Gerrit_ProjectCreator::class);
        $this->git_permissions_manager = \Mockery::spy(\GitPermissionsManager::class);

        stub($this->controller)->getRequest()->returns($this->request);

        $git_plugin  = mockery_stub(\GitPlugin::class)->areFriendlyUrlsActivated()->returns(false);
        $url_manager = new Git_GitRepositoryUrlManager($git_plugin);


        $this->actions = new GitActions(
            $this->controller,
            $this->system_event_manager,
            $this->factory,
            \Mockery::spy(\GitRepositoryManager::class),
            $this->gerrit_server_factory,
            mockery_stub(\Git_Driver_Gerrit_GerritDriverFactory::class)->getDriver()->returns($this->driver),
            \Mockery::spy(\Git_Driver_Gerrit_UserAccountManager::class),
            $this->project_creator,
            \Mockery::spy(\Git_Driver_Gerrit_Template_TemplateFactory::class),
            \Mockery::spy(\ProjectManager::class),
            $this->git_permissions_manager,
            $url_manager,
            \Mockery::spy(\Logger::class),
            \Mockery::spy(\Git_Mirror_MirrorDataMapper::class),
            \Mockery::spy(\ProjectHistoryDao::class),
            \Mockery::spy(\GitRepositoryMirrorUpdater::class),
            \Mockery::spy(\Tuleap\Git\RemoteServer\Gerrit\MigrationHandler::class),
            \Mockery::spy(\Tuleap\Git\GerritCanMigrateChecker::class),
            \Mockery::spy(\Tuleap\Git\Permissions\FineGrainedUpdater::class),
            \Mockery::spy(\Tuleap\Git\Permissions\FineGrainedPermissionSaver::class),
            \Mockery::spy(\Tuleap\Git\Permissions\FineGrainedRetriever::class),
            \Mockery::spy(\Tuleap\Git\Permissions\HistoryValueFormatter::class),
            \Mockery::spy(\Tuleap\Git\Permissions\PermissionChangesDetector::class),
            \Mockery::spy(\Tuleap\Git\Permissions\RegexpFineGrainedEnabler::class),
            \Mockery::spy(\Tuleap\Git\Permissions\RegexpFineGrainedDisabler::class),
            \Mockery::spy(\Tuleap\Git\Permissions\RegexpPermissionFilter::class),
            \Mockery::spy(\Tuleap\Git\Permissions\RegexpFineGrainedRetriever::class),
            \Mockery::spy(\Tuleap\Git\Notifications\UsersToNotifyDao::class),
            \Mockery::spy(\Tuleap\Git\Notifications\UgroupsToNotifyDao::class),
            \Mockery::spy(\UGroupManager::class)
        );

    }

    public function itReturnsAnErrorIfRepoDoesNotExist() {
        stub($this->factory)->getRepositoryById()->returns(null);
        $repo_id = 458;

        $GLOBALS['Response']->shouldReceive('sendStatusCode')->with(404)->once();

        $this->actions->fetchGitConfig($repo_id, $this->user, $this->project);
    }

    public function itReturnsAnErrorIfRepoDoesNotBelongToProject() {
        $project = \Mockery::spy(\Project::class);
        stub($this->repo)->belongsToProject($project)->returns(false);

        $GLOBALS['Response']->shouldReceive('sendStatusCode')->with(403)->once();

        $this->actions->fetchGitConfig($this->repo_id, $this->user, $project);
    }

    public function itReturnsAnErrorIfUserIsNotProjectAdmin() {
        stub($this->user)->isAdmin($this->project_id)->returns(false);
        stub($this->repo)->isMigratedToGerrit()->returns(true);
        $GLOBALS['Response']->shouldReceive('sendStatusCode')->with(401)->once();


        $this->actions->fetchGitConfig($this->repo_id, $this->user, $this->project);
    }

    public function itReturnsAnErrorIfRepoIsNotMigratedToGerrit() {
        stub($this->user)->isAdmin($this->project_id)->returns(true);
        stub($this->repo)->isMigratedToGerrit()->returns(false);
        $GLOBALS['Response']->shouldReceive('sendStatusCode')->with(500)->once();

        $this->actions->fetchGitConfig($this->repo_id, $this->user, $this->project);
    }

    public function itReturnsAnErrorIfRepoIsGerritServerIsDown() {
        stub($this->git_permissions_manager)->userIsGitAdmin()->returns(true);
        stub($this->repo)->isMigratedToGerrit()->returns(true);
        stub($this->project_creator)->getGerritConfig()->throws(new Git_Driver_Gerrit_Exception());
        $GLOBALS['Response']->shouldReceive('sendStatusCode')->with(500)->once();

        $this->actions->fetchGitConfig($this->repo_id, $this->user, $this->project);
    }
}
