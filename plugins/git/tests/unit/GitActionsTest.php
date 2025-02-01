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

declare(strict_types=1);

namespace Tuleap\Git;

use Git;
use Git_Backend_Gitolite;
use Git_Driver_Gerrit;
use Git_Driver_Gerrit_GerritDriverFactory;
use Git_Driver_Gerrit_ProjectCreator;
use Git_Driver_Gerrit_Template_TemplateFactory;
use Git_Driver_Gerrit_UserAccountManager;
use Git_GitRepositoryUrlManager;
use Git_RemoteServer_GerritServerFactory;
use Git_SystemEventManager;
use GitActions;
use GitDao;
use GitPermissionsManager;
use GitPlugin;
use GitRepository;
use GitRepositoryFactory;
use GitRepositoryManager;
use PHPUnit\Framework\MockObject\MockObject;
use ProjectHistoryDao;
use ProjectManager;
use Psr\Log\NullLogger;
use TestHelper;
use Tuleap\Git\Notifications\UgroupsToNotifyDao;
use Tuleap\Git\Notifications\UsersToNotifyDao;
use Tuleap\Git\Permissions\FineGrainedPermissionSaver;
use Tuleap\Git\Permissions\FineGrainedRetriever;
use Tuleap\Git\Permissions\FineGrainedUpdater;
use Tuleap\Git\Permissions\HistoryValueFormatter;
use Tuleap\Git\Permissions\PermissionChangesDetector;
use Tuleap\Git\Permissions\RegexpFineGrainedDisabler;
use Tuleap\Git\Permissions\RegexpFineGrainedEnabler;
use Tuleap\Git\Permissions\RegexpFineGrainedRetriever;
use Tuleap\Git\Permissions\RegexpPermissionFilter;
use Tuleap\Git\RemoteServer\Gerrit\MigrationHandler;
use Tuleap\Git\RemoteServer\GerritCanMigrateChecker;
use Tuleap\GlobalLanguageMock;
use Tuleap\Test\PHPUnit\TestCase;
use UGroupManager;

final class GitActionsTest extends TestCase
{
    use GlobalLanguageMock;

    private GitActions&MockObject $gitAction;

    protected function setUp(): void
    {
        $GLOBALS['Language']->method('getText')->willReturnMap(
            [
                ['plugin_git', 'actions_no_repository_forked', self::any(), 'actions_no_repository_forked'],
                ['plugin_git', 'successfully_forked', self::any(), 'successfully_forked'],
            ]
        );

        $git_plugin = $this->createMock(GitPlugin::class);
        $git_plugin->method('areFriendlyUrlsActivated')->willReturn(false);
        $url_manager = new Git_GitRepositoryUrlManager($git_plugin);

        $driver_factory = $this->createMock(Git_Driver_Gerrit_GerritDriverFactory::class);
        $driver_factory->method('getDriver')->willReturn($this->createMock(Git_Driver_Gerrit::class));
        $git = $this->createMock(Git::class);
        $git->method('getUser');
        $git->method('getRequest');
        $git_system_event_manager = $this->createMock(Git_SystemEventManager::class);
        $git_system_event_manager->method('queueRepositoryUpdate');
        $history_dao = $this->createMock(ProjectHistoryDao::class);
        $history_dao->method('groupAddHistory');
        $this->gitAction = $this->getMockBuilder(GitActions::class)
            ->setConstructorArgs([
                $git,
                $git_system_event_manager,
                $this->createMock(GitRepositoryFactory::class),
                $this->createMock(GitRepositoryManager::class),
                $this->createMock(Git_RemoteServer_GerritServerFactory::class),
                $driver_factory,
                $this->createMock(Git_Driver_Gerrit_UserAccountManager::class),
                $this->createMock(Git_Driver_Gerrit_ProjectCreator::class),
                $this->createMock(Git_Driver_Gerrit_Template_TemplateFactory::class),
                $this->createMock(ProjectManager::class),
                $this->createMock(GitPermissionsManager::class),
                $url_manager,
                new NullLogger(),
                $history_dao,
                $this->createMock(MigrationHandler::class),
                $this->createMock(GerritCanMigrateChecker::class),
                $this->createMock(FineGrainedUpdater::class),
                $this->createMock(FineGrainedPermissionSaver::class),
                $this->createMock(FineGrainedRetriever::class),
                $this->createMock(HistoryValueFormatter::class),
                $this->createMock(PermissionChangesDetector::class),
                $this->createMock(RegexpFineGrainedEnabler::class),
                $this->createMock(RegexpFineGrainedDisabler::class),
                $this->createMock(RegexpPermissionFilter::class),
                $this->createMock(RegexpFineGrainedRetriever::class),
                $this->createMock(UsersToNotifyDao::class),
                $this->createMock(UgroupsToNotifyDao::class),
                $this->createMock(UGroupManager::class),
            ])
            ->onlyMethods(['getGitRepository', 'addData', 'save'])
            ->getMock();
    }

