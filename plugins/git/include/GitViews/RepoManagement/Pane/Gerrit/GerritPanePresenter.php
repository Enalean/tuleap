<?php
/**
 * Copyright (c) Enalean, 2025-Present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\Git\GitViews\RepoManagement\Pane\Gerrit;

use GitRepository;
use Tuleap\Git\GitViews\RepoManagement\Pane\Gerrit;
use Tuleap\Request\CSRFSynchronizerTokenInterface;

final readonly class GerritPanePresenter
{
    public int $repository_id;
    public int $project_id;
    public string $gerrit_pane;
    public string $settings_action;
    public string $disconnect_gerrit_project_option_name;
    public string $delete_gerrit_project_value;
    public string $readonly_gerrit_project_value;
    public string $submit_button_name;

    public function __construct(
        public CSRFSynchronizerTokenInterface $csrf_token,
        GitRepository $repository,
        public ?GerritRepositoryPresenter $gerrit_repository,
        public ?GerritMigrationFailurePresenter $migration_failure,
    ) {
        $this->repository_id   = $repository->getId();
        $this->project_id      = (int) $repository->getProjectId();
        $this->gerrit_pane     = Gerrit::ID;
        $this->settings_action = 'repo_management';

        $this->disconnect_gerrit_project_option_name = Gerrit::OPTION_DISCONNECT_GERRIT_PROJECT;
        $this->delete_gerrit_project_value           = Gerrit::OPTION_DELETE_GERRIT_PROJECT;
        $this->readonly_gerrit_project_value         = Gerrit::OPTION_READONLY_GERRIT_PROJECT;
        $this->submit_button_name                    = Gerrit::CONFIRM_DISCONNECT_ACTION;
    }
}
