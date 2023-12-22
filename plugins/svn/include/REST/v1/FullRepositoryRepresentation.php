<?php
/**
 * Copyright (c) Enalean, 2017-Present. All Rights Reserved.
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

use Project;
use Tuleap\REST\JsonCast;
use Tuleap\SVN\AccessControl\AccessFileHistory;
use Tuleap\SVN\Admin\ImmutableTag;
use Tuleap\SVN\Admin\MailNotification;
use Tuleap\SVN\Repository\HookConfig;
use Tuleap\SVNCore\Repository;

/**
 * @psalm-immutable
 */
class FullRepositoryRepresentation extends RepositoryRepresentation
{
    /**
     * @var SettingsGETRepresentation {@type \Tuleap\SVN\REST\v1\SettingsGETRepresentation}
     */
    public $settings;

    protected function __construct(Project $project, int $id, string $name, string $svn_url, SettingsGETRepresentation $settings)
    {
        parent::__construct($project, $id, $name, $svn_url);
        $this->settings = $settings;
    }

    /**
     * @param MailNotification[] $notifications
     */
    public static function fullBuild(
        Repository $repository,
        HookConfig $hook_config,
        ImmutableTag $immutable_tag,
        AccessFileHistory $access_file_history,
        array $notifications,
    ): self {
        $immutable_tag_representation = ImmutableTagRepresentation::build($immutable_tag);

        $commit_rules_representation = CommitRulesRepresentation::build($hook_config);

        $settings_representation = SettingsGETRepresentation::build(
            $commit_rules_representation,
            $immutable_tag_representation,
            $access_file_history,
            $notifications
        );

        return new self(
            $repository->getProject(),
            JsonCast::toInt($repository->getId()),
            $repository->getName(),
            $repository->getSvnUrl(),
            $settings_representation
        );
    }
}
