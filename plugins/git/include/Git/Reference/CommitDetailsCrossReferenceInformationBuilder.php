<?php
/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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

namespace Tuleap\Git\Reference;

use ProjectManager;
use Tuleap\Reference\CrossReferencePresenter;

class CommitDetailsCrossReferenceInformationBuilder
{
    /**
     * @var ProjectManager
     */
    private $project_manager;
    /**
     * @var \Git_ReferenceManager
     */
    private $git_reference_manager;
    /**
     * @var CommitProvider
     */
    private $commit_provider;
    /**
     * @var CommitDetailsRetriever
     */
    private $commit_details_retriever;

    public function __construct(
        ProjectManager $project_manager,
        \Git_ReferenceManager $git_reference_manager,
        CommitProvider $commit_provider,
        CommitDetailsRetriever $commit_details_retriever,
    ) {
        $this->project_manager          = $project_manager;
        $this->git_reference_manager    = $git_reference_manager;
        $this->commit_provider          = $commit_provider;
        $this->commit_details_retriever = $commit_details_retriever;
    }

    public function getCommitDetailsCrossReferenceInformation(
        \PFUser $user,
        CrossReferencePresenter $cross_reference_presenter,
    ): ?CommitDetailsCrossReferenceInformation {
        $project = $this->project_manager->getProject($cross_reference_presenter->target_gid);

        $commit_info = $this->git_reference_manager->getCommitInfoFromReferenceValue(
            $project,
            $cross_reference_presenter->target_value
        );

        $repository = $commit_info->getRepository();

        if (! $repository || ! $repository->userCanRead($user)) {
            return null;
        }

        $commit = $this->commit_provider->getCommit($repository, $commit_info->getSha1());
        if (! $commit) {
            return null;
        }

        $commit_details = $this->commit_details_retriever->retrieveCommitDetails($repository, $commit);
        if (! $commit_details) {
            return null;
        }

        $section_label = $project->getUnixNameLowerCase() . '/' . $repository->getFullName();

        return new CommitDetailsCrossReferenceInformation(
            $commit_details,
            $cross_reference_presenter,
            $section_label
        );
    }
}
