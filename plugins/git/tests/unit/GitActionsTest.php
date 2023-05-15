<?php
/**
 * Copyright (c) Enalean, 2012 - Present. All Rights Reserved.
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

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Tuleap\Git\Notifications\UgroupsToNotifyDao;
use Tuleap\Git\Notifications\UsersToNotifyDao;
use Tuleap\GlobalLanguageMock;

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
class GitActionsTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;
    use GlobalLanguageMock;

    /**
     * @var \Mockery\MockInterface&GitActions
     */
    private $gitAction;

    protected function setUp(): void
    {
        parent::setUp();
        $GLOBALS['Language']->method('getText')->willReturnMap(
            [
                ['plugin_git', 'actions_no_repository_forked', self::any(), 'actions_no_repository_forked'],
                ['plugin_git', 'successfully_forked', self::any(), 'successfully_forked'],
            ]
        );

        $git_plugin = Mockery::mock(\GitPlugin::class)
            ->shouldReceive('areFriendlyUrlsActivated')
            ->andReturnFalse()
            ->getMock();

        $url_manager = new Git_GitRepositoryUrlManager($git_plugin);

        $this->gitAction = \Mockery::mock(
            \GitActions::class,
            [
                \Mockery::spy(\Git::class),
                \Mockery::spy(\Git_SystemEventManager::class),
                \Mockery::spy(\GitRepositoryFactory::class),
                \Mockery::spy(\GitRepositoryManager::class),
                \Mockery::spy(\Git_RemoteServer_GerritServerFactory::class),
                \Mockery::spy(\Git_Driver_Gerrit_GerritDriverFactory::class)
                    ->shouldReceive('getDriver')
                    ->andReturns(\Mockery::spy(\Git_Driver_Gerrit::class))
                    ->getMock(),
                \Mockery::spy(\Git_Driver_Gerrit_UserAccountManager::class),
                \Mockery::spy(\Git_Driver_Gerrit_ProjectCreator::class),
                \Mockery::spy(\Git_Driver_Gerrit_Template_TemplateFactory::class),
                \Mockery::spy(\ProjectManager::class),
                \Mockery::spy(\GitPermissionsManager::class),
                $url_manager,
                \Mockery::spy(\Psr\Log\LoggerInterface::class),
                Mockery::spy(ProjectHistoryDao::class),
                \Mockery::spy(\Tuleap\Git\RemoteServer\Gerrit\MigrationHandler::class),
                \Mockery::spy(\Tuleap\Git\RemoteServer\GerritCanMigrateChecker::class),
                \Mockery::spy(\Tuleap\Git\Permissions\FineGrainedUpdater::class),
                \Mockery::spy(\Tuleap\Git\Permissions\FineGrainedPermissionSaver::class),
                \Mockery::spy(\Tuleap\Git\Permissions\FineGrainedRetriever::class),
                \Mockery::spy(\Tuleap\Git\Permissions\HistoryValueFormatter::class),
                \Mockery::spy(\Tuleap\Git\Permissions\PermissionChangesDetector::class),
                \Mockery::spy(\Tuleap\Git\Permissions\RegexpFineGrainedEnabler::class),
                \Mockery::spy(\Tuleap\Git\Permissions\RegexpFineGrainedDisabler::class),
                \Mockery::spy(\Tuleap\Git\Permissions\RegexpPermissionFilter::class),
                \Mockery::spy(\Tuleap\Git\Permissions\RegexpFineGrainedRetriever::class),
                Mockery::mock(UsersToNotifyDao::class),
                Mockery::mock(UgroupsToNotifyDao::class),
                \Mockery::spy(\UGroupManager::class),
            ]
        )
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();
    }

    public function testNotificationUpdatePrefixFail(): void
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

    public function testNotificationUpdatePrefixPass(): void
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

    public function testNotificationAddMailFailNoRepoId(): void
    {
        $git = \Mockery::spy(\Git::class);
        $this->gitAction->setController($git);
        $gitRepository = \Mockery::spy(\GitRepository::class);
        $this->gitAction->shouldReceive('getGitRepository')->andReturns($gitRepository);

        $git->shouldReceive('addError')->with('Empty required parameter(s)')->once();
        $git->shouldReceive('addInfo')->never();

        $mails = ['john.doe@acme.com'];
        $this->assertFalse($this->gitAction->notificationAddMail(1, null, $mails, 'a_pane'));
    }

    public function testNotificationAddMailFailNoMails(): void
    {
        $git = \Mockery::spy(\Git::class);
        $this->gitAction->setController($git);
        $gitRepository = \Mockery::spy(\GitRepository::class);
        $this->gitAction->shouldReceive('getGitRepository')->andReturns($gitRepository);

        $git->shouldReceive('addError')->with('Empty required parameter(s)')->once();
        $git->shouldReceive('addInfo')->never();

        $this->assertFalse($this->gitAction->notificationAddMail(1, 1, null, 'a_pane'));
    }

    public function testNotificationAddMailFailAlreadyNotified(): void
    {
        $this->gitAction->shouldReceive('getText')->with('mail_existing', ['john.doe@acme.com'])->andReturns('mail_existing john.doe@acme.com');
        $this->gitAction->shouldReceive('getText')->with('mail_existing', ['jane.doe@acme.com'])->andReturns('mail_existing jane.doe@acme.com');
        $this->gitAction->shouldReceive('getText')->with('mail_existing', ['john.smith@acme.com'])->andReturns('mail_existing john.smith@acme.com');
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

        $mails = ['john.doe@acme.com',
            'jane.doe@acme.com',
            'john.smith@acme.com',
        ];
        $this->assertTrue($this->gitAction->notificationAddMail(1, 1, $mails, 'a_pane'));
    }

    public function testNotificationAddMailPartialPass(): void
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

        $mails = ['john.doe@acme.com',
            'jane.doe@acme.com',
            'john.smith@acme.com',
        ];
        $this->assertTrue($this->gitAction->notificationAddMail(1, 1, $mails, 'a_pane'));
    }

    public function testNotificationAddMailPass(): void
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

        $mails = ['john.doe@acme.com',
            'jane.doe@acme.com',
            'john.smith@acme.com',
        ];
        $this->assertTrue($this->gitAction->notificationAddMail(1, 1, $mails, 'a_pane'));
    }

    public function testNotificationRemoveMailFailNoRepoId(): void
    {
        $git = \Mockery::spy(\Git::class);
        $this->gitAction->setController($git);
        $gitRepository = \Mockery::spy(\GitRepository::class);
        $this->gitAction->shouldReceive('getGitRepository')->andReturns($gitRepository);

        $git->shouldReceive('addError')->with('Empty required parameter(s)')->once();
        $git->shouldReceive('addInfo')->never();

        $this->assertFalse($this->gitAction->notificationRemoveMail(1, null, 'john.doe@acme.com', 'a_pane'));
    }

    public function testNotificationRemoveMailFailNoMail(): void
    {
        $git = \Mockery::spy(\Git::class);
        $this->gitAction->setController($git);
        $gitRepository = \Mockery::spy(\GitRepository::class);
        $this->gitAction->shouldReceive('getGitRepository')->andReturns($gitRepository);

        $git->shouldReceive('addError')->with('Empty required parameter(s)')->once();
        $git->shouldReceive('addInfo')->never();

        $this->assertFalse($this->gitAction->notificationRemoveMail(1, 1, null, 'a_pane'));
    }

    public function testNotificationRemoveMailFailMailNotRemoved(): void
    {
        $git = \Mockery::spy(\Git::class);
        $this->gitAction->setController($git);
        $gitRepository = \Mockery::spy(\GitRepository::class);
        $gitRepository->shouldReceive('notificationRemoveMail')->andReturns(false);
        $this->gitAction->shouldReceive('getGitRepository')->andReturns($gitRepository);

        $git->shouldReceive('addError')->with('Could not remove mail john.doe@acme.com')->once();
        $git->shouldReceive('addInfo')->never();

        $this->assertFalse($this->gitAction->notificationRemoveMail(1, 1, ['john.doe@acme.com'], 'a_pane'));
    }

    public function testNotificationRemoveMailFailMailPass(): void
    {
        $git = \Mockery::spy(\Git::class);
        $this->gitAction->setController($git);
        $gitRepository = \Mockery::spy(\GitRepository::class);
        $gitRepository->shouldReceive('notificationRemoveMail')->andReturns(true);
        $this->gitAction->shouldReceive('getGitRepository')->andReturns($gitRepository);

        $git->shouldReceive('addError')->never();
        $git->shouldReceive('addInfo')->with('Mail john.doe@acme.com removed')->once();

        $this->assertTrue($this->gitAction->notificationRemoveMail(1, 1, ['john.doe@acme.com'], 'a_pane'));
    }

    public function testConfirmPrivateFailNoRepoId(): void
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

    public function testConfirmPrivateFailNoAccess(): void
    {
        $gitAction = \Mockery::mock(\GitActions::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $git       = \Mockery::spy(\Git::class);
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

    public function testConfirmPrivateFailNoDesc(): void
    {
        $gitAction = \Mockery::mock(\GitActions::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $git       = \Mockery::spy(\Git::class);
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

    public function testConfirmPrivateNotSettingToPrivate(): void
    {
        $gitAction = \Mockery::mock(\GitActions::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $git       = \Mockery::spy(\Git::class);
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

    public function testConfirmPrivateAlreadyPrivate(): void
    {
        $gitAction = \Mockery::mock(\GitActions::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $git       = \Mockery::spy(\Git::class);
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

    public function testConfirmPrivateNoMailsToDelete(): void
    {
        $gitAction = \Mockery::mock(\GitActions::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $git       = \Mockery::spy(\Git::class);
        $gitAction->setController($git);
        $gitRepository = \Mockery::spy(\GitRepository::class);
        $gitRepository->shouldReceive('getAccess')->andReturns('public');
        $gitAction->shouldReceive('getGitRepository')->andReturns($gitRepository);

        $git->shouldReceive('addError')->never();
        $git->shouldReceive('addWarn')->never();
        $gitRepository->shouldReceive('getNonMemberMails')->once()->andReturns([]);
        $gitRepository->shouldReceive('setDescription')->never();
        $gitRepository->shouldReceive('save')->never();
        $gitAction->shouldReceive('save')->once();

        $this->assertTrue($gitAction->confirmPrivate(1, 1, 'private', 'desc'));
    }

    public function testConfirmPrivate(): void
    {
        $gitAction = \Mockery::mock(\GitActions::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $git       = \Mockery::spy(\Git::class);
        $gitAction->setController($git);
        $gitRepository = \Mockery::spy(\GitRepository::class);
        $gitRepository->shouldReceive('getAccess')->andReturns('public');
        $gitAction->shouldReceive('getGitRepository')->andReturns($gitRepository);

        $git->shouldReceive('addError')->never();
        $git->shouldReceive('addWarn')->with('Making the repository access private will remove notification for all mail addresses that doesn\'t correspond to a user member of this project.')->once();
        $gitRepository->shouldReceive('getNonMemberMails')->once()->andReturns(['john.doe@acme.com']);
        $gitRepository->shouldReceive('setDescription')->once();
        $gitRepository->shouldReceive('save')->once();
        $gitAction->shouldReceive('save')->never();
        $gitAction->shouldReceive('addData')->times(3);

        $this->assertTrue($gitAction->confirmPrivate(1, 1, 'private', 'desc'));
    }

    public function testGetProjectRepositoryListShouldReturnProjectRepositories(): void
    {
        $projectId = 42;
        $userId    = 24;

        $project_repos = [
            [
                'id'   => '1',
                'name' => 'a',
            ],
            [
                'id'   => '2',
                'name' => 'b',
            ],
        ];

        $sandra_repos = [
            [
                'id'   => '3',
                'name' => 'c',
            ],
        ];

        $repo_owners = TestHelper::arrayToDar(
            [
                [
                    'id' => '123',
                ],
                [
                    'id' => '456',
                ],
            ]
        );

        $dao = \Mockery::spy(\GitDao::class);
        $dao->shouldReceive('getProjectRepositoryList')->with($projectId, true, null)->andReturns($project_repos);
        $dao->shouldReceive('getProjectRepositoryList')->with($projectId, true, $userId)->andReturns($sandra_repos);
        $dao->shouldReceive('getProjectRepositoriesOwners')->with($projectId)->andReturns($repo_owners);

        $controller = \Mockery::spy(\Git::class);
        $controller->shouldReceive('addData')->with(['repository_list' => $project_repos, 'repositories_owners' => $repo_owners])->ordered()->atLeast()->once();
        $controller->shouldReceive('addData')->with(['repository_list' => $sandra_repos, 'repositories_owners' => $repo_owners])->ordered()->atLeast()->once();

        $action = \Mockery::mock(\GitActions::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $action->setController($controller);
        $action->shouldReceive('getDao')->andReturns($dao);

        $action->getProjectRepositoryList($projectId);
        $action->getProjectRepositoryList($projectId, $userId);
    }
}
