<?php
/**
 * Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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

namespace Tuleap\Git\RepositoryList;

use Tuleap\Project\Icons\EmojiCodepointConverter;
use Tuleap\Project\ProjectPrivacyPresenter;

class GitRepositoryListPresenter
{
    public $repositories_administration_url;
    public $repositories_fork_url;
    public $repositories_list_url;
    public $project_id;
    /** @var bool */
    public $is_admin;
    /** @var string */
    public $json_encoded_repositories_owners;
    /** @var string */
    public $display_mode;
    /** @var string */
    public $external_plugins;
    /**
     * @var string
     * @psalm-readonly
     */
    public $project_url;
    /**
     * @var mixed
     * @psalm-readonly
     */
    public $project_public_name;
    /**
     * @var false|string
     * @psalm-readonly
     */
    public $privacy;
    /**
     * @var false|string
     * @psalm-readonly
     */
    public $project_flags;
    /**
     * @var string
     */
    public $external_services_name_used;
    /**
     * @psalm-readonly
     */
    public string $project_icon;

    public function __construct(
        \PFUser $current_user,
        \Project $project,
        $is_git_administrator,
        array $repositories_owners,
        array $external_plugins,
        array $project_flags,
        array $external_services_name_used,
        public readonly bool $is_old_pull_request_dashboard_view_enabled,
    ) {
        $this->repositories_administration_url = GIT_BASE_URL . "/?" . http_build_query(
            [
                "group_id" => $project->getID(),
                "action"   => "admin",
            ]
        );

        $this->repositories_fork_url = GIT_BASE_URL . "/?" . http_build_query(
            [
                "group_id" => $project->getID(),
                "action"   => "fork_repositories",
            ]
        );

        $this->repositories_list_url = GIT_BASE_URL . "/" . urlencode($project->getUnixNameLowerCase()) . "/";
        $this->project_id            = $project->getID();
        $this->is_admin              = $is_git_administrator;

        $this->json_encoded_repositories_owners = json_encode($repositories_owners);

        $this->display_mode                = (string) $current_user->getPreference("are_git_repositories_sorted_by_path");
        $this->external_plugins            = json_encode($external_plugins);
        $this->external_services_name_used = json_encode($external_services_name_used);

        $this->project_url         = $project->getUrl();
        $this->project_public_name = $project->getPublicName();

        $this->privacy       = json_encode(ProjectPrivacyPresenter::fromProject($project), JSON_THROW_ON_ERROR);
        $this->project_flags = json_encode($project_flags, JSON_THROW_ON_ERROR);
        $this->project_icon  = EmojiCodepointConverter::convertStoredEmojiFormatToEmojiFormat($project->getIconUnicodeCodepoint());
    }
}
