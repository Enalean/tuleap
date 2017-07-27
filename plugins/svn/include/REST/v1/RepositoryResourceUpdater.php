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

namespace Tuleap\REST\v1;

use Tuleap\Svn\Admin\ImmutableTagCreator;
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

    public function __construct(HookConfigUpdator $hook_config_updator, ImmutableTagCreator $immutable_tag_creator)
    {
        $this->hook_config_updator   = $hook_config_updator;
        $this->immutable_tag_creator = $immutable_tag_creator;
    }

    public function update(Repository $repository, Settings $settings)
    {
        if ($settings->getCommitRules()) {
            $this->hook_config_updator->updateHookConfig($repository, $settings->getCommitRules());
        }

        if ($settings->getImmutableTag()) {
            $this->immutable_tag_creator->save(
                $repository,
                $settings->getImmutableTag()->getPathsAsString(),
                $settings->getImmutableTag()->getWhitelistAsString()
            );
        }
    }
}
