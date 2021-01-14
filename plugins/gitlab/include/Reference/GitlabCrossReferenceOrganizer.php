<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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

use Tuleap\Gitlab\Repository\GitlabRepositoryFactory;
use Tuleap\Reference\CrossReferenceByNatureOrganizer;
use Tuleap\Reference\CrossReferencePresenter;

class GitlabCrossReferenceOrganizer
{
    /**
     * @var \ProjectManager
     */
    private $project_manager;
    /**
     * @var GitlabRepositoryFactory
     */
    private $gitlab_repository_factory;
    /**
     * @var GitlabCommitFactory
     */
    private $gitlab_commit_factory;
    /**
     * @var GitlabCrossReferenceEnhancer
     */
    private $gitlab_cross_reference_enhancer;

    public function __construct(
        GitlabRepositoryFactory $gitlab_repository_factory,
        GitlabCommitFactory $gitlab_commit_factory,
        GitlabCrossReferenceEnhancer $gitlab_cross_reference_enhancer,
        \ProjectManager $project_manager
    ) {
        $this->gitlab_repository_factory       = $gitlab_repository_factory;
        $this->gitlab_commit_factory           = $gitlab_commit_factory;
        $this->gitlab_cross_reference_enhancer = $gitlab_cross_reference_enhancer;
        $this->project_manager                 = $project_manager;
    }

    public function organizeGitLabReferences(CrossReferenceByNatureOrganizer $by_nature_organizer): void
    {
        foreach ($by_nature_organizer->getCrossReferencePresenters() as $cross_reference_presenter) {
            if ($cross_reference_presenter->type !== GitlabCommitReference::NATURE_NAME) {
                continue;
            }

            $this->moveGitlabCrossReferenceToRepositorySection($by_nature_organizer, $cross_reference_presenter);
        }
    }

    private function moveGitlabCrossReferenceToRepositorySection(
        CrossReferenceByNatureOrganizer $by_nature_organizer,
        CrossReferencePresenter $cross_reference_presenter
    ): void {
        $user    = $by_nature_organizer->getCurrentUser();
        $project = $this->project_manager->getProject($cross_reference_presenter->target_gid);

        [$repository_name, $sha1] = GitlabReferenceExtractor::splitRepositoryNameAndReferencedItemId($cross_reference_presenter->target_value);

        if (! $repository_name || ! $sha1) {
            return;
        }

        $repository = $this->gitlab_repository_factory->getGitlabRepositoryByNameInProject(
            $project,
            $repository_name
        );

        if ($repository === null) {
            $by_nature_organizer->removeUnreadableCrossReference($cross_reference_presenter);
            return;
        }

        $commit_info = $this->gitlab_commit_factory->getGitlabCommitInRepositoryWithSha1(
            $repository,
            $sha1
        );

        if ($commit_info === null) {
            $by_nature_organizer->removeUnreadableCrossReference($cross_reference_presenter);
            return;
        }

        $by_nature_organizer->moveCrossReferenceToSection(
            $this->gitlab_cross_reference_enhancer->getCrossReferencePresenterWithCommitInformation(
                $cross_reference_presenter,
                $commit_info,
                $user
            ),
            $project->getUnixNameLowerCase() . '/' . $repository->getName()
        );
    }
}
