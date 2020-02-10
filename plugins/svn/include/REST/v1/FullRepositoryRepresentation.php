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

use Tuleap\SVN\AccessControl\AccessFileHistory;
use Tuleap\SVN\Admin\ImmutableTag;
use Tuleap\SVN\Admin\MailNotification;
use Tuleap\SVN\Repository\HookConfig;
use Tuleap\SVN\Repository\Repository;

class FullRepositoryRepresentation extends RepositoryRepresentation
{
    /**
     * @var SettingsRepresentation {@type Tuleap\SVN\REST\v1\SettingsRepresentation}
     */
    public $settings;

    /**
     * @param MailNotification[] $notifications
     */
    public function fullBuild(
        Repository $repository,
        HookConfig $hook_config,
        ImmutableTag $immutable_tag,
        AccessFileHistory $access_file_history,
        array $notifications
    ) {
        parent::build($repository);

        $immutable_tag_representation = new ImmutableTagRepresentation();
        $immutable_tag_representation->build($immutable_tag);

        $commit_rules_representation = new CommitRulesRepresentation();
        $commit_rules_representation->build($hook_config);

        $settings_representation = new SettingsRepresentation();
        $settings_representation->build(
            $commit_rules_representation,
            $immutable_tag_representation,
            $access_file_history,
            $notifications
        );

        $this->settings = $settings_representation;
    }
}
