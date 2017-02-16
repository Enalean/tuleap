<?php
/**
 * Copyright (c) STMicroelectronics, 2011. All Rights Reserved.
 * Copyright (c) Enalean, 2012 - 2016. All Rights Reserved.
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
require_once (dirname(__FILE__).'/../include/GitActions.class.php');
Mock::generatePartial('GitActions', 'GitActionsTestVersion', array('getText', 'addData', 'getGitRepository', 'save'));
require_once (dirname(__FILE__).'/../include/Git.class.php');
Mock::generate('Git');
require_once (dirname(__FILE__).'/../include/GitRepository.class.php');
Mock::generate('GitRepository');
Mock::generate('GitDao');
require_once('common/language/BaseLanguage.class.php');
Mock::generate('BaseLanguage');
require_once('common/include/Response.class.php');
Mock::generate('Response');
Mock::generate('Project');
Mock::generate('GitRepositoryFactory');
Mock::generate('PFUser');
Mock::generate('SystemEventManager');
Mock::generate('Layout');
require_once(dirname(__FILE__).'/../include/Git_Backend_Gitolite.class.php');

Mock::generate('Git_Backend_Gitolite');

require_once 'builders/aGitRepository.php';

class GitActionsTest extends TuleapTestCase {

    function setUp() {
        parent::setUp();
        $GLOBALS['Language']->setReturnValue('getText', 'actions_no_repository_forked', array('plugin_git', 'actions_no_repository_forked', '*'));
        $GLOBALS['Language']->setReturnValue('getText', 'successfully_forked', array('plugin_git', 'successfully_forked', '*'));

        $git_plugin        = stub('GitPlugin')->areFriendlyUrlsActivated()->returns(false);
        $url_manager       = new Git_GitRepositoryUrlManager($git_plugin);

        $this->gitAction = partial_mock(
            'GitActions',
            array('getText', 'addData', 'getGitRepository', 'save'),
            array(
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
                mock('Git_Backend_Gitolite'),
                mock('Git_Mirror_MirrorDataMapper'),
                mock('ProjectHistoryDao'),
                mock('GitRepositoryMirrorUpdater'),
                mock('Tuleap\Git\RemoteServer\Gerrit\MigrationHandler'),
                mock('Tuleap\Git\GerritCanMigrateChecker'),
                mock('Tuleap\Git\Webhook\WebhookDao'),
                mock('Tuleap\Git\Permissions\FineGrainedUpdater'),
                mock('Tuleap\Git\Permissions\FineGrainedPermissionSaver'),
                mock('Tuleap\Git\CIToken\Manager'),
                mock('Tuleap\Git\Permissions\FineGrainedPermissionReplicator'),
                mock('Tuleap\Git\Permissions\FineGrainedRetriever'),
                mock('Tuleap\Git\Permissions\HistoryValueFormatter'),
                mock('Tuleap\Git\Permissions\PermissionChangesDetector'),
                mock('Tuleap\Git\Permissions\RegexpFineGrainedEnabler'),
                mock('Tuleap\Git\Permissions\RegexpFineGrainedDisabler'),
                mock('Tuleap\Git\Permissions\RegexpPermissionFilter'),
                mock('Tuleap\Git\Permissions\RegexpFineGrainedRetriever'),
                mock('Tuleap\Git\Notifications\UsersToNotifyDao')
            )
        );
    }

    function testNotificationUpdatePrefixFail() {
        $this->gitAction->setReturnValue('getText', 'actions_params_error', array('actions_params_error'));
        $git = new MockGit($this);
        $this->gitAction->setController($git);
        $gitRepository = new MockGitRepository($this);
        $this->gitAction->setReturnValue('getGitRepository', $gitRepository);

        $git->expectOnce('addError', array('actions_params_error'));
        $git->expectNever('addInfo');
        $gitRepository->expectNever('setMailPrefix');
        $gitRepository->expectNever('changeMailPrefix');
        $this->gitAction->expectNever('addData');

        $this->assertFalse($this->gitAction->notificationUpdatePrefix(1, null, '[new prefix]', 'a_pane'));
    }

    function testNotificationUpdatePrefixPass() {
        $this->gitAction->setReturnValue('getText', 'mail_prefix_updated', array('mail_prefix_updated'));
        $git = new MockGit($this);
        $this->gitAction->setController($git);
        $gitRepository = new MockGitRepository($this);
        $this->gitAction->setReturnValue('getGitRepository', $gitRepository);

        $git->expectNever('addError');
        $git->expectOnce('addInfo', array('mail_prefix_updated'));
        $gitRepository->expectOnce('setMailPrefix');
        $gitRepository->expectOnce('changeMailPrefix');
        $this->gitAction->expectCallCount('addData', 2);

        $this->assertTrue($this->gitAction->notificationUpdatePrefix(1, 1, '[new prefix]', 'a_pane'));
    }

    function testNotificationAddMailFailNoRepoId() {
        $this->gitAction->setReturnValue('getText', 'actions_params_error', array('actions_params_error'));
        $git = new MockGit($this);
        $this->gitAction->setController($git);
        $gitRepository = new MockGitRepository($this);
        $this->gitAction->setReturnValue('getGitRepository', $gitRepository);

        $git->expectOnce('addError', array('actions_params_error'));
        $git->expectNever('addInfo');

        $mails = array('john.doe@acme.com');
        $this->assertFalse($this->gitAction->notificationAddMail(1, null, $mails, 'a_pane'));
    }

    function testNotificationAddMailFailNoMails() {
        $this->gitAction->setReturnValue('getText', 'actions_params_error', array('actions_params_error'));
        $git = new MockGit($this);
        $this->gitAction->setController($git);
        $gitRepository = new MockGitRepository($this);
        $this->gitAction->setReturnValue('getGitRepository', $gitRepository);

        $git->expectOnce('addError', array('actions_params_error'));
        $git->expectNever('addInfo');

        $this->assertFalse($this->gitAction->notificationAddMail(1, 1, null, 'a_pane'));
    }

    function testNotificationAddMailFailAlreadyNotified() {
        $this->gitAction->setReturnValue('getText', 'mail_existing john.doe@acme.com', array('mail_existing', array('john.doe@acme.com')));
        $this->gitAction->setReturnValue('getText', 'mail_existing jane.doe@acme.com', array('mail_existing', array('jane.doe@acme.com')));
        $this->gitAction->setReturnValue('getText', 'mail_existing john.smith@acme.com', array('mail_existing', array('john.smith@acme.com')));
        $git = new MockGit($this);
        $this->gitAction->setController($git);
        $gitRepository = new MockGitRepository($this);
        $gitRepository->setReturnValue('isAlreadyNotified', true);
        $gitRepository->setReturnValue('notificationAddMail', false, array('john.doe@acme.com'));
        $gitRepository->setReturnValue('notificationAddMail', false, array('jane.doe@acme.com'));
        $gitRepository->setReturnValue('notificationAddMail', false, array('john.smith@acme.com'));
        $this->gitAction->setReturnValue('getGitRepository', $gitRepository);

        $git->expectNever('addError');
        $git->expectAt(0, 'addInfo', array('mail_existing john.doe@acme.com'));
        $git->expectAt(1, 'addInfo', array('mail_existing jane.doe@acme.com'));
        $git->expectAt(2, 'addInfo', array('mail_existing john.smith@acme.com'));
        $git->expectCallCount('addInfo', 3);

        $mails = array('john.doe@acme.com',
                       'jane.doe@acme.com',
                       'john.smith@acme.com');
        $this->assertTrue($this->gitAction->notificationAddMail(1, 1, $mails, 'a_pane'));
    }

    function testNotificationAddMailPartialPass() {
        $this->gitAction->setReturnValue('getText', 'mail_not_added john.doe@acme.com', array('mail_not_added', array('john.doe@acme.com')));
        $this->gitAction->setReturnValue('getText', 'mail_not_added john.smith@acme.com', array('mail_not_added', array('john.smith@acme.com')));
        $git = new MockGit($this);
        $this->gitAction->setController($git);
        $gitRepository = new MockGitRepository($this);
        $gitRepository->setReturnValue('isAlreadyNotified', false);
        $gitRepository->setReturnValue('notificationAddMail', false, array('john.doe@acme.com'));
        $gitRepository->setReturnValue('notificationAddMail', true, array('jane.doe@acme.com'));
        $gitRepository->setReturnValue('notificationAddMail', false, array('john.smith@acme.com'));
        $this->gitAction->setReturnValue('getGitRepository', $gitRepository);

        $git->expectAt(0, 'addError', array('mail_not_added john.doe@acme.com'));
        $git->expectAt(1, 'addError', array('mail_not_added john.smith@acme.com'));
        $git->expectCallCount('addError', 2);
        $git->expectNever('addInfo');

        $mails = array('john.doe@acme.com',
                       'jane.doe@acme.com',
                       'john.smith@acme.com');
        $this->assertTrue($this->gitAction->notificationAddMail(1, 1, $mails, 'a_pane'));
    }

    function testNotificationAddMailPass() {
        $this->gitAction->setReturnValue('getText', 'mail_added', array('mail_added'));
        $git = new MockGit($this);
        $this->gitAction->setController($git);
        $gitRepository = new MockGitRepository($this);
        $gitRepository->setReturnValue('isAlreadyNotified', false);
        $gitRepository->setReturnValue('notificationAddMail', true, array('john.doe@acme.com'));
        $gitRepository->setReturnValue('notificationAddMail', true, array('jane.doe@acme.com'));
        $gitRepository->setReturnValue('notificationAddMail', true, array('john.smith@acme.com'));
        $this->gitAction->setReturnValue('getGitRepository', $gitRepository);

        $git->expectNever('addError');
        $git->expectOnce('addInfo', array('mail_added'));

        $mails = array('john.doe@acme.com',
                       'jane.doe@acme.com',
                       'john.smith@acme.com');
        $this->assertTrue($this->gitAction->notificationAddMail(1, 1, $mails, 'a_pane'));
    }

    function testNotificationRemoveMailFailNoRepoId() {
        $this->gitAction->setReturnValue('getText', 'actions_params_error', array('actions_params_error'));
        $git = new MockGit($this);
        $this->gitAction->setController($git);
        $gitRepository = new MockGitRepository($this);
        $this->gitAction->setReturnValue('getGitRepository', $gitRepository);

        $git->expectOnce('addError', array('actions_params_error'));
        $git->expectNever('addInfo');

        $this->assertFalse($this->gitAction->notificationRemoveMail(1, null, 'john.doe@acme.com', 'a_pane'));
    }

    function testNotificationRemoveMailFailNoMail() {
        $this->gitAction->setReturnValue('getText', 'actions_params_error', array('actions_params_error'));
        $git = new MockGit($this);
        $this->gitAction->setController($git);
        $gitRepository = new MockGitRepository($this);
        $this->gitAction->setReturnValue('getGitRepository', $gitRepository);

        $git->expectOnce('addError', array('actions_params_error'));
        $git->expectNever('addInfo');

        $this->assertFalse($this->gitAction->notificationRemoveMail(1, 1, null, 'a_pane'));
    }

    function testNotificationRemoveMailFailMailNotRemoved() {
        $this->gitAction->setReturnValue('getText', 'mail_not_removed john.doe@acme.com', array('mail_not_removed', array('john.doe@acme.com')));
        $git = new MockGit($this);
        $this->gitAction->setController($git);
        $gitRepository = new MockGitRepository($this);
        $gitRepository->setReturnValue('notificationRemoveMail', false);
        $this->gitAction->setReturnValue('getGitRepository', $gitRepository);

        $git->expectOnce('addError', array('mail_not_removed john.doe@acme.com'));
        $git->expectNever('addInfo');

        $this->assertFalse($this->gitAction->notificationRemoveMail(1, 1, array('john.doe@acme.com'), 'a_pane'));
    }

    function testNotificationRemoveMailFailMailPass() {
        $this->gitAction->setReturnValue('getText', 'mail_removed john.doe@acme.com', array('mail_removed', array('john.doe@acme.com')));
        $git = new MockGit($this);
        $this->gitAction->setController($git);
        $gitRepository = new MockGitRepository($this);
        $gitRepository->setReturnValue('notificationRemoveMail', True);
        $this->gitAction->setReturnValue('getGitRepository', $gitRepository);

        $git->expectNever('addError');
        $git->expectOnce('addInfo', array('mail_removed john.doe@acme.com'));

        $this->assertTrue($this->gitAction->notificationRemoveMail(1, 1, array('john.doe@acme.com'), 'a_pane'));
    }

    function testConfirmPrivateFailNoRepoId() {
        $this->gitAction->setReturnValue('getText', 'actions_params_error', array('actions_params_error'));
        $git = new MockGit($this);
        $this->gitAction->setController($git);
        $gitRepository = new MockGitRepository($this);
        $this->gitAction->setReturnValue('getGitRepository', $gitRepository);

        $git->expectOnce('addError', array('actions_params_error'));
        $git->expectNever('addWarn');
        $gitRepository->expectNever('getNonMemberMails');
        $gitRepository->expectNever('setDescription');
        $gitRepository->expectNever('save');
        $this->gitAction->expectNever('save');

        $this->assertFalse($this->gitAction->confirmPrivate(1, null, 'private', 'desc'));
    }

    function testConfirmPrivateFailNoAccess() {
        $gitAction = new GitActionsTestVersion();
        $gitAction->setReturnValue('getText', 'actions_params_error', array('actions_params_error'));
        $git = new MockGit($this);
        $gitAction->setController($git);
        $gitRepository = new MockGitRepository($this);
        $gitAction->setReturnValue('getGitRepository', $gitRepository);

        $git->expectOnce('addError', array('actions_params_error'));
        $git->expectNever('addWarn');
        $gitRepository->expectNever('getNonMemberMails');
        $gitRepository->expectNever('setDescription');
        $gitRepository->expectNever('save');
        $gitAction->expectNever('save');

        $this->assertFalse($gitAction->confirmPrivate(1, 1, null, 'desc'));
    }

    function testConfirmPrivateFailNoDesc() {
        $gitAction = new GitActionsTestVersion();
        $gitAction->setReturnValue('getText', 'actions_params_error', array('actions_params_error'));
        $git = new MockGit($this);
        $gitAction->setController($git);
        $gitRepository = new MockGitRepository($this);
        $gitAction->setReturnValue('getGitRepository', $gitRepository);

        $git->expectOnce('addError', array('actions_params_error'));
        $git->expectNever('addWarn');
        $gitRepository->expectNever('getNonMemberMails');
        $gitRepository->expectNever('setDescription');
        $gitRepository->expectNever('save');
        $gitAction->expectNever('save');

        $this->assertFalse($gitAction->confirmPrivate(1, 1, 'private', null));
    }

    function testConfirmPrivateNotSettingToPrivate() {
        $gitAction = new GitActionsTestVersion();
        $git = new MockGit($this);
        $gitAction->setController($git);
        $gitRepository = new MockGitRepository($this);
        $gitRepository->setReturnValue('getAccess', 'public');
        $gitAction->setReturnValue('getGitRepository', $gitRepository);

        $git->expectNever('addError');
        $git->expectNever('addWarn');
        $gitRepository->expectNever('getNonMemberMails');
        $gitRepository->expectNever('setDescription');
        $gitRepository->expectNever('save');
        $gitAction->expectOnce('save');

        $this->assertTrue($gitAction->confirmPrivate(1, 1, 'public', 'desc'));
    }

    function testConfirmPrivateAlreadyPrivate() {
        $gitAction = new GitActionsTestVersion();
        $git = new MockGit($this);
        $gitAction->setController($git);
        $gitRepository = new MockGitRepository($this);
        $gitRepository->setReturnValue('getAccess', 'private');
        $gitAction->setReturnValue('getGitRepository', $gitRepository);

        $git->expectNever('addError');
        $git->expectNever('addWarn');
        $gitRepository->expectNever('getNonMemberMails');
        $gitRepository->expectNever('setDescription');
        $gitRepository->expectNever('save');
        $gitAction->expectOnce('save');

        $this->assertTrue($gitAction->confirmPrivate(1, 1, 'private', 'desc'));
    }

    function testConfirmPrivateNoMailsToDelete() {
        $gitAction = new GitActionsTestVersion();
        $git = new MockGit($this);
        $gitAction->setController($git);
        $gitRepository = new MockGitRepository($this);
        $gitRepository->setReturnValue('getAccess', 'public');
        $gitRepository->setReturnValue('getNonMemberMails', array());
        $gitAction->setReturnValue('getGitRepository', $gitRepository);

        $git->expectNever('addError');
        $git->expectNever('addWarn');
        $gitRepository->expectOnce('getNonMemberMails');
        $gitRepository->expectNever('setDescription');
        $gitRepository->expectNever('save');
        $gitAction->expectOnce('save');

        $this->assertTrue($gitAction->confirmPrivate(1, 1, 'private', 'desc'));
    }

    function testConfirmPrivate() {
        $gitAction = new GitActionsTestVersion();
        $gitAction->setReturnValue('getText', 'set_private_warn');
        $git = new MockGit($this);
        $gitAction->setController($git);
        $gitRepository = new MockGitRepository($this);
        $gitRepository->setReturnValue('getAccess', 'public');
        $gitRepository->setReturnValue('getNonMemberMails', array('john.doe@acme.com'));
        $gitAction->setReturnValue('getGitRepository', $gitRepository);

        $git->expectNever('addError');
        $git->expectOnce('addWarn', array('set_private_warn'));
        $gitRepository->expectOnce('getNonMemberMails');
        $gitRepository->expectOnce('setDescription');
        $gitRepository->expectOnce('save');
        $gitAction->expectNever('save');
        $gitAction->expectCallCount('addData', 3);

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
        
        $dao    = new MockGitDao();
        $dao->setReturnValue('getProjectRepositoryList', $project_repos, array($projectId, false, true, null));
        $dao->setReturnValue('getProjectRepositoryList', $sandra_repos, array($projectId, false, true, $userId));
        $dao->setReturnValue('getProjectRepositoriesOwners', $repo_owners, array($projectId));
        
        $controller = new MockGit();
        $controller->expectAt(0, 'addData', array(array('repository_list' => $project_repos, 'repositories_owners' => $repo_owners)));
        $controller->expectAt(1, 'addData', array(array('repository_list' => $sandra_repos, 'repositories_owners' => $repo_owners)));
        
        $action = TestHelper::getPartialMock('GitActions', array('getDao'));
        $action->setController($controller);
        $action->setReturnValue('getDao', $dao);
        
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

        $this->project_id    = 101;
        $this->repository_id = 69;

        $this->repository = mock('GitRepository');
        stub($this->repository)->getId()->returns($this->repository_id);
        stub($this->repository)->getProjectId()->returns($this->project_id);

        $this->git_system_event_manager = mock('Git_SystemEventManager');
        $controler                  = stub('Git')->getPlugin()->returns(mock('gitPlugin'));
        $git_repository_factory     = mock('GitRepositoryFactory');

        stub($git_repository_factory)->getRepositoryById($this->repository_id)->returns($this->repository);

        $git_plugin  = stub('GitPlugin')->areFriendlyUrlsActivated()->returns(false);
        $url_manager = new Git_GitRepositoryUrlManager($git_plugin);

        $this->git_actions = new GitActions(
            $controler,
            $this->git_system_event_manager,
            $git_repository_factory,
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
            mock('Git_Backend_Gitolite'),
            mock('Git_Mirror_MirrorDataMapper'),
            mock('ProjectHistoryDao'),
            mock('GitRepositoryMirrorUpdater'),
            mock('Tuleap\Git\RemoteServer\Gerrit\MigrationHandler'),
            mock('Tuleap\Git\GerritCanMigrateChecker'),
            mock('Tuleap\Git\Webhook\WebhookDao'),
            mock('Tuleap\Git\Permissions\FineGrainedUpdater'),
            mock('Tuleap\Git\Permissions\FineGrainedPermissionSaver'),
            mock('Tuleap\Git\CIToken\Manager'),
            mock('Tuleap\Git\Permissions\FineGrainedPermissionReplicator'),
            mock('Tuleap\Git\Permissions\FineGrainedRetriever'),
            mock('Tuleap\Git\Permissions\HistoryValueFormatter'),
            mock('Tuleap\Git\Permissions\PermissionChangesDetector'),
            mock('Tuleap\Git\Permissions\RegexpFineGrainedEnabler'),
            mock('Tuleap\Git\Permissions\RegexpFineGrainedDisabler'),
            mock('Tuleap\Git\Permissions\RegexpPermissionFilter'),
            mock('Tuleap\Git\Permissions\RegexpFineGrainedRetriever'),
            mock('Tuleap\Git\Notifications\UsersToNotifyDao')
        );
    }

    public function itMarksRepositoryAsDeleted() {
        stub($this->repository)->canBeDeleted()->returns(true);

        $this->repository->expectOnce('markAsDeleted');

        $this->git_actions->deleteRepository($this->project_id, $this->repository_id);
    }

    public function itTriggersASystemEventForPhysicalRemove() {
        stub($this->repository)->canBeDeleted()->returns(true);

        stub($this->repository)->getBackend()->returns(mock('Git_Backend_Gitolite'));

        expect($this->git_system_event_manager)->queueRepositoryDeletion($this->repository)->once();

        $this->git_actions->deleteRepository($this->project_id, $this->repository_id);
    }

    public function itDoesntDeleteWhenRepositoryCannotBeDeleted() {
        stub($this->repository)->canBeDeleted()->returns(false);

        $this->repository->expectNever('markAsDeleted');
        expect($this->git_system_event_manager)->queueRepositoryDeletion()->never();
        $this->git_actions->deleteRepository($this->project_id, $this->repository_id);
    }
}

class GitActions_ForkTests extends TuleapTestCase {
    private $actions;

    public function setUp() {
        parent::setUp();
        $this->manager = mock('GitRepositoryManager');

        $git_plugin  = stub('GitPlugin')->areFriendlyUrlsActivated()->returns(false);
        $url_manager = new Git_GitRepositoryUrlManager($git_plugin);

        $this->actions = new GitActions(
            mock('Git'),
            mock('Git_SystemEventManager'),
            mock('GitRepositoryFactory'),
            $this->manager,
            mock('Git_RemoteServer_GerritServerFactory'),
            stub('Git_Driver_Gerrit_GerritDriverFactory')->getDriver()->returns(mock('Git_Driver_Gerrit')),
            mock('Git_Driver_Gerrit_UserAccountManager'),
            mock('Git_Driver_Gerrit_ProjectCreator'),
            mock('Git_Driver_Gerrit_Template_TemplateFactory'),
            mock('ProjectManager'),
            mock('GitPermissionsManager'),
            $url_manager,
            mock('Logger'),
            mock('Git_Backend_Gitolite'),
            mock('Git_Mirror_MirrorDataMapper'),
            mock('ProjectHistoryDao'),
            mock('GitRepositoryMirrorUpdater'),
            mock('Tuleap\Git\RemoteServer\Gerrit\MigrationHandler'),
            mock('Tuleap\Git\GerritCanMigrateChecker'),
            mock('Tuleap\Git\Webhook\WebhookDao'),
            mock('Tuleap\Git\Permissions\FineGrainedUpdater'),
            mock('Tuleap\Git\Permissions\FineGrainedPermissionSaver'),
            mock('Tuleap\Git\CIToken\Manager'),
            mock('Tuleap\Git\Permissions\FineGrainedPermissionReplicator'),
            mock('Tuleap\Git\Permissions\FineGrainedRetriever'),
            mock('Tuleap\Git\Permissions\HistoryValueFormatter'),
            mock('Tuleap\Git\Permissions\PermissionChangesDetector'),
            mock('Tuleap\Git\Permissions\RegexpFineGrainedEnabler'),
            mock('Tuleap\Git\Permissions\RegexpFineGrainedDisabler'),
            mock('Tuleap\Git\Permissions\RegexpPermissionFilter'),
            mock('Tuleap\Git\Permissions\RegexpFineGrainedRetriever'),
            mock('Tuleap\Git\Notifications\UsersToNotifyDao')
        );
    }

    public function itDelegatesForkToGitManager() {
        $repositories = array(aGitRepository()->build(), aGitRepository()->build());
        $to_project   = mock('Project');
        $namespace    = 'namespace';
        $scope        = GitRepository::REPO_SCOPE_INDIVIDUAL;
        $user         = mock('PFUser');
        $response     = mock('Layout');
        $redirect_url = '/stuff';
        $forkPermissions = array();

        $this->manager->expectOnce('forkRepositories', array($repositories, $to_project, $user, $namespace, $scope, $forkPermissions));

        $this->actions->fork($repositories, $to_project, $namespace, $scope, $user, $response, $redirect_url, $forkPermissions);
    }
}


class GitActions_ProjectPrivacyTest extends TuleapTestCase {
    public function setUp() {
        parent::setUp();
        $this->dao = mock('GitDao');
        $this->factory = mock('GitRepositoryFactory');
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
        $repo = stub('GitRepository')->setAccess()->never()->returns("whatever");
        stub($this->dao)->getProjectRepositoryList($project_id)->returns(array($repo_id => null));
        stub($this->factory)->getRepositoryById($repo_id)->returns($repo);
        $this->changeProjectRepositoriesAccess($project_id, $is_private);
    }

    public function itMakesRepositoriesPrivateWhenProjectBecomesPrivate() {
        $project_id = 99;
        $is_private = true;
        $repo_id = 333;
        $repo = stub('GitRepository')->setAccess(GitRepository::PRIVATE_ACCESS)->once()->returns("whatever");
        stub($this->dao)->getProjectRepositoryList($project_id)->returns(array($repo_id => null));
        stub($this->factory)->getRepositoryById($repo_id)->returns($repo);
        $this->changeProjectRepositoriesAccess($project_id, $is_private);

    }

    public function itDoesNothingIfThePermissionsAreAlreadyCorrect() {
        $project_id = 99;
        $is_private = true;
        $repo_id = 333;
        $repo = stub('GitRepository')->setAccess()->never()->returns("whatever");
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
        $repo1 = stub('GitRepository')->setAccess(GitRepository::PRIVATE_ACCESS)->once()->returns("whatever");
        $repo2 = stub('GitRepository')->setAccess(GitRepository::PRIVATE_ACCESS)->once()->returns("whatever");
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

        $this->backend = mock('Git_Backend_Gitolite');

        $this->project_id = 458;
        $this->project    = mock('Project');
        stub($this->project)->getId()->returns($this->project_id);

        $this->repo_id = 14;
        $this->repo    = mock('GitRepository');
        stub($this->repo)->getId()->returns($this->repo_id);
        stub($this->repo)->belongsToProject($this->project)->returns(true);

        $this->user    = mock('PFUser');

        $this->request = mock('Codendi_Request');
        $this->system_event_manager = mock('Git_SystemEventManager');
        $this->controller = mock('Git');
        $this->driver = mock('Git_Driver_Gerrit');

        $gerrit_server = mock('Git_RemoteServer_GerritServer');

        $this->gerrit_server_factory = mock('Git_RemoteServer_GerritServerFactory');
        stub($this->gerrit_server_factory)->getServerById()->returns($gerrit_server);

        $this->factory = stub('GitRepositoryFactory')->getRepositoryById(14)->returns($this->repo);

        $this->project_creator = mock('Git_Driver_Gerrit_ProjectCreator');
        $this->git_permissions_manager = mock('GitPermissionsManager');

        stub($this->controller)->getRequest()->returns($this->request);

        $git_plugin  = stub('GitPlugin')->areFriendlyUrlsActivated()->returns(false);
        $url_manager = new Git_GitRepositoryUrlManager($git_plugin);


        $this->actions = new GitActions(
            $this->controller,
            $this->system_event_manager,
            $this->factory,
            mock('GitRepositoryManager'),
            $this->gerrit_server_factory,
            stub('Git_Driver_Gerrit_GerritDriverFactory')->getDriver()->returns($this->driver),
            mock('Git_Driver_Gerrit_UserAccountManager'),
            $this->project_creator,
            mock('Git_Driver_Gerrit_Template_TemplateFactory'),
            mock('ProjectManager'),
            $this->git_permissions_manager,
            $url_manager,
            mock('Logger'),
            mock('Git_Backend_Gitolite'),
            mock('Git_Mirror_MirrorDataMapper'),
            mock('ProjectHistoryDao'),
            mock('GitRepositoryMirrorUpdater'),
            mock('Tuleap\Git\RemoteServer\Gerrit\MigrationHandler'),
            mock('Tuleap\Git\GerritCanMigrateChecker'),
            mock('Tuleap\Git\Webhook\WebhookDao'),
            mock('Tuleap\Git\Permissions\FineGrainedUpdater'),
            mock('Tuleap\Git\Permissions\FineGrainedPermissionSaver'),
            mock('Tuleap\Git\CIToken\Manager'),
            mock('Tuleap\Git\Permissions\FineGrainedPermissionReplicator'),
            mock('Tuleap\Git\Permissions\FineGrainedRetriever'),
            mock('Tuleap\Git\Permissions\HistoryValueFormatter'),
            mock('Tuleap\Git\Permissions\PermissionChangesDetector'),
            mock('Tuleap\Git\Permissions\RegexpFineGrainedEnabler'),
            mock('Tuleap\Git\Permissions\RegexpFineGrainedDisabler'),
            mock('Tuleap\Git\Permissions\RegexpPermissionFilter'),
            mock('Tuleap\Git\Permissions\RegexpFineGrainedRetriever'),
            mock('Tuleap\Git\Notifications\UsersToNotifyDao')
        );

    }

    public function itReturnsAnErrorIfRepoDoesNotExist() {
        stub($this->factory)->getRepositoryById()->returns(null);
        $repo_id = 458;

        $GLOBALS['Response']->expectOnce('sendStatusCode', array(404));

        $this->actions->fetchGitConfig($repo_id, $this->user, $this->project);
    }

    public function itReturnsAnErrorIfRepoDoesNotBelongToProject() {
        $project = mock('Project');
        stub($this->repo)->belongsToProject($project)->returns(false);

        $GLOBALS['Response']->expectOnce('sendStatusCode', array(403));

        $this->actions->fetchGitConfig($this->repo_id, $this->user, $project);
    }

    public function itReturnsAnErrorIfUserIsNotProjectAdmin() {
        stub($this->user)->isAdmin($this->project_id)->returns(false);
        stub($this->repo)->isMigratedToGerrit()->returns(true);
        $GLOBALS['Response']->expectOnce('sendStatusCode', array(401));


        $this->actions->fetchGitConfig($this->repo_id, $this->user, $this->project);
    }

    public function itReturnsAnErrorIfRepoIsNotMigratedToGerrit() {
        stub($this->user)->isAdmin($this->project_id)->returns(true);
        stub($this->repo)->isMigratedToGerrit()->returns(false);
        $GLOBALS['Response']->expectOnce('sendStatusCode', array(500));

        $this->actions->fetchGitConfig($this->repo_id, $this->user, $this->project);
    }

    public function itReturnsAnErrorIfRepoIsGerritServerIsDown() {
        stub($this->git_permissions_manager)->userIsGitAdmin()->returns(true);
        stub($this->repo)->isMigratedToGerrit()->returns(true);
        stub($this->project_creator)->getGerritConfig()->throws(new Git_Driver_Gerrit_Exception());
        $GLOBALS['Response']->expectOnce('sendStatusCode', array(500));

        $this->actions->fetchGitConfig($this->repo_id, $this->user, $this->project);
    }
}
