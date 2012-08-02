<?php
/**
 * Copyright (c) STMicroelectronics, 2011. All Rights Reserved.
 *
 * This file is a part of Codendi.
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

require_once (dirname(__FILE__).'/../include/GitActions.class.php');
Mock::generatePartial('GitActions', 'GitActionsTestVersion', array('getController', 'getText', 'addData', 'getGitRepository', 'save'));
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
Mock::generate('User');
Mock::generate('SystemEventManager');
Mock::generate('Layout');
require_once(dirname(__FILE__).'/../include/Git_Backend_Gitolite.class.php');
//require_once(dirname(__FILE__).'/../include/exceptions/GitRepositoryAlreadyExistsException.class.php');

Mock::generate('Git_Backend_Gitolite');

require_once 'builders/aGitRepository.php';

class GitActionsTest extends TuleapTestCase {

    function setUp() {
        parent::setUp();
        $GLOBALS['Language']->setReturnValue('getText', 'actions_no_repository_forked', array('plugin_git', 'actions_no_repository_forked', '*'));
        $GLOBALS['Language']->setReturnValue('getText', 'successfully_forked', array('plugin_git', 'successfully_forked', '*'));
    }

    function testRepoManagement() {
        $gitAction = new GitActionsTestVersion();
        $gitAction->setReturnValue('getText', 'actions_params_error', array('actions_params_error'));
        $git = new MockGit($this);
        $gitAction->setReturnValue('getController', $git);
        $gitRepository = new MockGitRepository($this);
        $gitAction->setReturnValue('getGitRepository', $gitRepository);

        $git->expectOnce('addError', array('actions_params_error'));

        $this->assertFalse($gitAction->repoManagement(1, null));
        $this->assertTrue($gitAction->repoManagement(1, 1));
    }

    function testNotificationUpdatePrefixFail() {
        $gitAction = new GitActionsTestVersion();
        $gitAction->setReturnValue('getText', 'actions_params_error', array('actions_params_error'));
        $git = new MockGit($this);
        $gitAction->setReturnValue('getController', $git);
        $gitRepository = new MockGitRepository($this);
        $gitAction->setReturnValue('getGitRepository', $gitRepository);

        $git->expectOnce('addError', array('actions_params_error'));
        $git->expectNever('addInfo');
        $gitRepository->expectNever('setMailPrefix');
        $gitRepository->expectNever('changeMailPrefix');
        $gitAction->expectNever('addData');

        $this->assertFalse($gitAction->notificationUpdatePrefix(1, null, '[new prefix]'));
    }

    function testNotificationUpdatePrefixPass() {
        $gitAction = new GitActionsTestVersion();
        $gitAction->setReturnValue('getText', 'mail_prefix_updated', array('mail_prefix_updated'));
        $git = new MockGit($this);
        $gitAction->setReturnValue('getController', $git);
        $gitRepository = new MockGitRepository($this);
        $gitAction->setReturnValue('getGitRepository', $gitRepository);

        $git->expectNever('addError');
        $git->expectOnce('addInfo', array('mail_prefix_updated'));
        $gitRepository->expectOnce('setMailPrefix');
        $gitRepository->expectOnce('changeMailPrefix');
        $gitAction->expectCallCount('addData', 2);

        $this->assertTrue($gitAction->notificationUpdatePrefix(1, 1, '[new prefix]'));
    }

    function testNotificationAddMailFailNoRepoId() {
        $gitAction = new GitActionsTestVersion();
        $gitAction->setReturnValue('getText', 'actions_params_error', array('actions_params_error'));
        $git = new MockGit($this);
        $gitAction->setReturnValue('getController', $git);
        $gitRepository = new MockGitRepository($this);
        $gitAction->setReturnValue('getGitRepository', $gitRepository);

        $git->expectOnce('addError', array('actions_params_error'));
        $git->expectNever('addInfo');

        $mails = array('john.doe@acme.com');
        $this->assertFalse($gitAction->notificationAddMail(1, null, $mails));
    }

    function testNotificationAddMailFailNoMails() {
        $gitAction = new GitActionsTestVersion();
        $gitAction->setReturnValue('getText', 'actions_params_error', array('actions_params_error'));
        $git = new MockGit($this);
        $gitAction->setReturnValue('getController', $git);
        $gitRepository = new MockGitRepository($this);
        $gitAction->setReturnValue('getGitRepository', $gitRepository);

        $git->expectOnce('addError', array('actions_params_error'));
        $git->expectNever('addInfo');

        $this->assertFalse($gitAction->notificationAddMail(1, 1, null));
    }

    function testNotificationAddMailFailAlreadyNotified() {
        $gitAction = new GitActionsTestVersion();
        $gitAction->setReturnValue('getText', 'mail_existing john.doe@acme.com', array('mail_existing', array('john.doe@acme.com')));
        $gitAction->setReturnValue('getText', 'mail_existing jane.doe@acme.com', array('mail_existing', array('jane.doe@acme.com')));
        $gitAction->setReturnValue('getText', 'mail_existing john.smith@acme.com', array('mail_existing', array('john.smith@acme.com')));
        $git = new MockGit($this);
        $gitAction->setReturnValue('getController', $git);
        $gitRepository = new MockGitRepository($this);
        $gitRepository->setReturnValue('isAlreadyNotified', true);
        $gitRepository->setReturnValue('notificationAddMail', false, array('john.doe@acme.com'));
        $gitRepository->setReturnValue('notificationAddMail', false, array('jane.doe@acme.com'));
        $gitRepository->setReturnValue('notificationAddMail', false, array('john.smith@acme.com'));
        $gitAction->setReturnValue('getGitRepository', $gitRepository);

        $git->expectNever('addError');
        $git->expectAt(0, 'addInfo', array('mail_existing john.doe@acme.com'));
        $git->expectAt(1, 'addInfo', array('mail_existing jane.doe@acme.com'));
        $git->expectAt(2, 'addInfo', array('mail_existing john.smith@acme.com'));
        $git->expectCallCount('addInfo', 3);

        $mails = array('john.doe@acme.com',
                       'jane.doe@acme.com',
                       'john.smith@acme.com');
        $this->assertTrue($gitAction->notificationAddMail(1, 1, $mails));
    }

    function testNotificationAddMailPartialPass() {
        $gitAction = new GitActionsTestVersion();
        $gitAction->setReturnValue('getText', 'mail_not_added john.doe@acme.com', array('mail_not_added', array('john.doe@acme.com')));
        $gitAction->setReturnValue('getText', 'mail_not_added john.smith@acme.com', array('mail_not_added', array('john.smith@acme.com')));
        $git = new MockGit($this);
        $gitAction->setReturnValue('getController', $git);
        $gitRepository = new MockGitRepository($this);
        $gitRepository->setReturnValue('isAlreadyNotified', false);
        $gitRepository->setReturnValue('notificationAddMail', false, array('john.doe@acme.com'));
        $gitRepository->setReturnValue('notificationAddMail', true, array('jane.doe@acme.com'));
        $gitRepository->setReturnValue('notificationAddMail', false, array('john.smith@acme.com'));
        $gitAction->setReturnValue('getGitRepository', $gitRepository);

        $git->expectAt(0, 'addError', array('mail_not_added john.doe@acme.com'));
        $git->expectAt(1, 'addError', array('mail_not_added john.smith@acme.com'));
        $git->expectCallCount('addError', 2);
        $git->expectNever('addInfo');

        $mails = array('john.doe@acme.com',
                       'jane.doe@acme.com',
                       'john.smith@acme.com');
        $this->assertTrue($gitAction->notificationAddMail(1, 1, $mails));
    }

    function testNotificationAddMailPass() {
        $gitAction = new GitActionsTestVersion();
        $gitAction->setReturnValue('getText', 'mail_added', array('mail_added'));
        $git = new MockGit($this);
        $gitAction->setReturnValue('getController', $git);
        $gitRepository = new MockGitRepository($this);
        $gitRepository->setReturnValue('isAlreadyNotified', false);
        $gitRepository->setReturnValue('notificationAddMail', true, array('john.doe@acme.com'));
        $gitRepository->setReturnValue('notificationAddMail', true, array('jane.doe@acme.com'));
        $gitRepository->setReturnValue('notificationAddMail', true, array('john.smith@acme.com'));
        $gitAction->setReturnValue('getGitRepository', $gitRepository);

        $git->expectNever('addError');
        $git->expectOnce('addInfo', array('mail_added'));

        $mails = array('john.doe@acme.com',
                       'jane.doe@acme.com',
                       'john.smith@acme.com');
        $this->assertTrue($gitAction->notificationAddMail(1, 1, $mails));
    }

    function testNotificationRemoveMailFailNoRepoId() {
        $gitAction = new GitActionsTestVersion();
        $gitAction->setReturnValue('getText', 'actions_params_error', array('actions_params_error'));
        $git = new MockGit($this);
        $gitAction->setReturnValue('getController', $git);
        $gitRepository = new MockGitRepository($this);
        $gitAction->setReturnValue('getGitRepository', $gitRepository);

        $git->expectOnce('addError', array('actions_params_error'));
        $git->expectNever('addInfo');

        $this->assertFalse($gitAction->notificationRemoveMail(1, null, 'john.doe@acme.com'));
    }

    function testNotificationRemoveMailFailNoMail() {
        $gitAction = new GitActionsTestVersion();
        $gitAction->setReturnValue('getText', 'actions_params_error', array('actions_params_error'));
        $git = new MockGit($this);
        $gitAction->setReturnValue('getController', $git);
        $gitRepository = new MockGitRepository($this);
        $gitAction->setReturnValue('getGitRepository', $gitRepository);

        $git->expectOnce('addError', array('actions_params_error'));
        $git->expectNever('addInfo');

        $this->assertFalse($gitAction->notificationRemoveMail(1, 1, null));
    }

    function testNotificationRemoveMailFailMailNotRemoved() {
        $gitAction = new GitActionsTestVersion();
        $gitAction->setReturnValue('getText', 'mail_not_removed john.doe@acme.com', array('mail_not_removed', array('john.doe@acme.com')));
        $git = new MockGit($this);
        $gitAction->setReturnValue('getController', $git);
        $gitRepository = new MockGitRepository($this);
        $gitRepository->setReturnValue('notificationRemoveMail', false);
        $gitAction->setReturnValue('getGitRepository', $gitRepository);

        $git->expectOnce('addError', array('mail_not_removed john.doe@acme.com'));
        $git->expectNever('addInfo');

        $this->assertFalse($gitAction->notificationRemoveMail(1, 1, array('john.doe@acme.com')));
    }

    function testNotificationRemoveMailFailMailPass() {
        $gitAction = new GitActionsTestVersion();
        $gitAction->setReturnValue('getText', 'mail_removed john.doe@acme.com', array('mail_removed', array('john.doe@acme.com')));
        $git = new MockGit($this);
        $gitAction->setReturnValue('getController', $git);
        $gitRepository = new MockGitRepository($this);
        $gitRepository->setReturnValue('notificationRemoveMail', True);
        $gitAction->setReturnValue('getGitRepository', $gitRepository);

        $git->expectNever('addError');
        $git->expectOnce('addInfo', array('mail_removed john.doe@acme.com'));

        $this->assertTrue($gitAction->notificationRemoveMail(1, 1, array('john.doe@acme.com')));
    }

    function testConfirmPrivateFailNoRepoId() {
        $gitAction = new GitActionsTestVersion();
        $gitAction->setReturnValue('getText', 'actions_params_error', array('actions_params_error'));
        $git = new MockGit($this);
        $gitAction->setReturnValue('getController', $git);
        $gitRepository = new MockGitRepository($this);
        $gitAction->setReturnValue('getGitRepository', $gitRepository);

        $git->expectOnce('addError', array('actions_params_error'));
        $git->expectNever('addWarn');
        $gitRepository->expectNever('getNonMemberMails');
        $gitRepository->expectNever('setDescription');
        $gitRepository->expectNever('save');
        $gitAction->expectNever('save');

        $this->assertFalse($gitAction->confirmPrivate(1, null, 'private', 'desc'));
    }

    function testConfirmPrivateFailNoAccess() {
        $gitAction = new GitActionsTestVersion();
        $gitAction->setReturnValue('getText', 'actions_params_error', array('actions_params_error'));
        $git = new MockGit($this);
        $gitAction->setReturnValue('getController', $git);
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
        $gitAction->setReturnValue('getController', $git);
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
        $gitAction->setReturnValue('getController', $git);
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
        $gitAction->setReturnValue('getController', $git);
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
        $gitAction->setReturnValue('getController', $git);
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
        $gitAction->setReturnValue('getController', $git);
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
    protected $system_event_manager;

    public function setUp() {
        parent::setUp();

        $this->project_id    = 101;
        $this->repository_id = 69;

        $this->repository = mock('GitRepository');
        stub($this->repository)->getId()->returns($this->repository_id);
        stub($this->repository)->getProjectId()->returns($this->project_id);

        $this->system_event_manager = mock('SystemEventManager');
        $controler                  = stub('Git')->getPlugin()->returns(mock('gitPlugin'));
        $git_repository_factory     = mock('GitRepositoryFactory');

        stub($git_repository_factory)->getRepositoryById($this->repository_id)->returns($this->repository);

        $this->git_actions = new GitActions($controler, $this->system_event_manager, $git_repository_factory, mock('GitRepositoryManager'));
    }

    public function itMarksRepositoryAsDeleted() {
        stub($this->repository)->canBeDeleted()->returns(true);

        $this->repository->expectOnce('markAsDeleted');

        $this->git_actions->deleteRepository($this->project_id, $this->repository_id);
    }

    public function itTriggersASystemEventForPhysicalRemove() {
        stub($this->repository)->canBeDeleted()->returns(true);

        $this->system_event_manager->expectOnce(
            'createEvent',
            array(
                'GIT_REPO_DELETE',
                $this->project_id.SystemEvent::PARAMETER_SEPARATOR.$this->repository_id,
                '*'
            )
        );

        $this->git_actions->deleteRepository($this->project_id, $this->repository_id);
    }

    public function itDoesntDeleteWhenRepositoryCannotBeDeleted() {
        stub($this->repository)->canBeDeleted()->returns(false);

        $this->repository->expectNever('markAsDeleted');
        $this->system_event_manager->expectNever('createEvent');
        $this->git_actions->deleteRepository($this->project_id, $this->repository_id);
    }
}

class GitActions_ForkTests extends TuleapTestCase {
    private $actions;

    public function setUp() {
        parent::setUp();
        $this->manager = mock('GitRepositoryManager');
        $this->actions = new GitActions(mock('Git'), mock('SystemEventManager'), mock('GitRepositoryFactory'), $this->manager);
    }

    public function itDelegatesForkToGitManager() {
        $repositories = array(aGitRepository()->build(), aGitRepository()->build());
        $to_project   = mock('Project');
        $namespace    = 'namespace';
        $scope        = GitRepository::REPO_SCOPE_INDIVIDUAL;
        $user         = mock('User');
        $response     = mock('Layout');
        $redirect_url = '/stuff';

        $this->manager->expectOnce('forkRepositories', array($repositories, $to_project, $user, $namespace, $scope));

        $this->actions->fork($repositories, $to_project, $namespace, $scope, $user, $response, $redirect_url);
    }
}
?>
