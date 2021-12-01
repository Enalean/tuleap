<?php
/**
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

declare(strict_types=1);

namespace Tuleap\Gitlab\Reference;

use Project;
use Reference;
use Tuleap\Gitlab\Reference\Branch\GitlabBranchReference;
use Tuleap\Gitlab\Reference\Commit\GitlabCommitReference;
use Tuleap\Gitlab\Reference\MergeRequest\GitlabMergeRequestReference;
use Tuleap\Gitlab\Reference\Tag\GitlabTagReference;
use Tuleap\Gitlab\Repository\GitlabRepositoryIntegrationFactory;

class GitlabReferenceBuilder
{
    private ReferenceDao $reference_dao;
    private GitlabRepositoryIntegrationFactory $repository_integration_factory;

    public function __construct(
        ReferenceDao $reference_dao,
        GitlabRepositoryIntegrationFactory $repository_integration_factory,
    ) {
        $this->reference_dao                  = $reference_dao;
        $this->repository_integration_factory = $repository_integration_factory;
    }

    public function buildGitlabReference(Project $project, string $keyword, string $value): ?Reference
    {
        if (
            $keyword !== GitlabCommitReference::REFERENCE_NAME &&
            $keyword !== GitlabMergeRequestReference::REFERENCE_NAME &&
            $keyword !== GitlabTagReference::REFERENCE_NAME &&
            $keyword !== GitlabBranchReference::REFERENCE_NAME
        ) {
            return null;
        }

        if ($this->reference_dao->isAProjectReferenceExisting($keyword, (int) $project->getID())) {
            //Keep the behaviour of the already existing project reference
            return null;
        }

        $reference_splitted_values = GitlabReferenceExtractor::splitRepositoryNameAndReferencedItemId(
            $value
        );

        $repository_name = $reference_splitted_values->getRepositoryName();
        $item_id         = $reference_splitted_values->getValue();

        if (! $repository_name || ! $item_id) {
            return null;
        }

        $repository = $this->repository_integration_factory->getIntegrationByNameInProject(
            $project,
            $repository_name
        );

        if ($repository === null) {
            return null;
        }

        if ($keyword === GitlabCommitReference::REFERENCE_NAME) {
            return new GitlabCommitReference(
                $repository,
                $project,
                $item_id
            );
        } elseif ($keyword === GitlabTagReference::REFERENCE_NAME) {
            return new GitlabTagReference(
                $repository,
                $project,
                $item_id
            );
        } elseif ($keyword === GitlabBranchReference::REFERENCE_NAME) {
            return new GitlabBranchReference(
                $repository,
                $project,
                $item_id
            );
        }

        return new GitlabMergeRequestReference(
            $repository,
            $project,
            (int) $item_id
        );
    }
}