    public function testNotificationUpdatePrefixFail(): void
    {
        $git = $this->createMock(Git::class);
        $this->gitAction->setController($git);
        $gitRepository = $this->createMock(GitRepository::class);
        $this->gitAction->method('getGitRepository')->willReturn($gitRepository);

        $git->expects(self::once())->method('addError')->with('Empty required parameter(s)');
        $git->expects(self::never())->method('addInfo');
        $gitRepository->expects(self::never())->method('setMailPrefix');
        $gitRepository->expects(self::never())->method('changeMailPrefix');
        $this->gitAction->expects(self::never())->method('addData');

        self::assertFalse($this->gitAction->notificationUpdatePrefix(1, null, '[new prefix]', 'a_pane'));
    }

    public function testNotificationUpdatePrefixPass(): void
    {
        $git = $this->createMock(Git::class);
        $this->gitAction->setController($git);
        $gitRepository = new GitRepository();
        $this->gitAction->method('getGitRepository')->willReturn($gitRepository);

        $git->expects(self::never())->method('addError');
        $git->expects(self::once())->method('addInfo')->with('Mail prefix updated');
        $backend = $this->createMock(Git_Backend_Gitolite::class);
        $backend->method('save');
        $backend->method('changeRepositoryMailPrefix');
        $gitRepository->setBackend($backend);
        $this->gitAction->expects(self::exactly(2))->method('addData');

        self::assertTrue($this->gitAction->notificationUpdatePrefix(1, 1, '[new prefix]', 'a_pane'));
    }

    public function testNotificationAddMailFailNoRepoId(): void
    {
        $git = $this->createMock(Git::class);
        $this->gitAction->setController($git);
        $this->gitAction->method('getGitRepository')->willReturn(null);

        $git->expects(self::exactly(2))->method('addError')->withConsecutive(
            ['The repository does not exist'],
            ['Empty required parameter(s)'],
        );
        $git->expects(self::never())->method('addInfo');
        $git->method('redirect');
        $this->gitAction->method('addData');

        $mails = ['john.doe@acme.com'];
        self::assertFalse($this->gitAction->notificationAddMail(1, null, $mails, 'a_pane'));
    }

    public function testNotificationAddMailFailNoMails(): void
    {
        $git = $this->createMock(Git::class);
        $this->gitAction->setController($git);
        $gitRepository = new GitRepository();
        $this->gitAction->method('getGitRepository')->willReturn($gitRepository);

        $git->expects(self::once())->method('addError')->with('Empty required parameter(s)');
        $git->expects(self::never())->method('addInfo');
        $this->gitAction->method('addData');

        self::assertFalse($this->gitAction->notificationAddMail(1, 1, null, 'a_pane'));
    }

    public function testNotificationAddMailFailAlreadyNotified(): void
    {
        $git = $this->createMock(Git::class);
        $this->gitAction->setController($git);
        $gitRepository = $this->createMock(GitRepository::class);
        $gitRepository->method('isAlreadyNotified')->willReturn(true);
        $gitRepository->method('notificationAddMail')->willReturn(false);
        $gitRepository->method('getName');
        $gitRepository->method('getProjectId');
        $this->gitAction->method('getGitRepository')->willReturn($gitRepository);
        $this->gitAction->method('addData');

        $git->expects(self::never())->method('addError');
        $git->expects(self::exactly(3))->method('addInfo')->withConsecutive(
            ['The notification is already enabled for this email john.doe@acme.com'],
            ['The notification is already enabled for this email jane.doe@acme.com'],
            ['The notification is already enabled for this email john.smith@acme.com'],
        );

        $mails = ['john.doe@acme.com',
            'jane.doe@acme.com',
            'john.smith@acme.com',
        ];
        self::assertTrue($this->gitAction->notificationAddMail(1, 1, $mails, 'a_pane'));
    }

