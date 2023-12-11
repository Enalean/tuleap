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

use PHPUnit\Framework\MockObject\MockObject;
use Tuleap\SVN\AccessControl\AccessFileHistory;
use Tuleap\SVN\AccessControl\AccessFileHistoryCreator;
use Tuleap\SVN\AccessControl\AccessFileHistoryFactory;
use Tuleap\SVN\Admin\ImmutableTag;
use Tuleap\SVN\Admin\ImmutableTagCreator;
use Tuleap\SVN\Admin\ImmutableTagFactory;
use Tuleap\SVN\Admin\MailNotificationManager;
use Tuleap\SVN\Repository\HookConfig;
use Tuleap\SVN\Repository\HookConfigUpdator;
use Tuleap\SVNCore\Repository;
use Tuleap\SVN\Repository\Settings;
use Tuleap\SVNCore\CollectionOfSVNAccessFileFaults;
use Tuleap\Test\Builders\ProjectTestBuilder;

final class RepositoryResourceUpdaterTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private MailNotificationManager&MockObject $mail_notification_manager;
    private Repository&MockObject $repository;
    private ImmutableTagFactory&MockObject $immutable_tag_factory;
    private AccessFileHistoryFactory&MockObject $access_file_factory;
    private AccessFileHistoryCreator&MockObject $access_file_creator;
    private ImmutableTagCreator&MockObject $immutable_tag_creator;
    private HookConfigUpdator&MockObject $hook_config_updater;
    private RepositoryResourceUpdater $updater;
    private NotificationUpdateChecker&MockObject $notification_updater_checker;

    protected function setUp(): void
    {
        parent::setUp();

        $this->hook_config_updater          = $this->createMock(\Tuleap\SVN\Repository\HookConfigUpdator::class);
        $this->immutable_tag_creator        = $this->createMock(\Tuleap\SVN\Admin\ImmutableTagCreator::class);
        $this->access_file_creator          = $this->createMock(\Tuleap\SVN\AccessControl\AccessFileHistoryCreator::class);
        $this->access_file_factory          = $this->createMock(\Tuleap\SVN\AccessControl\AccessFileHistoryFactory::class);
        $this->immutable_tag_factory        = $this->createMock(\Tuleap\SVN\Admin\ImmutableTagFactory::class);
        $this->mail_notification_manager    = $this->createMock(\Tuleap\SVN\Admin\MailNotificationManager::class);
        $this->notification_updater_checker = $this->createMock(\Tuleap\SVN\REST\v1\NotificationUpdateChecker::class);

        $this->updater = new RepositoryResourceUpdater(
            $this->hook_config_updater,
            $this->immutable_tag_creator,
            $this->access_file_factory,
            $this->access_file_creator,
            $this->immutable_tag_factory,
            $this->mail_notification_manager,
            $this->notification_updater_checker,
        );

        $this->repository = $this->createMock(\Tuleap\SVNCore\Repository::class);
        $project          = ProjectTestBuilder::aProject()->withId(101)->build();

        $this->repository->method('getProject')->willReturn($project);
        $this->repository->method('getSystemPath')->willReturn('');
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

        $this->immutable_tag_factory->method('getByRepositoryId')->willReturn(
            new ImmutableTag(
                $this->repository,
                "/dev",
                ""
            ),
        );
        $this->access_file_factory->method('getCurrentVersion')->with($this->repository)->willReturn(
            $current_access_file
        );

        $this->notification_updater_checker->method('hasNotificationChanged')->willReturn(false);

        $this->hook_config_updater->expects(self::once())->method('updateHookConfig');
        $this->immutable_tag_creator->expects(self::once())->method('save');
        $this->access_file_creator->expects(self::once())->method('create')->willReturn(new CollectionOfSVNAccessFileFaults());

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

        $this->immutable_tag_factory->method('getByRepositoryId')->willReturn(
            new ImmutableTag(
                $this->repository,
                "/dev",
                ""
            ),
        );
        $this->access_file_factory->method('getCurrentVersion')->with($this->repository)->willReturn(
            $current_access_file
        );

        $this->notification_updater_checker->method('hasNotificationChanged')->willReturn(false);

        $this->hook_config_updater->expects(self::once())->method('updateHookConfig');
        $this->immutable_tag_creator->expects(self::once())->method('save');
        $this->access_file_creator->expects(self::once())->method('create')->willReturn(new CollectionOfSVNAccessFileFaults());

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

        $this->immutable_tag_factory->method('getByRepositoryId')->willReturn(
            new ImmutableTag(
                $this->repository,
                "/dev",
                ""
            ),
        );
        $this->access_file_factory->method('getCurrentVersion')->with($this->repository)->willReturn(
            $current_access_file
        );

        $this->notification_updater_checker->method('hasNotificationChanged')->willReturn(false);

        $this->hook_config_updater->expects(self::once())->method('updateHookConfig');
        $this->immutable_tag_creator->expects(self::once())->method('save');
        $this->access_file_creator->expects(self::never())->method('create');

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
        $this->immutable_tag_factory->method('getByRepositoryId')->willReturn($existing_tags);
        $this->access_file_factory->method('getCurrentVersion')->with($this->repository)->willReturn(
            $current_access_file
        );

        $this->notification_updater_checker->method('hasNotificationChanged')->willReturn(false);

        $this->hook_config_updater->expects(self::once())->method('updateHookConfig');
        $this->immutable_tag_creator->expects(self::once())->method('save');
        $this->access_file_creator->expects(self::once())->method('create')->willReturn(new CollectionOfSVNAccessFileFaults());

        $this->updater->update($this->repository, $settings);
    }
}
