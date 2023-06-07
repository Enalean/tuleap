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

namespace Tuleap\SVN\REST\v1;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Tuleap\GlobalSVNPollution;
use Tuleap\SVN\AccessControl\AccessFileHistory;
use Tuleap\SVN\AccessControl\AccessFileHistoryCreator;
use Tuleap\SVN\AccessControl\AccessFileHistoryFactory;
use Tuleap\SVN\Admin\ImmutableTag;
use Tuleap\SVN\Admin\ImmutableTagCreator;
use Tuleap\SVN\Admin\ImmutableTagFactory;
use Tuleap\SVN\Admin\MailNotificationManager;
use Tuleap\SVN\Repository\HookConfig;
use Tuleap\SVN\Repository\HookConfigUpdator;
use Tuleap\SVN\Repository\Repository;
use Tuleap\SVN\Repository\Settings;
use Tuleap\SVNCore\CollectionOfSVNAccessFileFaults;

class RepositoryResourceUpdaterTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;
    use GlobalSVNPollution;

    /**
     * @var MailNotificationManager
     */
    private $mail_notification_manager;
    /**
     * @var Repository
     */
    private $repository;
    /**
     * @var ImmutableTagFactory
     */
    private $immutable_tag_factory;
    /**
     * @var AccessFileHistoryFactory
     */
    private $access_file_factory;
    /**
     * @var AccessFileHistoryCreator
     */
    private $access_file_creator;
    /**
     * @var ImmutableTagCreator
     */
    private $immutable_tag_creator;
    /**
     * @var HookConfigUpdator
     */
    private $hook_config_updater;
    /**
     * @var RepositoryResourceUpdater
     */
    private $updater;

    protected function setUp(): void
    {
        parent::setUp();

        $this->hook_config_updater       = \Mockery::spy(\Tuleap\SVN\Repository\HookConfigUpdator::class);
        $this->immutable_tag_creator     = \Mockery::spy(\Tuleap\SVN\Admin\ImmutableTagCreator::class);
        $this->access_file_creator       = \Mockery::spy(\Tuleap\SVN\AccessControl\AccessFileHistoryCreator::class);
        $this->access_file_factory       = \Mockery::spy(\Tuleap\SVN\AccessControl\AccessFileHistoryFactory::class);
        $this->immutable_tag_factory     = \Mockery::spy(\Tuleap\SVN\Admin\ImmutableTagFactory::class);
        $this->mail_notification_manager = \Mockery::spy(\Tuleap\SVN\Admin\MailNotificationManager::class);
        $notification_updater_checker    = \Mockery::spy(\Tuleap\SVN\REST\v1\NotificationUpdateChecker::class);

        $this->updater = new RepositoryResourceUpdater(
            $this->hook_config_updater,
            $this->immutable_tag_creator,
            $this->access_file_factory,
            $this->access_file_creator,
            $this->immutable_tag_factory,
            $this->mail_notification_manager,
            $notification_updater_checker
        );

        $this->repository = \Mockery::spy(\Tuleap\SVN\Repository\Repository::class);
        $project          = \Mockery::mock(\Project::class);
        $project->shouldReceive('getId')->andReturn(101);

        $this->repository->shouldReceive('getProject')->andReturn($project);
    }

    public function testItUpdatesRepositorySettings(): void
    {
        $commit_rules = [
            HookConfig::MANDATORY_REFERENCE       => false,
            HookConfig::COMMIT_MESSAGE_CAN_CHANGE => false,
        ];

        $immutable_tag = new ImmutableTag(
            $this->repository,
            "/tags",
            "/white"
        );

        $access_file = "[/] * = rw";

        $mail_notifications = [];

        $settings = new Settings($commit_rules, $immutable_tag, $access_file, $mail_notifications, [], 1, false);

        $current_access_file = new AccessFileHistory(
            $this->repository,
            1,
            1,
            "",
            time()
        );

        $this->immutable_tag_factory->shouldReceive('getByRepositoryId')->andReturn(
            \Mockery::spy(\Tuleap\SVN\Admin\ImmutableTag::class)
        );
        $this->access_file_factory->shouldReceive('getCurrentVersion')->withArgs([$this->repository])->andReturn(
            $current_access_file
        );

        $this->hook_config_updater->shouldReceive('updateHookConfig')->once();
        $this->immutable_tag_creator->shouldReceive('save')->once();
        $this->access_file_creator->shouldReceive('create')->once()->andReturn(new CollectionOfSVNAccessFileFaults());

        $this->updater->update($this->repository, $settings);
    }

    public function testItUpdatesAccessFileIfNewContentHasANewLineCharacterAtTheEnd(): void
    {
        $commit_rules = [
            HookConfig::MANDATORY_REFERENCE       => false,
            HookConfig::COMMIT_MESSAGE_CAN_CHANGE => false,
        ];

        $immutable_tag = new ImmutableTag(
            $this->repository,
            "/tags",
            "/white"
        );

        $access_file = "[/] * = rw\n";

        $mail_notifications = [];

        $settings = new Settings($commit_rules, $immutable_tag, $access_file, $mail_notifications, [], 1, false);

        $current_access_file = new AccessFileHistory(
            $this->repository,
            1,
            1,
            "[/] * = rw",
            time()
        );

        $this->immutable_tag_factory->shouldReceive('getByRepositoryId')->andReturn(
            \Mockery::spy(\Tuleap\SVN\Admin\ImmutableTag::class)
        );
        $this->access_file_factory->shouldReceive('getCurrentVersion')->withArgs([$this->repository])->andReturn(
            $current_access_file
        );

        $this->hook_config_updater->shouldReceive('updateHookConfig')->once();
        $this->immutable_tag_creator->shouldReceive('save')->once();
        $this->access_file_creator->shouldReceive('create')->once()->andReturn(new CollectionOfSVNAccessFileFaults());

        $this->updater->update($this->repository, $settings);
    }

    public function testItDoesNotUpdateAccessFileIfContentIsSameAsTheVersionUsed(): void
    {
        $commit_rules = [
            HookConfig::MANDATORY_REFERENCE       => false,
            HookConfig::COMMIT_MESSAGE_CAN_CHANGE => false,
        ];

        $immutable_tag = new ImmutableTag(
            $this->repository,
            "/tags",
            "/white"
        );

        $access_file = "[/] * = rw";

        $mail_notifications = [];

        $settings = new Settings($commit_rules, $immutable_tag, $access_file, $mail_notifications, [], 1, false);

        $current_access_file = new AccessFileHistory(
            $this->repository,
            1,
            1,
            "[/] * = rw",
            time()
        );

        $this->immutable_tag_factory->shouldReceive('getByRepositoryId')->andReturn(
            \Mockery::spy(\Tuleap\SVN\Admin\ImmutableTag::class)
        );
        $this->access_file_factory->shouldReceive('getCurrentVersion')->withArgs([$this->repository])->andReturn(
            $current_access_file
        );

        $this->hook_config_updater->shouldReceive('updateHookConfig')->once();
        $this->immutable_tag_creator->shouldReceive('save')->once();
        $this->access_file_creator->shouldReceive('create')->never();

        $this->updater->update($this->repository, $settings);
    }

    public function testItDoesNotUpdateImmutableTagsWhenTagsAreIdentical(): void
    {
        $commit_rules = [
            HookConfig::MANDATORY_REFERENCE       => false,
            HookConfig::COMMIT_MESSAGE_CAN_CHANGE => false,
        ];

        $immutable_tag = new ImmutableTag(
            $this->repository,
            "/tags",
            "/white"
        );

        $access_file = "[/] * = rw";

        $mail_notifications = [];

        $settings = new Settings($commit_rules, $immutable_tag, $access_file, $mail_notifications, [], 1, false);

        $current_access_file = new AccessFileHistory(
            $this->repository,
            1,
            1,
            "",
            time()
        );

        $existing_tags = new ImmutableTag($this->repository, "/tags", "/whitelist");
        $this->immutable_tag_factory->shouldReceive('getByRepositoryId')->andReturn($existing_tags);
        $this->access_file_factory->shouldReceive('getCurrentVersion')->withArgs([$this->repository])->andReturn(
            $current_access_file
        );

        $this->hook_config_updater->shouldReceive('updateHookConfig')->once();
        $this->immutable_tag_creator->shouldReceive('save')->once();
        $this->access_file_creator->shouldReceive('create')->once()->andReturn(new CollectionOfSVNAccessFileFaults());

        $this->updater->update($this->repository, $settings);
    }
}