    public function testNotificationAddMailPartialPass(): void
    {
        $git = $this->createMock(Git::class);
        $this->gitAction->setController($git);
        $gitRepository = $this->createMock(GitRepository::class);
        $gitRepository->method('isAlreadyNotified')->willReturn(false);
        $gitRepository->method('notificationAddMail')->willReturnCallback(static fn(string $mail) => $mail === 'jane.doe@acme.com');
        $gitRepository->method('getName');
        $gitRepository->method('getProjectId');
        $this->gitAction->method('getGitRepository')->willReturn($gitRepository);
        $this->gitAction->method('addData');

        $git->expects(self::exactly(2))->method('addError')
            ->withConsecutive(
                ['Could not add mail john.doe@acme.com'],
                ['Could not add mail john.smith@acme.com'],
            );
        $git->expects(self::never())->method('addInfo');

        $mails = ['john.doe@acme.com',
            'jane.doe@acme.com',
            'john.smith@acme.com',
        ];
        self::assertTrue($this->gitAction->notificationAddMail(1, 1, $mails, 'a_pane'));
    }

    public function testNotificationAddMailPass(): void
    {
        $git = $this->createMock(Git::class);
        $this->gitAction->setController($git);
        $gitRepository = $this->createMock(GitRepository::class);
        $gitRepository->method('isAlreadyNotified')->willReturn(false);
        $gitRepository->method('notificationAddMail')->willReturn(true);
        $gitRepository->method('getName');
        $gitRepository->method('getProjectId');
        $this->gitAction->method('getGitRepository')->willReturn($gitRepository);
        $this->gitAction->method('addData');

        $git->expects(self::never())->method('addError');
        $git->expects(self::once())->method('addInfo')->with('Mail added');

        $mails = ['john.doe@acme.com',
            'jane.doe@acme.com',
            'john.smith@acme.com',
        ];
        self::assertTrue($this->gitAction->notificationAddMail(1, 1, $mails, 'a_pane'));
    }

    public function testNotificationRemoveMailFailNoRepoId(): void
    {
        $git = $this->createMock(Git::class);
        $this->gitAction->setController($git);
        $this->gitAction->method('getGitRepository')->willReturn(null);

        $git->expects(self::exactly(2))->method('addError')->withConsecutive(
            ['The repository does not exist'],
            ['Empty required parameter(s)'],
        );
        $git->expects(self::never())->method('addInfo');
        $git->method('redirect');

        self::assertFalse($this->gitAction->notificationRemoveMail(1, null, 'john.doe@acme.com', 'a_pane'));
    }

    public function testNotificationRemoveMailFailNoMail(): void
    {
        $git = $this->createMock(Git::class);
        $this->gitAction->setController($git);
        $gitRepository = new GitRepository();
        $this->gitAction->method('getGitRepository')->willReturn($gitRepository);
        $this->gitAction->method('addData');

        $git->expects(self::once())->method('addError')->with('Empty required parameter(s)');
        $git->expects(self::never())->method('addInfo');

        self::assertFalse($this->gitAction->notificationRemoveMail(1, 1, null, 'a_pane'));
    }

    public function testNotificationRemoveMailFailMailNotRemoved(): void
    {
        $git = $this->createMock(Git::class);
        $this->gitAction->setController($git);
        $gitRepository = $this->createMock(GitRepository::class);
        $gitRepository->method('notificationRemoveMail')->willReturn(false);
        $gitRepository->method('getName');
        $gitRepository->method('getProjectId');
        $this->gitAction->method('getGitRepository')->willReturn($gitRepository);
        $this->gitAction->method('addData');

        $git->expects(self::once())->method('addError')->with('Could not remove mail john.doe@acme.com');
        $git->expects(self::never())->method('addInfo');

        self::assertFalse($this->gitAction->notificationRemoveMail(1, 1, ['john.doe@acme.com'], 'a_pane'));
    }

