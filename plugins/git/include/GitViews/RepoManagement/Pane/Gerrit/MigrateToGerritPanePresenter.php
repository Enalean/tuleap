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

use Git;
use GitRepository;
use Project;
use Tuleap\Git\GitViews\RepoManagement\Pane\Gerrit;
use Tuleap\Option\Option;
use Tuleap\Request\CSRFSynchronizerTokenInterface;

final readonly class MigrateToGerritPanePresenter
{
    public bool $is_repository_created;
    public int $project_id;
    public string $gerrit_pane;
    public string $migrate_to_gerrit_action;
    public bool $is_parent_suspended;
    public int $repository_id;
    public string $repository_name;

    /**
     * @param Option<Project>               $parent
     * @param list<GerritServerPresenter>   $gerrit_servers
     * @param list<GerritTemplatePresenter> $gerrit_templates
     */
    public function __construct(
        public CSRFSynchronizerTokenInterface $csrf_token,
        GitRepository $repository,
        Option $parent,
        public string $gerrit_project_name,
        public array $gerrit_servers,
        public array $gerrit_templates,
    ) {
        $this->is_repository_created    = $repository->isCreated();
        $this->project_id               = (int) $repository->getProjectId();
        $this->gerrit_pane              = Gerrit::ID;
        $this->migrate_to_gerrit_action = Git::ADMIN_MIGRATE_TO_GERRIT_ACTION;
        $this->repository_id            = $repository->getId();

        $this->is_parent_suspended = $parent->mapOr(
            static fn(Project $parent_project) => ! $parent_project->isActive(),
            false
        );

        $this->repository_name = $repository->getName();
    }
}
