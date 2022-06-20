<?php
/**
 * Copyright (c) Enalean, 2021 - present. All Rights Reserved.
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

namespace Tuleap\Gitlab\Artifact\Action;

use PFUser;
use Tuleap\Gitlab\Artifact\BranchNameCreatorFromArtifact;
use Tuleap\Gitlab\Plugin\GitlabIntegrationAvailabilityChecker;
use Tuleap\Gitlab\REST\v1\GitlabRepositoryRepresentationFactory;
use Tuleap\Layout\JavascriptAsset;
use Tuleap\Tracker\Artifact\ActionButtons\AdditionalButtonAction;
use Tuleap\Tracker\Artifact\ActionButtons\AdditionalButtonLinkPresenter;
use Tuleap\Tracker\Artifact\Artifact;

final class CreateBranchButtonFetcher
{
    private GitlabIntegrationAvailabilityChecker $availability_checker;
    private JavascriptAsset $javascript_asset;
    private GitlabRepositoryRepresentationFactory $representation_factory;
    private BranchNameCreatorFromArtifact $branch_name_creator_from_artifact;

    public function __construct(
        GitlabIntegrationAvailabilityChecker $availability_checker,
        GitlabRepositoryRepresentationFactory $representation_factory,
        BranchNameCreatorFromArtifact $branch_name_creator_from_artifact,
        JavascriptAsset $javascript_asset,
    ) {
        $this->availability_checker              = $availability_checker;
        $this->javascript_asset                  = $javascript_asset;
        $this->representation_factory            = $representation_factory;
        $this->branch_name_creator_from_artifact = $branch_name_creator_from_artifact;
    }

    public function getActionButton(Artifact $artifact, PFUser $user): ?AdditionalButtonAction
    {
        $project    = $artifact->getTracker()->getProject();
        $project_id = (int) $project->getID();

        if (! $this->availability_checker->isGitlabIntegrationAvailableForProject($project)) {
            return null;
        }

        if (! $user->isMember($project_id)) {
            return null;
        }

        if (! $artifact->userCanView($user)) {
            return null;
        }

        $representations = $this->representation_factory->getAllIntegrationsRepresentationsInProjectWithConfiguredToken(
            $project
        );

        if (empty($representations)) {
            return null;
        }

        $link_label = dgettext('tuleap-gitlab', 'Create GitLab branch and merge request');
        $icon       = 'fab fa-gitlab';
        $link       = new AdditionalButtonLinkPresenter(
            $link_label,
            "",
            "",
            $icon,
            'artifact-create-gitlab-branches',
            [
                [
                    'name'  => "integrations",
                    'value' => json_encode($representations, JSON_THROW_ON_ERROR),
                ],
                [
                    'name'  => "artifact-id",
                    'value' => $artifact->getId(),
                ],
                [
                    'name'  => 'branch-name',
                    'value' => $this->branch_name_creator_from_artifact->getBaseBranchName($artifact),
                ],
            ],
        );

        return new AdditionalButtonAction(
            $link,
            $this->javascript_asset->getFileURL()
        );
    }
}
