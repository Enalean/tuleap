<?php
/**
 * Copyright (c) Enalean, 2018-Present. All Rights Reserved.
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

namespace Tuleap\Git\Repository\View;

use Git_Driver_Gerrit_GerritDriverFactory;
use Git_Driver_Gerrit_ProjectCreatorStatus;
use GitRepository;
use Tuleap\Date\DateHelper;
use Tuleap\Date\RelativeDatesAssetsRetriever;
use Tuleap\Git\Driver\Gerrit\GerritUnsupportedVersionDriver;

class GerritStatusPresenter
{
    /** @var bool */
    public $is_migration_queued;
    /** @var bool */
    public $is_migrated_to_gerrit;
    /** @var bool */
    public $has_migration_error;
    /** @var bool */
    public $is_migrated_to_unsupported_gerrit_version = false;
    /** @var string */
    public $gerrit_url;
    /** @var string */
    public $gerrit_project;
    /** @var string */
    public $purified_migration_time_ago;
    /** @var string */
    public $settings_gerrit_url;

    public function __construct(
        GitRepository $repository,
        Git_Driver_Gerrit_ProjectCreatorStatus $project_creator_status,
        Git_Driver_Gerrit_GerritDriverFactory $driver_factory,
        array $gerrit_servers,
        \PFUser $user,
    ) {
        $status                      = $project_creator_status->getStatus($repository);
        $this->is_migration_queued   = ($status === Git_Driver_Gerrit_ProjectCreatorStatus::QUEUE);
        $this->is_migrated_to_gerrit = ($status === Git_Driver_Gerrit_ProjectCreatorStatus::DONE);
        $this->has_migration_error   = ($status === Git_Driver_Gerrit_ProjectCreatorStatus::ERROR);

        if ($this->is_migrated_to_gerrit === true) {
            $gerrit_server                                   = $gerrit_servers[$repository->getRemoteServerId()];
            $driver                                          = $driver_factory->getDriver($gerrit_server);
            $this->gerrit_project                            = $driver->getGerritProjectName($repository);
            $this->gerrit_url                                = $gerrit_server->getProjectUrl($this->gerrit_project);
            $this->is_migrated_to_unsupported_gerrit_version = $driver instanceof GerritUnsupportedVersionDriver;
        }

        if ($this->has_migration_error === true) {
            $GLOBALS['Response']->addJavascriptAsset(RelativeDatesAssetsRetriever::getAsJavascriptAssets());
            $this->purified_migration_time_ago = DateHelper::relativeDateInlineContext((int) $project_creator_status->getEventDate($repository), $user);
            $this->settings_gerrit_url         = $url = GIT_BASE_URL . '/?'
                . http_build_query(
                    [
                        "action"   => "repo_management",
                        "group_id" => $repository->getProjectId(),
                        "repo_id"  => $repository->getId(),
                        "pane"     => "gerrit",
                    ]
                );
        }
    }
}
