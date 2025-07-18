<?php
/**
 * Copyright (c) Enalean, 2022 - present. All Rights Reserved.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\Git\Artifact\Action;

use GitRepositoryFactory;
use PFUser;
use Project;
use Tuleap\Git\PullRequestEndpointsAvailableChecker;
use Tuleap\Git\REST\v1\Branch\BranchNameCreatorFromArtifact;
use Tuleap\Layout\JavascriptAssetGeneric;
use Tuleap\Tracker\Artifact\ActionButtons\AdditionalButtonAction;
use Tuleap\Tracker\Artifact\ActionButtons\AdditionalButtonLinkPresenter;
use Tuleap\Tracker\Artifact\Artifact;

final class CreateBranchButtonFetcher
{
    public function __construct(
        private GitRepositoryFactory $git_repository_factory,
        private BranchNameCreatorFromArtifact $branch_name_creator_from_artifact,
        private JavascriptAssetGeneric $javascript_asset,
        private PullRequestEndpointsAvailableChecker $pr_checker,
    ) {
    }

    public function getActionButton(Artifact $artifact, PFUser $user): ?AdditionalButtonAction
    {
        $project = $artifact->getTracker()->getProject();
        if (! $this->doesProjectHaveRepositoriesUserCanRead($project, $user)) {
            return null;
        }

        $are_pullrequest_endpoints_available = $this->pr_checker->arePullRequestEndpointsAvailable();

        $link_label = $are_pullrequest_endpoints_available ?
            dgettext('tuleap-git', 'Create Git branch and pull request') :
            dgettext('tuleap-git', 'Create Git branch');

        $link = new AdditionalButtonLinkPresenter(
            $link_label,
            '',
            'create-git-branch-button',
            'fas fa-code-branch',
            'artifact-create-git-branches',
            [
                [
                    'name'  => 'project-id',
                    'value' => (string) $project->getID(),
                ],
                [
                    'name'  => 'git-branch-name-preview',
                    'value' => $this->branch_name_creator_from_artifact->getBaseBranchName($artifact),
                ],
                [
                    'name'  => 'are-pullrequest-endpoints-available',
                    'value' => $are_pullrequest_endpoints_available,
                ],
            ],
        );

        return new AdditionalButtonAction(
            $link,
            $this->javascript_asset
        );
    }

    private function doesProjectHaveRepositoriesUserCanRead(Project $project, PFUser $user): bool
    {
        foreach ($this->git_repository_factory->getAllRepositories($project) as $repository) {
            if ($repository->userCanRead($user)) {
                return true;
            }
        }

        return false;
    }
}
