<?php
/**
 * Copyright (c) Enalean, 2023 - present. All Rights Reserved.
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

namespace Tuleap\PullRequest;

use Git_GitRepositoryUrlManager;
use GitRepository;
use PFUser;
use Project_AccessException;
use Tuleap\Git\Repository\View\ParentRepositoryPresenter;
use Tuleap\Git\Repository\View\PresentPullRequest;
use URLVerification;

final class PullRequestEmptyStatePresenterBuilder
{
    public function __construct(
        private readonly Git_GitRepositoryUrlManager $url_manager,
        private readonly URLVerification $url_verificator,
    ) {
    }

    public function build(
        \GitRepository $repository,
        \PFUser $user,
    ): PresentPullRequest {
        return new PullRequestEmptyStatePresenter(
            $repository->getId(),
            (int) $repository->getProjectId(),
            $repository->isMigratedToGerrit(),
            $this->getParentRepositoryPresenter($repository, $user)
        );
    }

    private function getParentRepositoryPresenter(\GitRepository $repository, \PFUser $user): ?ParentRepositoryPresenter
    {
        $parent_repository = $repository->getParent();
        if (! $parent_repository) {
            return null;
        }

        return new ParentRepositoryPresenter(
            $parent_repository,
            $this->url_manager->getRepositoryBaseUrl($parent_repository),
            $this->userCanSeeParentRepository($user, $parent_repository),
        );
    }

    private function userCanSeeParentRepository(PFUser $current_user, GitRepository $repository): bool
    {
        try {
            return $this->url_verificator->userCanAccessProject($current_user, $repository->getProject())
                && $repository->userCanRead($current_user);
        } catch (Project_AccessException $exception) {
            return false;
        }
    }
}
