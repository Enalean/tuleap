<?php
/**
 * Copyright (c) Enalean, 2017. All Rights Reserved.
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

use Tuleap\Svn\AccessControl\AccessFileHistory;
use Tuleap\Svn\Admin\ImmutableTag;
use Tuleap\Svn\Repository\HookConfig;
use Tuleap\Svn\Repository\Settings;
use TuleapTestCase;

require_once __DIR__ . '/../bootstrap.php';

class RepositoryResourceUpdaterTest extends TuleapTestCase
{
    /**
     * @var RepositoryResourceUpdater
     */
    private $updater;

    public function setUp()
    {
        parent::setUp();

        $this->hook_config_updater   = mock('Tuleap\Svn\Repository\HookConfigUpdator');
        $this->immutable_tag_creator = mock('Tuleap\Svn\Admin\ImmutableTagCreator');
        $this->access_file_creator   = mock('Tuleap\Svn\AccessControl\AccessFileHistoryCreator');
        $this->access_file_factory   = mock('Tuleap\Svn\AccessControl\AccessFileHistoryFactory');

        $this->updater = new RepositoryResourceUpdater(
            $this->hook_config_updater,
            $this->immutable_tag_creator,
            $this->access_file_creator,
            $this->access_file_factory
        );

        $this->repository = mock('Tuleap\Svn\Repository\Repository');
    }

    public function itUpdatesRespositorySettings()
    {
        $commit_rules = array(
            HookConfig::MANDATORY_REFERENCE       => false,
            HookConfig::COMMIT_MESSAGE_CAN_CHANGE => false,
        );

        $immutable_tag = new ImmutableTag(
            $this->repository,
            "",
            ""
        );

        $access_file = "[/] * = rw";

        $settings = new Settings(
            $commit_rules,
            $immutable_tag,
            $access_file
        );

        $current_access_file = new AccessFileHistory(
            $this->repository,
            1,
            1,
            "",
            time()
        );

        stub($this->access_file_factory)->getCurrentVersion($this->repository)->returns($current_access_file);

        expect($this->hook_config_updater)->updateHookConfig()->once();
        expect($this->immutable_tag_creator)->save()->once();
        expect($this->access_file_creator)->create()->once();

        $this->updater->update($this->repository, $settings);
    }



    public function itUpdatesAccessFileIfNewContentHasANewLineCaracterAtTheEnd()
    {
        $commit_rules = array(
            HookConfig::MANDATORY_REFERENCE       => false,
            HookConfig::COMMIT_MESSAGE_CAN_CHANGE => false,
        );

        $immutable_tag = new ImmutableTag(
            $this->repository,
            "",
            ""
        );

        $access_file = "[/] * = rw\n";

        $settings = new Settings(
            $commit_rules,
            $immutable_tag,
            $access_file
        );

        $current_access_file = new AccessFileHistory(
            $this->repository,
            1,
            1,
            "[/] * = rw",
            time()
        );

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
            "",
            ""
        );

        $access_file = "[/] * = rw";

        $settings = new Settings(
            $commit_rules,
            $immutable_tag,
            $access_file
        );

        $current_access_file = new AccessFileHistory(
            $this->repository,
            1,
            1,
            "[/] * = rw",
            time()
        );

        stub($this->access_file_factory)->getCurrentVersion($this->repository)->returns($current_access_file);

        expect($this->hook_config_updater)->updateHookConfig()->once();
        expect($this->immutable_tag_creator)->save()->once();
        expect($this->access_file_creator)->create()->never();

        $this->updater->update($this->repository, $settings);
    }
}