    public function testNotificationRemoveMailFailMailPass(): void
    {
        $git = $this->createMock(Git::class);
        $this->gitAction->setController($git);
        $gitRepository = $this->createMock(GitRepository::class);
        $gitRepository->method('notificationRemoveMail')->willReturn(true);
        $gitRepository->method('getName');
        $gitRepository->method('getProjectId');
        $this->gitAction->method('getGitRepository')->willReturn($gitRepository);
        $this->gitAction->method('addData');

        $git->expects(self::never())->method('addError');
        $git->expects(self::once())->method('addInfo')->with('Mail john.doe@acme.com removed');

        self::assertTrue($this->gitAction->notificationRemoveMail(1, 1, ['john.doe@acme.com'], 'a_pane'));
    }

    public function testConfirmPrivateFailNoRepoId(): void
    {
        $git = $this->createMock(Git::class);
        $this->gitAction->setController($git);
        $gitRepository = $this->createMock(GitRepository::class);
        $this->gitAction->method('getGitRepository')->willReturn($gitRepository);

        $git->expects(self::once())->method('addError')->with('Empty required parameter(s)');
        $git->expects(self::never())->method('addWarn');
        $gitRepository->expects(self::never())->method('getNonMemberMails');
        $gitRepository->expects(self::never())->method('setDescription');
        $gitRepository->expects(self::never())->method('save');
        $this->gitAction->expects(self::never())->method('save');

        self::assertFalse($this->gitAction->confirmPrivate(1, null, 'private', 'desc'));
    }

    public function testConfirmPrivateFailNoAccess(): void
    {
        $gitAction = $this->createPartialMock(GitActions::class, ['getGitRepository', 'save']);
        $git       = $this->createMock(Git::class);
        $gitAction->setController($git);
        $gitRepository = $this->createMock(GitRepository::class);
        $gitAction->method('getGitRepository')->willReturn($gitRepository);

        $git->expects(self::once())->method('addError')->with('Empty required parameter(s)');
        $git->expects(self::never())->method('addWarn');
        $gitRepository->expects(self::never())->method('getNonMemberMails');
        $gitRepository->expects(self::never())->method('setDescription');
        $gitRepository->expects(self::never())->method('save');
        $gitAction->expects(self::never())->method('save');

        self::assertFalse($gitAction->confirmPrivate(1, 1, null, 'desc'));
    }

    public function testConfirmPrivateFailNoDesc(): void
    {
        $gitAction = $this->createPartialMock(GitActions::class, ['getGitRepository', 'save']);
        $git       = $this->createMock(Git::class);
        $gitAction->setController($git);
        $gitRepository = $this->createMock(GitRepository::class);
        $gitAction->method('getGitRepository')->willReturn($gitRepository);

        $git->expects(self::once())->method('addError')->with('Empty required parameter(s)');
        $git->expects(self::never())->method('addWarn');
        $gitRepository->expects(self::never())->method('getNonMemberMails');
        $gitRepository->expects(self::never())->method('setDescription');
        $gitRepository->expects(self::never())->method('save');
        $gitAction->expects(self::never())->method('save');

        self::assertFalse($gitAction->confirmPrivate(1, 1, 'private', null));
    }

    public function testConfirmPrivateNotSettingToPrivate(): void
    {
        $gitAction = $this->createPartialMock(GitActions::class, ['getGitRepository', 'save']);
        $git       = $this->createMock(Git::class);
        $gitAction->setController($git);
        $gitRepository = $this->createMock(GitRepository::class);
        $gitRepository->method('getAccess')->willReturn('public');
        $gitAction->method('getGitRepository')->willReturn($gitRepository);
        $git->method('addData');

        $git->expects(self::never())->method('addError');
        $git->expects(self::never())->method('addWarn');
        $gitRepository->expects(self::never())->method('getNonMemberMails');
        $gitRepository->expects(self::never())->method('setDescription');
        $gitRepository->expects(self::never())->method('save');
        $gitAction->expects(self::once())->method('save');

        self::assertTrue($gitAction->confirmPrivate(1, 1, 'public', 'desc'));
    }

