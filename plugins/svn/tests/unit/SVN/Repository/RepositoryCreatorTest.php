<?php
/**
 * Copyright (c) Enalean, 2017 - present. All Rights Reserved.
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

namespace Tuleap\SVN\Repository;

use PHPUnit\Framework\MockObject\MockObject;
use Tuleap\SVN\AccessControl\AccessFileHistory;
use Tuleap\SVN\AccessControl\AccessFileHistoryCreator;
use Tuleap\SVN\Admin\ImmutableTag;
use Tuleap\SVN\Dao;
use Tuleap\SVN\Repository\Exception\RepositoryNameIsInvalidException;
use Tuleap\SVN\Repository\Exception\UserIsNotSVNAdministratorException;
use Tuleap\SVN\SvnPermissionManager;
use Tuleap\SVNCore\Repository;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;

final class RepositoryCreatorTest extends \Tuleap\Test\PHPUnit\TestCase
{
    /**
     * @var AccessFileHistoryCreator&MockObject
     */
    private $history_creator;
    /**
     * @var \ProjectHistoryDao&MockObject
     */
    private $history_dao;
    /**
     * @var HookConfigUpdator&MockObject
     */
    private $hook_config_updator;
    /**
     * @var Dao&MockObject
     */
    private $dao;
    private \Project $project;
    /**
     * @var SvnPermissionManager&MockObject
     */
    private $permissions_manager;
    private \PFUser $user;
    /**
     * @var \SystemEventManager&MockObject
     */
    private $system_event_manager;
    private Repository $repository;
    private RepositoryCreator $repository_creator;

    protected function setUp(): void
    {
        $this->system_event_manager = $this->createMock(\SystemEventManager::class);
        $this->history_dao          = $this->createMock(\ProjectHistoryDao::class);
        $this->dao                  = $this->createMock(\Tuleap\SVN\Dao::class);
        $this->permissions_manager  = $this->createMock(\Tuleap\SVN\SvnPermissionManager::class);
        $this->hook_config_updator  = $this->createMock(\Tuleap\SVN\Repository\HookConfigUpdator::class);
        $this->history_creator      = $this->createMock(\Tuleap\SVN\AccessControl\AccessFileHistoryCreator::class);
        $this->repository_creator   = new RepositoryCreator(
            $this->dao,
            $this->system_event_manager,
            $this->history_dao,
            $this->permissions_manager,
            $this->hook_config_updator,
            new ProjectHistoryFormatter(),
            $this->createMock(\Tuleap\SVN\Admin\ImmutableTagCreator::class),
            $this->history_creator,
            $this->createMock(\Tuleap\SVN\Admin\MailNotificationManager::class)
        );

        $this->project    = ProjectTestBuilder::aProject()->withId(101)->withUnixName('project-unix-name')->build();
        $this->user       = UserTestBuilder::aUser()->withId(110)->build();
        $this->repository = SvnRepository::buildActiveRepository(1, 'repo01', $this->project);

        $this->dao->method('create')->willReturn(1);
    }

    public function testItCreatesTheRepository(): void
    {
        $this->permissions_manager->method('isAdmin')->with($this->project, $this->user)->willReturn(true);

        $this->system_event_manager->expects(self::once())->method('createEvent');
        $this->history_dao->expects(self::once())
            ->method('groupAddHistory')
            ->with('svn_multi_repository_creation', self::anything(), self::anything());

        $this->dao->method('doesRepositoryAlreadyExist')->willReturn(false);

        $this->repository_creator->create($this->repository, $this->user);
    }

    public function testItThrowsAnExceptionWhenUserIsNotASVNAdministrator(): void
    {
        $this->permissions_manager->method('isAdmin')->with($this->project, $this->user)->willReturn(false);

        $this->system_event_manager->expects(self::never())->method('createEvent');
        $this->expectException(UserIsNotSVNAdministratorException::class);
        $this->history_dao->expects(self::never())->method('groupAddHistory');

        $this->repository_creator->create($this->repository, $this->user);
    }

    public function testItThrowsAnExceptionWhenRepositoryNameIsAlreadyUsed(): void
    {
        $this->permissions_manager->method('isAdmin')->with($this->project, $this->user)->willReturn(true);
        $this->dao->method('doesRepositoryAlreadyExist')->willReturn(true);

        $this->system_event_manager->expects(self::never())->method('createEvent');
        $this->expectException(RepositoryNameIsInvalidException::class);
        $this->history_dao->expects(self::never())->method('groupAddHistory');

        $this->repository_creator->create($this->repository, $this->user);
    }

    public function testItCreatesRepositoryWithCustomSettingsAndImportAllAccessFileHistory(): void
    {
        $this->permissions_manager->method('isAdmin')->with($this->project, $this->user)->willReturn(true);

        $this->system_event_manager->expects(self::once())->method('createEvent');
        $this->history_creator->expects(self::once())->method('useAVersionWithHistoryWithoutUpdateSVNAccessFile');
        $this->history_dao->expects(self::once())->method('groupAddHistory')
            ->with('svn_multi_repository_creation_with_full_settings', self::anything(), self::anything());
        $this->history_creator->expects(self::never())->method('storeInDBWithoutCleaningContent');
        $this->history_creator->expects(self::once())->method('storeInDB');
        $this->dao->method('doesRepositoryAlreadyExist')->willReturn(false);
        $this->hook_config_updator->method('initHookConfiguration');

        $commit_rules        = [
            HookConfig::COMMIT_MESSAGE_CAN_CHANGE => true,
            HookConfig::MANDATORY_REFERENCE       => true,
        ];
        $immutable_tag       = new ImmutableTag($this->repository, [], []);
        $access_file         = "[/]\r\n* = rw \r\n@members = rw\r\n[/tags]\r\n@admins = rw";
        $access_file_history = [new AccessFileHistory($this->repository, 1, 1, $access_file, time())];
        $mail_notifications  = [];
        $settings            = new Settings(
            $commit_rules,
            $immutable_tag,
            $access_file,
            $mail_notifications,
            $access_file_history,
            1,
            false
        );
        $initial_layout      = [];

        $this->repository_creator->createWithSettings(
            $this->repository,
            $this->user,
            $settings,
            $initial_layout,
            false
        );
    }

    public function testItCreatesRepositoryWithCustomSettingsAndImportAllAccessFileHistoryWithoutPurgeThemContent(): void
    {
        $this->permissions_manager->method('isAdmin')->with($this->project, $this->user)->willReturn(true);

        $this->system_event_manager->expects(self::once())->method('createEvent');
        $this->history_creator->expects(self::once())->method('useAVersionWithHistoryWithoutUpdateSVNAccessFile');
        $this->history_dao->expects(self::once())
            ->method('groupAddHistory')
            ->with('svn_multi_repository_creation_with_full_settings', self::anything(), self::anything());
        $this->history_creator->expects(self::once())->method('storeInDBWithoutCleaningContent');
        $this->dao->method('doesRepositoryAlreadyExist')->willReturn(false);
        $this->hook_config_updator->method('initHookConfiguration');

        $commit_rules        = [
            HookConfig::COMMIT_MESSAGE_CAN_CHANGE => true,
            HookConfig::MANDATORY_REFERENCE       => true,
        ];
        $immutable_tag       = new ImmutableTag($this->repository, [], []);
        $access_file         = "[/]\r\n* = rw \r\n@members = rw\r\n[/tags]\r\n@admins = rw";
        $access_file_history = [new AccessFileHistory($this->repository, 1, 1, $access_file, time())];
        $mail_notifications  = [];
        $settings            = new Settings(
            $commit_rules,
            $immutable_tag,
            $access_file,
            $mail_notifications,
            $access_file_history,
            1,
            true
        );
        $initial_layout      = [];

        $this->repository_creator->createWithSettings(
            $this->repository,
            $this->user,
            $settings,
            $initial_layout,
            false
        );
    }

    public function testItCreatesRepositoryWithCustomSettings(): void
    {
        $this->permissions_manager->method('isAdmin')->with($this->project, $this->user)->willReturn(true);

        $this->system_event_manager->expects(self::once())->method('createEvent');
        $this->hook_config_updator->expects(self::once())->method('initHookConfiguration');
        $this->history_creator->expects(self::never())->method('useAVersionWithHistoryWithoutUpdateSVNAccessFile');
        $this->history_dao->expects(self::once())->method('groupAddHistory')->with(
            'svn_multi_repository_creation_with_full_settings',
            self::anything(),
            self::anything(),
        );
        $this->history_creator->expects(self::once())->method('storeInDB');
        $this->dao->method('doesRepositoryAlreadyExist')->willReturn(false);
        $this->hook_config_updator->method('initHookConfiguration');

        $commit_rules       = [
            HookConfig::COMMIT_MESSAGE_CAN_CHANGE => true,
            HookConfig::MANDATORY_REFERENCE       => true,
        ];
        $immutable_tag      = new ImmutableTag($this->repository, [], []);
        $access_file        = "[/]\r\n* = rw \r\n@members = rw\r\n[/tags]\r\n@admins = rw";
        $mail_notifications = [];
        $settings           = new Settings(
            $commit_rules,
            $immutable_tag,
            $access_file,
            $mail_notifications,
            [],
            1,
            false
        );
        $initial_layout     = [];

        $this->repository_creator->createWithSettings($this->repository, $this->user, $settings, $initial_layout, false);
    }

    public function testItCreatesRepositoryWithNoCustomSettings(): void
    {
        $this->permissions_manager->method('isAdmin')->with($this->project, $this->user)->willReturn(true);

        $this->system_event_manager->expects(self::once())->method('createEvent');
        $this->hook_config_updator->expects(self::never())->method('initHookConfiguration');
        $this->history_dao->expects(self::once())
            ->method('groupAddHistory')
            ->with('svn_multi_repository_creation', self::anything(), self::anything());
        $this->dao->method('doesRepositoryAlreadyExist')->willReturn(false);

        $commit_rules       = [];
        $immutable_tag      = new ImmutableTag($this->repository, [], []);
        $access_file        = "";
        $mail_notifications = [];
        $settings           = new Settings(
            $commit_rules,
            $immutable_tag,
            $access_file,
            $mail_notifications,
            [],
            1,
            false
        );
        $initial_layout     = [];

        $this->repository_creator->createWithSettings($this->repository, $this->user, $settings, $initial_layout, false);
    }
}
