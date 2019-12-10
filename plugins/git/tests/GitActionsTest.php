<?php
/**
 * Copyright (c) Enalean, 2012 - 2019. All Rights Reserved.
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

use Tuleap\Git\Notifications\UgroupsToNotifyDao;
use Tuleap\Git\Notifications\UsersToNotifyDao;

require_once 'bootstrap.php';
require_once 'builders/aGitRepository.php';

class GitActionsTest extends TuleapTestCase
{

    function setUp()
    {
        parent::setUp();
        $this->setUpGlobalsMockery();
        $GLOBALS['Language']->shouldReceive('getText')->with('plugin_git', 'actions_no_repository_forked', '*')->andReturns('actions_no_repository_forked');
        $GLOBALS['Language']->shouldReceive('getText')->with('plugin_git', 'successfully_forked', '*')->andReturns('successfully_forked');

        $git_plugin        = mockery_stub(\GitPlugin::class)->areFriendlyUrlsActivated()->returns(false);
        $url_manager       = new Git_GitRepositoryUrlManager($git_plugin, new \Tuleap\InstanceBaseURLBuilder());

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
            safe_mock(UsersToNotifyDao::class),
            safe_mock(UgroupsToNotifyDao::class),
            mock('UGroupManager')
        ))
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();
    }

    function testNotificationUpdatePrefixFail()
    {
        $git = \Mockery::spy(\Git::class);
        $this->gitAction->setController($git);
        $gitRepository = \Mockery::spy(\GitRepository::class);
        $this->gitAction->shouldReceive('getGitRepository')->andReturns($gitRepository);

        $git->shouldReceive('addError')->with('Empty required parameter(s)')->once();
        $git->shouldReceive('addInfo')->never();
        $gitRepository->shouldReceive('setMailPrefix')->never();
        $gitRepository->shouldReceive('changeMailPrefix')->never();
        $this->gitAction->shouldReceive('addData')->never();

        $this->assertFalse($this->gitAction->notificationUpdatePrefix(1, null, '[new prefix]', 'a_pane'));
    }

    function testNotificationUpdatePrefixPass()
    {
        $git = \Mockery::spy(\Git::class);
        $this->gitAction->setController($git);
        $gitRepository = \Mockery::spy(\GitRepository::class);
        $this->gitAction->shouldReceive('getGitRepository')->andReturns($gitRepository);

        $git->shouldReceive('addError')->never();
        $git->shouldReceive('addInfo')->with('Mail prefix updated')->once();
        $gitRepository->shouldReceive('setMailPrefix')->once();
        $gitRepository->shouldReceive('changeMailPrefix')->once();
        $this->gitAction->shouldReceive('addData')->times(2);

        $this->assertTrue($this->gitAction->notificationUpdatePrefix(1, 1, '[new prefix]', 'a_pane'));
    }

    function testNotificationAddMailFailNoRepoId()
    {
        $git = \Mockery::spy(\Git::class);
        $this->gitAction->setController($git);
        $gitRepository = \Mockery::spy(\GitRepository::class);
        $this->gitAction->shouldReceive('getGitRepository')->andReturns($gitRepository);

        $git->shouldReceive('addError')->with('Empty required parameter(s)')->once();
        $git->shouldReceive('addInfo')->never();

        $mails = array('john.doe@acme.com');
        $this->assertFalse($this->gitAction->notificationAddMail(1, null, $mails, 'a_pane'));
    }

    function testNotificationAddMailFailNoMails()
    {
        $git = \Mockery::spy(\Git::class);
        $this->gitAction->setController($git);
        $gitRepository = \Mockery::spy(\GitRepository::class);
        $this->gitAction->shouldReceive('getGitRepository')->andReturns($gitRepository);

        $git->shouldReceive('addError')->with('Empty required parameter(s)')->once();
        $git->shouldReceive('addInfo')->never();

        $this->assertFalse($this->gitAction->notificationAddMail(1, 1, null, 'a_pane'));
    }

    function testNotificationAddMailFailAlreadyNotified()
    {
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

    function testNotificationAddMailPartialPass()
    {
        $git = \Mockery::spy(\Git::class);
        $this->gitAction->setController($git);
        $gitRepository = \Mockery::spy(\GitRepository::class);
        $gitRepository->shouldReceive('isAlreadyNotified')->andReturns(false);
        $gitRepository->shouldReceive('notificationAddMail')->with('john.doe@acme.com')->andReturns(false);
        $gitRepository->shouldReceive('notificationAddMail')->with('jane.doe@acme.com')->andReturns(true);
        $gitRepository->shouldReceive('notificationAddMail')->with('john.smith@acme.com')->andReturns(false);
        $this->gitAction->shouldReceive('getGitRepository')->andReturns($gitRepository);

        $git->shouldReceive('addError')->times(2);
        $git->shouldReceive('addError')->with('Could not remove mail john.doe@acme.com')->ordered();
        $git->shouldReceive('addError')->with('Could not remove mail john.smith@acme.com')->ordered();
        $git->shouldReceive('addInfo')->never();

        $mails = array('john.doe@acme.com',
                       'jane.doe@acme.com',
                       'john.smith@acme.com');
        $this->assertTrue($this->gitAction->notificationAddMail(1, 1, $mails, 'a_pane'));
    }

    function testNotificationAddMailPass()
    {
        $git = \Mockery::spy(\Git::class);
        $this->gitAction->setController($git);
        $gitRepository = \Mockery::spy(\GitRepository::class);
        $gitRepository->shouldReceive('isAlreadyNotified')->andReturns(false);
        $gitRepository->shouldReceive('notificationAddMail')->with('john.doe@acme.com')->andReturns(true);
        $gitRepository->shouldReceive('notificationAddMail')->with('jane.doe@acme.com')->andReturns(true);
        $gitRepository->shouldReceive('notificationAddMail')->with('john.smith@acme.com')->andReturns(true);
        $this->gitAction->shouldReceive('getGitRepository')->andReturns($gitRepository);

        $git->shouldReceive('addError')->never();
        $git->shouldReceive('addInfo')->with('Mail added')->once();

        $mails = array('john.doe@acme.com',
                       'jane.doe@acme.com',
                       'john.smith@acme.com');
        $this->assertTrue($this->gitAction->notificationAddMail(1, 1, $mails, 'a_pane'));
    }

    function testNotificationRemoveMailFailNoRepoId()
    {
        $git = \Mockery::spy(\Git::class);
        $this->gitAction->setController($git);
        $gitRepository = \Mockery::spy(\GitRepository::class);
        $this->gitAction->shouldReceive('getGitRepository')->andReturns($gitRepository);

        $git->shouldReceive('addError')->with('Empty required parameter(s)')->once();
        $git->shouldReceive('addInfo')->never();

        $this->assertFalse($this->gitAction->notificationRemoveMail(1, null, 'john.doe@acme.com', 'a_pane'));
    }

    function testNotificationRemoveMailFailNoMail()
    {
        $git = \Mockery::spy(\Git::class);
        $this->gitAction->setController($git);
        $gitRepository = \Mockery::spy(\GitRepository::class);
        $this->gitAction->shouldReceive('getGitRepository')->andReturns($gitRepository);

        $git->shouldReceive('addError')->with('Empty required parameter(s)')->once();
        $git->shouldReceive('addInfo')->never();

        $this->assertFalse($this->gitAction->notificationRemoveMail(1, 1, null, 'a_pane'));
    }

    function testNotificationRemoveMailFailMailNotRemoved()
    {
        $git = \Mockery::spy(\Git::class);
        $this->gitAction->setController($git);
        $gitRepository = \Mockery::spy(\GitRepository::class);
        $gitRepository->shouldReceive('notificationRemoveMail')->andReturns(false);
        $this->gitAction->shouldReceive('getGitRepository')->andReturns($gitRepository);

        $git->shouldReceive('addError')->with('Could not remove mail john.doe@acme.com')->once();
        $git->shouldReceive('addInfo')->never();

        $this->assertFalse($this->gitAction->notificationRemoveMail(1, 1, array('john.doe@acme.com'), 'a_pane'));
    }

    function testNotificationRemoveMailFailMailPass()
    {
        $git = \Mockery::spy(\Git::class);
        $this->gitAction->setController($git);
        $gitRepository = \Mockery::spy(\GitRepository::class);
        $gitRepository->shouldReceive('notificationRemoveMail')->andReturns(true);
        $this->gitAction->shouldReceive('getGitRepository')->andReturns($gitRepository);

        $git->shouldReceive('addError')->never();
        $git->shouldReceive('addInfo')->with('Mail john.doe@acme.com removed')->once();

        $this->assertTrue($this->gitAction->notificationRemoveMail(1, 1, array('john.doe@acme.com'), 'a_pane'));
    }

    function testConfirmPrivateFailNoRepoId()
    {
        $git = \Mockery::spy(\Git::class);
        $this->gitAction->setController($git);
        $gitRepository = \Mockery::spy(\GitRepository::class);
        $this->gitAction->shouldReceive('getGitRepository')->andReturns($gitRepository);

        $git->shouldReceive('addError')->with('Empty required parameter(s)')->once();
        $git->shouldReceive('addWarn')->never();
        $gitRepository->shouldReceive('getNonMemberMails')->never();
        $gitRepository->shouldReceive('setDescription')->never();
        $gitRepository->shouldReceive('save')->never();
        $this->gitAction->shouldReceive('save')->never();

        $this->assertFalse($this->gitAction->confirmPrivate(1, null, 'private', 'desc'));
    }

    function testConfirmPrivateFailNoAccess()
    {
        $gitAction = \Mockery::mock(\GitActions::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $git = \Mockery::spy(\Git::class);
        $gitAction->setController($git);
        $gitRepository = \Mockery::spy(\GitRepository::class);
        $gitAction->shouldReceive('getGitRepository')->andReturns($gitRepository);

        $git->shouldReceive('addError')->with('Empty required parameter(s)')->once();
        $git->shouldReceive('addWarn')->never();
        $gitRepository->shouldReceive('getNonMemberMails')->never();
        $gitRepository->shouldReceive('setDescription')->never();
        $gitRepository->shouldReceive('save')->never();
        $gitAction->shouldReceive('save')->never();

        $this->assertFalse($gitAction->confirmPrivate(1, 1, null, 'desc'));
    }

    function testConfirmPrivateFailNoDesc()
    {
        $gitAction = \Mockery::mock(\GitActions::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $git = \Mockery::spy(\Git::class);
        $gitAction->setController($git);
        $gitRepository = \Mockery::spy(\GitRepository::class);
        $gitAction->shouldReceive('getGitRepository')->andReturns($gitRepository);

        $git->shouldReceive('addError')->with('Empty required parameter(s)')->once();
        $git->shouldReceive('addWarn')->never();
        $gitRepository->shouldReceive('getNonMemberMails')->never();
        $gitRepository->shouldReceive('setDescription')->never();
        $gitRepository->shouldReceive('save')->never();
        $gitAction->shouldReceive('save')->never();

        $this->assertFalse($gitAction->confirmPrivate(1, 1, 'private', null));
    }

    function testConfirmPrivateNotSettingToPrivate()
    {
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

    function testConfirmPrivateAlreadyPrivate()
    {
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

    function testConfirmPrivateNoMailsToDelete()
    {
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

    function testConfirmPrivate()
    {
        $gitAction = \Mockery::mock(\GitActions::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $git = \Mockery::spy(\Git::class);
        $gitAction->setController($git);
        $gitRepository = \Mockery::spy(\GitRepository::class);
        $gitRepository->shouldReceive('getAccess')->andReturns('public');
        $gitAction->shouldReceive('getGitRepository')->andReturns($gitRepository);

        $git->shouldReceive('addError')->never();
        $git->shouldReceive('addWarn')->with('Making the repository access private will remove notification for all mail addresses that doesn\'t correspond to a user member of this project.')->once();
        $gitRepository->shouldReceive('getNonMemberMails')->once()->andReturns(array('john.doe@acme.com'));
        $gitRepository->shouldReceive('setDescription')->once();
        $gitRepository->shouldReceive('save')->once();
        $gitAction->shouldReceive('save')->never();
        $gitAction->shouldReceive('addData')->times(3);

        $this->assertTrue($gitAction->confirmPrivate(1, 1, 'private', 'desc'));
    }

    function testGetProjectRepositoryListShouldReturnProjectRepositories()
    {
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
