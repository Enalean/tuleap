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
Mock::generate('GitForkIndividualCommand');
Mock::generate('Git_Backend_Gitolite');
class AbstractGitActionsTest extends UnitTestCase {
        function setUp() {
        $GLOBALS['Language'] = new MockBaseLanguage();
        $GLOBALS['Language']->setReturnValue('getText', 'actions_no_repository_forked', array('plugin_git', 'actions_no_repository_forked', '*'));
        $GLOBALS['Language']->setReturnValue('getText', 'successfully_forked', array('plugin_git', 'successfully_forked', '*'));
    }
    function tearDown() {
        unset($GLOBALS['Language']);
    }
}
class GitActionsTest extends AbstractGitActionsTest {

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

}
class GitActions_Fork_Test extends AbstractGitActionsTest {
    function testForkRepositories() {
        $path  = 'toto';
        $group_id = 101;
        
        $user = new MockUser();
        $user->setReturnValue('getId', 123);
        $project = new MockProject();
        $backend = new MockGit_Backend_Gitolite();
        $backend->expectOnce('fork');
        
        
        $repo = new MockGitRepository();
        $repo->setReturnValue('userCanRead', true, array($user));
        $repo->setReturnValue('getProject', $project);
        $repo->setReturnValue('getBackend', $backend);
        
        $layout = new MockLayout();
        $layout->expectOnce('redirect', array('/plugins/git/?group_id='. $group_id .'&user='. $user->getId()));
                
        $controller = new MockGit($this);
        $systemEventManager = new MockSystemEventManager();
        $action = new GitActions($controller, $systemEventManager);
        $action->forkRepositories($group_id, array($repo), $path, $user, $layout);
    }
    
    function testClonesManyInternalRepositories() {    	
    	$path  = 'toto';
    	$group_id = 101;
    	
    	$user = new MockUser();
    	$user->setReturnValue('getId', 123);
    	
    	$project = new MockProject();
    	
    	$layout = new MockLayout();
    	$layout->expectOnce('redirect', array('/plugins/git/?group_id='. $group_id .'&user='. $user->getId()));
    	
        $repo_ids = array('1', '2', '3');
        
        $repos = array();
        foreach ($repo_ids as $id) {
            $backend = new MockGit_Backend_Gitolite();
            $backend->expectOnce('fork');
        	$repo = new MockGitRepository();
        	$repo->setReturnValue('getId', $id);
        	$repo->setReturnValue('userCanRead', true, array($user));
        	$repo->setReturnValue('getProject', $project);
        	$repo->setReturnValue('getBackend', $backend);
        	$repos[] = $repo;
        }
        
        $controller = new MockGit($this);
        $systemEventManager = new MockSystemEventManager();
        $action = new GitActions($controller, $systemEventManager);
        $action->forkRepositories($group_id, $repos, $path, $user, $layout);
    }
    function testCloneManyExternalRepositories() {
        
        $path  = '';
        $group_id = 101;
         
        $user = new MockUser();
        $user->setReturnValue('getId', 123);
        $user->setReturnValue('isMember', true);

        $project_id = 2;
        $to_project = new MockProject();
        $to_project->setReturnValue('getId', $project_id);
         
        $repo_ids = array('1', '2', '3');        
        $repos = array();
        foreach ($repo_ids as $id) {
            $backend = new MockGit_Backend_Gitolite();
            $backend->expectOnce('fork');
            $repo = new MockGitRepository();
            $repo->setReturnValue('getId', $id);
            $repo->setReturnValue('userCanRead', true, array($user));
            $repo->setReturnValue('getProject', $to_project);
        	$repo->setReturnValue('getBackend', $backend);
            $repos[] = $repo;
        }
        
        $layout = new MockLayout();
        $layout->expectOnce('redirect', array('/plugins/git/?group_id='. $project_id));
        
        $controller = new MockGit($this);
        $systemEventManager = new MockSystemEventManager();
        $action = new GitActions($controller, $systemEventManager);
        
        $action->forkCrossProject($group_id, $repos, $to_project, $user, $layout);
        
    }
    
    function testWhenNoRepositorySelectedItAddsWarning() {
        $group_id = 101;

        $repos = array();
        $user = new MockUser();
                
        $layout = new MockLayout();
        $layout->expectNever('redirect');
        
        $controller = new MockGit($this);
        $controller->expectOnce('addError', array('actions_no_repository_forked'));
        $systemEventManager = new MockSystemEventManager();
        $action = new GitActions($controller, $systemEventManager);
        $action->forkRepositories($group_id, $repos, '', $user, $layout);
    }
    
