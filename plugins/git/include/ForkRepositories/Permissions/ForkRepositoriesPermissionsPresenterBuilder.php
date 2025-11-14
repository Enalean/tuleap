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
use Tuleap\Git\GitAccessControlPresenterBuilder;
use Tuleap\Git\GitPHP\NotFoundException;
use Tuleap\Git\RetrieveGitRepository;
use Tuleap\Request\CSRFSynchronizerTokenInterface;

final readonly class ForkRepositoriesPermissionsPresenterBuilder
{
    public function __construct(
        private RetrieveGitRepository $retrieve_git_repository,
        private GitAccessControlPresenterBuilder $access_control_presenter_builder,
    ) {
    }

    /**
     * @throws NotFoundException
     */
    public function build(
        Project $destination_project,
        \PFUser $user,
        ForkRepositoriesFormInputs $form_inputs,
        CSRFSynchronizerTokenInterface $csrf_token,
    ): ForkRepositoriesPermissionsPresenter {
        $first_repository = $this->getFirstRepository($form_inputs);

        return new ForkRepositoriesPermissionsPresenter(
            $destination_project,
            $csrf_token,
            count($form_inputs->repositories_ids) > 1
                ? $this->access_control_presenter_builder->buildWithDefaults($first_repository, $destination_project)
                : $this->access_control_presenter_builder->buildForSingleRepositoryFork($first_repository, $destination_project, $user),
            $form_inputs->fork_type->value,
            $form_inputs->path,
            array_map(
                fn(string $id) => $this->retrieve_git_repository->getRepositoryById((int) $id)?->getFullName() ?? '',
                $form_inputs->repositories_ids,
            ),
            implode(',', $form_inputs->repositories_ids),
        );
    }

    /**
     * @throws NotFoundException
     */
    private function getFirstRepository(ForkRepositoriesFormInputs $form_inputs): \GitRepository
    {
        if (empty($form_inputs->repositories_ids)) {
            throw new NotFoundException();
        }

        $first_repository = $this->retrieve_git_repository->getRepositoryById(
            (int) $form_inputs->repositories_ids[0],
        );

        if ($first_repository === null) {
            throw new NotFoundException();
        }
        return $first_repository;
    }
}
