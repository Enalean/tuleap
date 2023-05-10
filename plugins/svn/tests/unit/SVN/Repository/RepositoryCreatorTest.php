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

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Tuleap\GlobalSVNPollution;
use Tuleap\SVN\AccessControl\AccessFileHistory;
use Tuleap\SVN\AccessControl\AccessFileHistoryCreator;
use Tuleap\SVN\Admin\ImmutableTag;
use Tuleap\SVN\Dao;
use Tuleap\SVN\Repository\Exception\RepositoryNameIsInvalidException;
use Tuleap\SVN\Repository\Exception\UserIsNotSVNAdministratorException;
use Tuleap\SVN\SvnPermissionManager;

class RepositoryCreatorTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;
    use GlobalSVNPollution;

    /**
     * @var AccessFileHistoryCreator
     */
    private $history_creator;
    /**
     * @var \ProjectHistoryDao
     */
    private $history_dao;
    /**
     * @var HookConfigUpdator
     */
    private $hook_config_updator;
    /**
     * @var Dao
     */
    private $dao;
    /**
     * @var \Project
     */
    private $project;
    /**
     * @var SvnPermissionManager
     */
    private $permissions_manager;
    /**
     * @var \PFUser
     */
    private $user;

    /**
     * @var \SystemEventManager
     */
    private $system_event_manager;

    /**
     * @var Repository
     */
    private $repository;

    /**
     * @var RepositoryCreator
     */
    private $repository_creator;

    protected function setUp(): void
    {
        $this->system_event_manager = \Mockery::spy(\SystemEventManager::class);
        $this->history_dao          = \Mockery::spy(\ProjectHistoryDao::class);
        $this->dao                  = \Mockery::spy(\Tuleap\SVN\Dao::class);
        $this->permissions_manager  = \Mockery::spy(\Tuleap\SVN\SvnPermissionManager::class);
        $this->hook_config_updator  = \Mockery::spy(\Tuleap\SVN\Repository\HookConfigUpdator::class);
        $this->history_creator      = \Mockery::spy(\Tuleap\SVN\AccessControl\AccessFileHistoryCreator::class);
        $this->repository_creator   = new RepositoryCreator(
            $this->dao,
            $this->system_event_manager,
            $this->history_dao,
            $this->permissions_manager,
            $this->hook_config_updator,
            new ProjectHistoryFormatter(),
            \Mockery::spy(\Tuleap\SVN\Admin\ImmutableTagCreator::class),
            $this->history_creator,
            \Mockery::spy(\Tuleap\SVN\Admin\MailNotificationManager::class)
        );

        $this->project = \Mockery::mock(\Project::class);
        $this->project->shouldReceive('getId')->andReturn(101);
        $this->project->shouldReceive('getUnixNameMixedCase')->andReturn('project-unix-name');
        $this->user = \Mockery::mock(\PFUser::class);
        $this->user->shouldReceive('getId')->andReturn(110);
        $this->repository = SvnRepository::buildActiveRepository(1, 'repo01', $this->project);

        $this->dao->shouldReceive('create')->andReturn(1);
    }

    public function testItCreatesTheRepository(): void
    {
        $this->permissions_manager->shouldReceive('isAdmin')->withArgs([$this->project, $this->user])->andReturn(true);

        $this->system_event_manager->shouldReceive('createEvent')->once();
        $this->history_dao->shouldReceive('groupAddHistory')
            ->withArgs(['svn_multi_repository_creation', \Mockery::any(), \Mockery::any()])
            ->once();

        $this->repository_creator->create($this->repository, $this->user);
    }

    public function testItThrowsAnExceptionWhenUserIsNotASVNAdministrator(): void
    {
        $this->permissions_manager->shouldReceive('isAdmin')->withArgs([$this->project, $this->user])->andReturn(false);

        $this->system_event_manager->shouldReceive('createEvent')->never();
        $this->expectException(UserIsNotSVNAdministratorException::class);
        $this->history_dao->shouldReceive('groupAddHistory')->never();

        $this->repository_creator->create($this->repository, $this->user);
    }

    public function testItThrowsAnExceptionWhenRepositoryNameIsAlreadyUsed(): void
    {
        $this->permissions_manager->shouldReceive('isAdmin')->withArgs([$this->project, $this->user])->andReturn(true);
        $this->dao->shouldReceive('doesRepositoryAlreadyExist')->andReturn(true);

        $this->system_event_manager->shouldReceive('createEvent')->never();
        $this->expectException(RepositoryNameIsInvalidException::class);
        $this->history_dao->shouldReceive('groupAddHistory')->never();

        $this->repository_creator->create($this->repository, $this->user);
    }

    public function testItCreatesRepositoryWithCustomSettingsAndImportAllAccessFileHistory(): void
    {
        $this->permissions_manager->shouldReceive('isAdmin')->withArgs([$this->project, $this->user])->andReturn(true);

        $this->system_event_manager->shouldReceive('createEvent')->once();
        $this->history_creator->shouldReceive('useAVersionWithHistoryWithoutUpdateSVNAccessFile')->once();
        $this->history_dao->shouldReceive('groupAddHistory')
            ->withArgs(['svn_multi_repository_creation_with_full_settings', \Mockery::any(), \Mockery::any()])
            ->once();
        $this->history_creator->shouldReceive('storeInDBWithoutCleaningContent')->never();
        $this->history_creator->shouldReceive('storeInDB')->once();

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
        $this->permissions_manager->shouldReceive('isAdmin')->withArgs([$this->project, $this->user])->andReturn(true);

        $this->system_event_manager->shouldReceive('createEvent')->once();
        $this->history_creator->shouldReceive('useAVersionWithHistoryWithoutUpdateSVNAccessFile')->once();
        $this->history_dao->shouldReceive('groupAddHistory')
            ->withArgs(['svn_multi_repository_creation_with_full_settings', \Mockery::any(), \Mockery::any()])
            ->once();
        $this->history_creator->shouldReceive('storeInDBWithoutCleaningContent')->once();

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
        $this->permissions_manager->shouldReceive('isAdmin')->withArgs([$this->project, $this->user])->andReturn(true);

        $this->system_event_manager->shouldReceive('createEvent')->once();
        $this->hook_config_updator->shouldReceive('initHookConfiguration')->once();
        $this->history_creator->shouldReceive('useAVersionWithHistoryWithoutUpdateSVNAccessFile')->never();
        $this->history_dao->shouldReceive('groupAddHistory')->withArgs(
            ['svn_multi_repository_creation_with_full_settings', \Mockery::any(), \Mockery::any()]
        )->once();
        $this->history_creator->shouldReceive('storeInDB')->once();

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
        $this->permissions_manager->shouldReceive('isAdmin')->withArgs([$this->project, $this->user])->andReturn(true);

        $this->system_event_manager->shouldReceive('createEvent')->once();
        $this->hook_config_updator->shouldReceive('initHookConfiguration')->never();
        $this->history_dao->shouldReceive('groupAddHistory')
            ->withArgs(['svn_multi_repository_creation', \Mockery::any(), \Mockery::any()])
            ->once();

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
