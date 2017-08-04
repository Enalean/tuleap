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

use Tuleap\Svn\AccessControl\AccessFileHistoryCreator;
use Tuleap\Svn\AccessControl\AccessFileHistoryFactory;
use Tuleap\Svn\Admin\ImmutableTag;
use Tuleap\Svn\Admin\ImmutableTagCreator;
use Tuleap\Svn\Admin\ImmutableTagFactory;
use Tuleap\Svn\Repository\HookConfigUpdator;
use Tuleap\Svn\Repository\Repository;
use Tuleap\Svn\Repository\Settings;

class RepositoryResourceUpdater
{
    /**
     * @var HookConfigUpdator
     */
    private $hook_config_updator;
    /**
     * @var ImmutableTagCreator
     */
    private $immutable_tag_creator;
    /**
     * @var AccessFileHistoryCreator
     */
    private $access_file_history_creator;
    /**
     * @var AccessFileHistoryFactory
     */
    private $access_file_history_factory;
    /**
     * @var ImmutableTagFactory
     */
    private $immutable_tag_factory;

    public function __construct(
        HookConfigUpdator $hook_config_updator,
        ImmutableTagCreator $immutable_tag_creator,
        AccessFileHistoryFactory $access_file_history_factory,
        AccessFileHistoryCreator $access_file_history_creator,
        ImmutableTagFactory $immutable_tag_factory
    ) {
        $this->hook_config_updator         = $hook_config_updator;
        $this->immutable_tag_creator       = $immutable_tag_creator;
        $this->access_file_history_creator = $access_file_history_creator;
        $this->access_file_history_factory = $access_file_history_factory;
        $this->immutable_tag_factory       = $immutable_tag_factory;
    }

    public function update(Repository $repository, Settings $settings)
    {
        $this->hook_config_updator->updateHookConfig($repository, $settings->getCommitRules());

        if ($this->hasImmutableTagChanged($settings->getImmutableTag(), $repository)) {
            $this->immutable_tag_creator->save(
                $repository,
                $settings->getImmutableTag()->getPathsAsString(),
                $settings->getImmutableTag()->getWhitelistAsString()
            );
        }

        $current_version = $this->access_file_history_factory->getCurrentVersion($repository);
        if ($current_version->getContent() !== $settings->getAccessFileContent()) {
            $this->access_file_history_creator->create($repository, $settings->getAccessFileContent(), time());
        }
    }

    private function hasImmutableTagChanged(ImmutableTag $new_immutable_tag, Repository $repository)
    {
        $old_immutable_tag = $this->immutable_tag_factory->getByRepositoryId($repository);

        return $old_immutable_tag->getPathsAsString() != $new_immutable_tag->getPathsAsString()
            || $old_immutable_tag->getWhitelistAsString() != $new_immutable_tag->getWhitelistAsString();
    }
}