    public function testConfirmPrivateAlreadyPrivate(): void
    {
        $gitAction = $this->createPartialMock(GitActions::class, ['getGitRepository', 'save']);
        $git       = $this->createMock(Git::class);
        $gitAction->setController($git);
        $gitRepository = $this->createMock(GitRepository::class);
        $gitRepository->method('getAccess')->willReturn('private');
        $gitAction->method('getGitRepository')->willReturn($gitRepository);
        $git->method('addData');

        $git->expects(self::never())->method('addError');
        $git->expects(self::never())->method('addWarn');
        $gitRepository->expects(self::never())->method('getNonMemberMails');
        $gitRepository->expects(self::never())->method('setDescription');
        $gitRepository->expects(self::never())->method('save');
        $gitAction->expects(self::once())->method('save');

        self::assertTrue($gitAction->confirmPrivate(1, 1, 'private', 'desc'));
    }

    public function testConfirmPrivateNoMailsToDelete(): void
    {
        $gitAction = $this->createPartialMock(GitActions::class, ['getGitRepository', 'save']);
        $git       = $this->createMock(Git::class);
        $gitAction->setController($git);
        $gitRepository = $this->createMock(GitRepository::class);
        $gitRepository->method('getAccess')->willReturn('public');
        $gitAction->method('getGitRepository')->willReturn($gitRepository);
        $git->method('addData');

        $git->expects(self::never())->method('addError');
        $git->expects(self::never())->method('addWarn');
        $gitRepository->expects(self::once())->method('getNonMemberMails')->willReturn([]);
        $gitRepository->expects(self::never())->method('setDescription');
        $gitRepository->expects(self::never())->method('save');
        $gitAction->expects(self::once())->method('save');

        self::assertTrue($gitAction->confirmPrivate(1, 1, 'private', 'desc'));
    }

    public function testConfirmPrivate(): void
    {
        $gitAction = $this->createPartialMock(GitActions::class, ['getGitRepository', 'save', 'addData']);
        $git       = $this->createMock(Git::class);
        $gitAction->setController($git);
        $gitRepository = $this->createMock(GitRepository::class);
        $gitRepository->method('getAccess')->willReturn('public');
        $gitAction->method('getGitRepository')->willReturn($gitRepository);

        $git->expects(self::never())->method('addError');
        $git->expects(self::once())->method('addWarn')->with('Making the repository access private will remove notification for all mail addresses that doesn\'t correspond to a user member of this project.');
        $gitRepository->expects(self::once())->method('getNonMemberMails')->willReturn(['john.doe@acme.com']);
        $gitRepository->expects(self::once())->method('setDescription');
        $gitRepository->expects(self::once())->method('save');
        $gitAction->expects(self::never())->method('save');
        $gitAction->expects(self::exactly(3))->method('addData');

        self::assertTrue($gitAction->confirmPrivate(1, 1, 'private', 'desc'));
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

        $repo_owners = TestHelper::arrayToDar([
            [
                'id' => '123',
            ],
            [
                'id' => '456',
            ],
        ]);

        $dao = $this->createMock(GitDao::class);
        $dao->method('getProjectRepositoryList')->willReturnCallback(static fn(int $project, bool $scope, ?int $user_id) => match ($user_id) {
            null    => $project_repos,
            $userId => $sandra_repos,
        });
        $dao->method('getProjectRepositoriesOwners')->with($projectId)->willReturn($repo_owners);

        $controller = $this->createMock(Git::class);
        $controller->expects(self::atLeast(2))->method('addData')->withConsecutive(
            [['repository_list' => $project_repos, 'repositories_owners' => $repo_owners]],
            [['repository_list' => $sandra_repos, 'repositories_owners' => $repo_owners]],
        );

        $action = $this->createPartialMock(GitActions::class, ['getDao']);
        $action->setController($controller);
        $action->method('getDao')->willReturn($dao);

        $action->getProjectRepositoryList($projectId);
        $action->getProjectRepositoryList($projectId, $userId);
    }
}