    function testClonesOneRepository() {
        $id = '1';
        $group_id = 101;
        
        $user = new MockUser();
        $user->setReturnValue('getId', 123);
        $project = new MockProject();
        $project->setReturnValue('getId', 2);
        $project->setReturnValue('getUnixName', '');
        $backend = new MockGit_Backend_Gitolite();
        $backend->expectOnce('fork');
        $layout = new MockLayout();
        $layout->expectOnce('redirect', array('/plugins/git/?group_id='. $group_id .'&user='. $user->getId()));
        
        $repo = new MockGitRepository();
        $repo->setReturnValue('getId', $id);
        $repo->setReturnValue('userCanRead', true, array($user));
        $repo->setReturnValue('getBackend', $backend);
        $repo->setReturnValue('getProject', $project);
        $repos = array($repo);
        
        $controller = new MockGit($this);
        $systemEventManager = new MockSystemEventManager();
        
        $action = new GitActions($controller, $systemEventManager);
        $action->forkRepositories($group_id, $repos, '', $user, $layout);
    }
    

    function testDoesntCloneUnreadableRepos() {
        $repositories = array('1', '2', '3');
        $group_id = 101;
        
        $user = new MockUser();
        $user->setReturnValue('getId', 123);
        $repos = $this->getRepoCollectionUnreadableFor($repositories, $user);
        
        $to_project = new MockProject();
        $to_project->setReturnValue('getId', 2);
        
        $controller = new MockGit($this);
        
        $systemEventManager = new MockSystemEventManager();
        $layout = new MockLayout();
        $layout->expectNever('redirect');
        
        $action = new GitActions($controller, $systemEventManager);
        $action->forkCrossProject($group_id, $repos, $to_project, $user, $layout);
    }
    
    function testUserMustBeAdminOfTheDestinationProject() {
        $controller = new MockGit($this);
        $action = new GitActions($controller, new MockSystemEventManager());
        $layout = new MockLayout();

        $to_project = new MockProject();
        $to_project->setReturnValue('getId', 2);
        
        $user = new MockUser();
        $user->setReturnValue('isMember', false, array($to_project->getId(), 'A'));
        $layout->expectNever('redirect');
        $adminMsg = 'must_be_admin_to_create_project_repo';
        $GLOBALS['Language']->setReturnValue('getText', $adminMsg, array('plugin_git', $adminMsg, '*'));
        
        $controller->expectOnce('addError', array($adminMsg));
        
        $action->forkCrossProject(null, array(), $to_project, $user, $layout);
    }
    
    protected function getRepoCollectionUnreadableFor($repo_ids, $user) {
        $return = array();
        foreach ($repo_ids as $id) {
            $repo = new MockGitRepository();
            $repo->setReturnValue('getId', $id);
            $repo->setReturnValue('userCanRead', false, array($user));
            $repo->expectNever('fork');
            $return[] = $repo;
        }
        return $return;
    }
    
    public function testForkCrossProjectsRedirectToCrossProjectGitRepositories() {
        $repo_id = '1';
        $project_id = 2;
        
        $user = new MockUser();
        $user->setReturnValue('getId', 123);
        $user->setReturnValue('isMember', true, array($project_id, 'A'));
        $to_project = new MockProject();
        $to_project->setReturnValue('getId', $project_id);
        
        $backend = new MockGit_Backend_Gitolite();
        $backend->expectOnce('fork');
        
        $controller = new MockGit($this);
        $controller->expectOnce('addInfo', array('successfully_forked'));
        
        $repo = new MockGitRepository();
        $repo->setReturnValue('getId', $repo_id);
        $repo->setReturnValue('userCanRead', true, array($user));
        $repo->setReturnValue('getBackend', $backend);
        $repos = array($repo);
        
        $systemEventManager = new MockSystemEventManager();
        $layout = new MockLayout();
        $layout->expectOnce('redirect', array('/plugins/git/?group_id='. $project_id));
        
        $action = new GitActions($controller, $systemEventManager);
        $action->forkCrossProject($project_id, $repos, $to_project, $user, $layout);
    }

}

?>