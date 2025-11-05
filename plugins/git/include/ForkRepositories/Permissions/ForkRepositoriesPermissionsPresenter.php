<?php
/**
 * Copyright (c) Enalean, 2025 - present. All Rights Reserved.
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

namespace Tuleap\Git\ForkRepositories\Permissions;

use Project;
use Tuleap\Git\ForkRepositories\ForkRepositoriesUrlsBuilder;
use Tuleap\Request\CSRFSynchronizerTokenInterface;

/**
 * @psalm-immutable
 */
final readonly class ForkRepositoriesPermissionsPresenter
{
    public int $project_id;
    public int $nb_repositories;
    public string $fork_destination;
    public string $post_url;

    /**
     * @param list<string> $repositories_names
     */
    public function __construct(
        Project $destination_project,
        public CSRFSynchronizerTokenInterface $csrf_token,
        public \GitPresenters_AccessControlPresenter $access_control_presenter,
        public string $fork_type,
        public string $fork_path,
        public array $repositories_names,
        public string $repositories_ids,
    ) {
        $this->project_id       = (int) $destination_project->getID();
        $this->nb_repositories  = count($repositories_names);
        $this->fork_destination = $this->fork_type === ForkType::CROSS_PROJECT->value
                ?  $destination_project->getIconAndPublicName()
                : dgettext('tuleap-git', 'as personal fork');
        $this->post_url         = ForkRepositoriesUrlsBuilder::buildPOSTDoForksRepositoriesURL($destination_project);
    }
}
