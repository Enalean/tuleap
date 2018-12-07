<?php
/**
 * Copyright (c) Enalean, 2017 - 2018. All Rights Reserved.
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
use TuleapTestCase;

require_once __DIR__ . '/../bootstrap.php';

class RepositoryResourceUpdaterTest extends TuleapTestCase
{
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

    public function setUp()
    {
        parent::setUp();

        $this->hook_config_updater       = mock('Tuleap\SVN\Repository\HookConfigUpdator');
        $this->immutable_tag_creator     = mock('Tuleap\SVN\Admin\ImmutableTagCreator');
        $this->access_file_creator       = mock('Tuleap\SVN\AccessControl\AccessFileHistoryCreator');
        $this->access_file_factory       = mock('Tuleap\SVN\AccessControl\AccessFileHistoryFactory');
        $this->immutable_tag_factory     = mock('Tuleap\SVN\Admin\ImmutableTagFactory');
        $this->mail_notification_manager = mock('Tuleap\SVN\Admin\MailNotificationManager');
        $notification_updater_checker    = mock('Tuleap\SVN\REST\v1\NotificationUpdateChecker');

        $this->updater = new RepositoryResourceUpdater(
            $this->hook_config_updater,
            $this->immutable_tag_creator,
            $this->access_file_factory,
            $this->access_file_creator,
            $this->immutable_tag_factory,
            $this->mail_notification_manager,
            $notification_updater_checker
        );

        $this->repository = mock('Tuleap\SVN\Repository\Repository');
        stub($this->repository)->getProject()->returns(aMockProject()->withId(101)->build());
    }

    public function itUpdatesRepositorySettings()
    {
        $commit_rules = array(
            HookConfig::MANDATORY_REFERENCE       => false,
            HookConfig::COMMIT_MESSAGE_CAN_CHANGE => false,
        );

        $immutable_tag = new ImmutableTag(
            $this->repository,
            "/tags",
            "/white"
        );

        $access_file = "[/] * = rw";

        $mail_notifications = array();

        $settings = new Settings($commit_rules, $immutable_tag, $access_file, $mail_notifications, array(), 1, false);

        $current_access_file = new AccessFileHistory(
            $this->repository,
            1,
            1,
            "",
            time()
        );

        stub($this->immutable_tag_factory)->getByRepositoryId()->returns(mock('Tuleap\SVN\Admin\ImmutableTag'));
        stub($this->access_file_factory)->getCurrentVersion($this->repository)->returns($current_access_file);

        expect($this->hook_config_updater)->updateHookConfig()->once();
        expect($this->immutable_tag_creator)->save()->once();
        expect($this->access_file_creator)->create()->once();

        $this->updater->update($this->repository, $settings);
    }

    public function itUpdatesAccessFileIfNewContentHasANewLineCharacterAtTheEnd()
    {
        $commit_rules = array(
            HookConfig::MANDATORY_REFERENCE       => false,
            HookConfig::COMMIT_MESSAGE_CAN_CHANGE => false,
        );

        $immutable_tag = new ImmutableTag(
            $this->repository,
            "/tags",
            "/white"
        );

        $access_file = "[/] * = rw\n";

        $mail_notifications = array();

        $settings = new Settings($commit_rules, $immutable_tag, $access_file, $mail_notifications, array(), 1, false);

        $current_access_file = new AccessFileHistory(
            $this->repository,
            1,
            1,
            "[/] * = rw",
            time()
        );

        stub($this->immutable_tag_factory)->getByRepositoryId()->returns(mock('Tuleap\SVN\Admin\ImmutableTag'));
        stub($this->access_file_factory)->getCurrentVersion($this->repository)->returns($current_access_file);

        expect($this->hook_config_updater)->updateHookConfig()->once();
        expect($this->immutable_tag_creator)->save()->once();
        expect($this->access_file_creator)->create()->once();

        $this->updater->update($this->repository, $settings);
    }

    public function itDoesNotUpdateAccessFileIfContentIsSameAsTheVersionUsed()
    {
        $commit_rules = array(
            HookConfig::MANDATORY_REFERENCE       => false,
            HookConfig::COMMIT_MESSAGE_CAN_CHANGE => false,
        );

        $immutable_tag = new ImmutableTag(
            $this->repository,
            "/tags",
            "/white"
        );

        $access_file = "[/] * = rw";

        $mail_notifications = array();

        $settings = new Settings($commit_rules, $immutable_tag, $access_file, $mail_notifications, array(), 1, false);

        $current_access_file = new AccessFileHistory(
            $this->repository,
            1,
            1,
            "[/] * = rw",
            time()
        );

        stub($this->immutable_tag_factory)->getByRepositoryId()->returns(mock('Tuleap\SVN\Admin\ImmutableTag'));
        stub($this->access_file_factory)->getCurrentVersion($this->repository)->returns($current_access_file);

        expect($this->hook_config_updater)->updateHookConfig()->once();
        expect($this->immutable_tag_creator)->save()->once();
        expect($this->access_file_creator)->create()->never();

        $this->updater->update($this->repository, $settings);
    }

    public function itDoesNotUpdateImmutableTagsWhenTagsAreIdentical()
    {
        $commit_rules = array(
            HookConfig::MANDATORY_REFERENCE       => false,
            HookConfig::COMMIT_MESSAGE_CAN_CHANGE => false,
        );

        $immutable_tag = new ImmutableTag(
            $this->repository,
            "/tags",
            "/white"
        );

        $access_file = "[/] * = rw";

        $mail_notifications = array();

        $settings = new Settings($commit_rules, $immutable_tag, $access_file, $mail_notifications, array(), 1, false);

        $current_access_file = new AccessFileHistory(
            $this->repository,
            1,
            1,
            "",
            time()
        );

        $existing_tags = new ImmutableTag($this->repository, "/tags", "/whitelist");
        stub($this->immutable_tag_factory)->getByRepositoryId()->returns($existing_tags);
        stub($this->access_file_factory)->getCurrentVersion($this->repository)->returns($current_access_file);

        expect($this->hook_config_updater)->updateHookConfig()->once();
        expect($this->immutable_tag_creator)->save()->once();
        expect($this->access_file_creator)->create()->once();

        $this->updater->update($this->repository, $settings);
    }
}
