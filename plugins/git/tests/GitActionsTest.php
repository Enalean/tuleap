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
Mock::generate('GitRepositoryFactory');
Mock::generate('User');
Mock::generate('SystemEventManager');
Mock::generate('Layout');

class GitActionsTest extends UnitTestCase {

    function setUp() {
        $GLOBALS['Language'] = new MockBaseLanguage();
        $GLOBALS['Language']->setReturnValue('getText', 'actions_no_repository_selected', array('plugin_git', 'actions_no_repository_selected', '*'));
    }
    function tearDown() {
        unset($GLOBALS['Language']);
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
        $dao->setReturnValue('getProjectRepositoryList', $project_repos, array($projectId, false, null));
        $dao->setReturnValue('getProjectRepositoryList', $sandra_repos, array($projectId, false, $userId));
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
    
    function testFork_selectNoRepositoryToCloneShouldDisplayAWarning() {
        $repositories = array();
        $path = 'toto';
        $group_id = 101;
        
        $user = new MockUser();
        
        $controller = new MockGit($this);
        $controller->expectOnce('addError', array('actions_no_repository_selected'));

        $factory = new MockGitRepositoryFactory();
        $systemEventManager = new MockSystemEventManager();
        $layout = new MockLayout();
        $layout->expectNever('redirect');
        
        $action = new GitActions($controller, $factory, $systemEventManager);
        $action->forkRepositories($group_id, $repositories, $path, $user, $layout);
    }
    
    function testForkShouldCloneOneRepository() {
        $repositories = array('1');
        $path = 'toto';
        $group_id = 101;
        
        $user = new MockUser();
        $user->setReturnValue('getId', 123);
        
        $controller = new MockGit($this);
        $factory = $this->getFactoryForFork($group_id, $repositories, $path, $user);
        
        $systemEventManager = new MockSystemEventManager();
        $layout = new MockLayout();
        $layout->expectOnce('redirect', array('/plugins/git/?group_id='. $group_id .'&user='. $user->getId()));
        
        $action = new GitActions($controller, $factory, $systemEventManager);
        $action->forkRepositories($group_id, $repositories, $path, $user, $layout);
    }
    
    function testForkShouldCloneManyRepositories() {
        $repositories = array('1', '2', '3');
        $path = 'toto';
        $group_id = 101;
        
        $user = new MockUser();
        
        $controller = new MockGit($this);
        $factory = $this->getFactoryForFork($group_id, $repositories, $path, $user);
        
        $systemEventManager = new MockSystemEventManager();
        $layout = new MockLayout();
        $layout->expectOnce('redirect');
        
        $action = new GitActions($controller, $factory, $systemEventManager);
        $action->forkRepositories($group_id, $repositories, $path, $user, $layout);
    }
    
    function testForkShouldCloneManyRepositoriesExceptNonExistentOne() {
        $repositories = array('1', '2', '3');
        $path = 'toto';
        $group_id = 101;
        
        $user = new MockUser();
        
        $controller = new MockGit($this);
        $factory = $this->getFactoryForFork($group_id, $repositories, $path, $user);
        
        $systemEventManager = new MockSystemEventManager();
        $layout = new MockLayout();
        $layout->expectOnce('redirect');
        
        $action = new GitActions($controller, $factory, $systemEventManager);
        $action->forkRepositories($group_id, array('1', '2', '3', '4'), $path, $user, $layout);
    }
    
    function testForkShouldNotCloneAnyNonExistentRepositories() {
        $repositories = array();
        $path = 'toto';
        $group_id = 101;
        
        $user = new MockUser();
        
        $controller = new MockGit($this);
        $factory = $this->getFactoryForFork($group_id, $repositories, $path, $user);
        
        $systemEventManager = new MockSystemEventManager();
        $layout = new MockLayout();
        $layout->expectNever('redirect');
        
        $action = new GitActions($controller, $factory, $systemEventManager);
        $action->forkRepositories($group_id, array('1', '2', '3', '4'), $path, $user, $layout);
    }
    
    function testForkRepositoriesShouldNotForkUnreadableRepositories() {
        $repositories = array('1');
        $path = 'toto';
        $group_id = 101;
        
        $user = new MockUser();
        
        $controller = new MockGit($this);
        $factory = $this->getFactoryForFork($group_id, $repositories, $path, $user, array('1'));
        
        $systemEventManager = new MockSystemEventManager();
        $layout = new MockLayout();
        $layout->expectNever('redirect');
        
        $action = new GitActions($controller, $factory, $systemEventManager);
        $action->forkRepositories($group_id, $repositories, $path, $user, $layout);
    }
    
    function testForkRepositoriesShouldNotForkOutsideProjectRepositories() {
        $id1 = '1';
        $id2 = '2';
        $repositories = array($id1, $id2);
        $path = 'toto';
        $group_id = 101;
        $other_group_id = 102;
        
        $user = new MockUser();
        
        $controller = new MockGit($this);
        
        $factory = new MockGitRepositoryFactory();
        $repo1 = new MockGitRepository();
        $repo1->setReturnValue('getId', $id1);
        $repo1->setReturnValue('userCanRead', true, array($user));
        $repo1->expectNever('fork');
        $factory->setReturnValue('getRepository', $repo1, array($other_group_id, $id1));
        $factory->setReturnValue('getRepository', null, array($group_id, $id1));
        
        $repo2 = new MockGitRepository();
        $repo2->setReturnValue('getId', $id2);
        $repo2->setReturnValue('userCanRead', true, array($user));
        $repo2->expectOnce('fork', array($path, $user));
        $factory->setReturnValue('getRepository', $repo2, array($group_id, $id2));
        $factory->setReturnValue('getRepository', null, array($other_group_id, $id2));
        
        $systemEventManager = new MockSystemEventManager();
        $layout = new MockLayout();
        $layout->expectOnce('redirect');
        
        $action = new GitActions($controller, $factory, $systemEventManager);
        $action->forkRepositories($group_id, $repositories, $path, $user, $layout);
    }
    
    protected function getFactoryForFork($group_id, $repo_ids, $path, $user, array $unreadable = array()) {
        $factory = new MockGitRepositoryFactory();
        foreach ($repo_ids as $id) {
            $repo = new MockGitRepository();
            $repo->setReturnValue('getId', $id);
            if (in_array($id, $unreadable)) {
                $repo->setReturnValue('userCanRead', false, array($user));
                $repo->expectNever('fork');
            } else {
                $repo->setReturnValue('userCanRead', true, array($user));
                $repo->expectOnce('fork', array($path, $user));
            }
            
            $factory->setReturnValue('getRepository', $repo, array($group_id, $id));
        }
        return $factory;
    }

}

?>