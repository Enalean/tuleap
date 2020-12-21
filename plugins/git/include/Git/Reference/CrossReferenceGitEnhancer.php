<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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

use GitRepository;
use Tuleap\Date\TlpRelativeDatePresenter;
use Tuleap\Date\TlpRelativeDatePresenterBuilder;
use Tuleap\Git\CommitMetadata\CommitMetadata;
use Tuleap\Git\CommitMetadata\CommitMetadataRetriever;
use Tuleap\Git\GitPHP\Commit;
use Tuleap\Reference\AdditionalBadgePresenter;
use Tuleap\Reference\CrossReferencePresenter;
use Tuleap\Reference\Metadata\CreatedByPresenter;

class CrossReferenceGitEnhancer
{
    /**
     * @var CommitMetadataRetriever
     */
    private $commit_metadata_retriever;
    /**
     * @var \UserHelper
     */
    private $user_helper;
    /**
     * @var TlpRelativeDatePresenterBuilder
     */
    private $relative_date_builder;

    public function __construct(
        CommitMetadataRetriever $commit_metadata_retriever,
        \UserHelper $user_helper,
        TlpRelativeDatePresenterBuilder $relative_date_builder
    ) {
        $this->commit_metadata_retriever = $commit_metadata_retriever;
        $this->user_helper               = $user_helper;
        $this->relative_date_builder     = $relative_date_builder;
    }

    public function getCrossReferencePresenterWithCommitInformation(
        CrossReferencePresenter $basic_cross_reference_presenter,
        Commit $commit,
        GitRepository $repository,
        \PFUser $user
    ): CrossReferencePresenter {
        $git_commit_reference = $basic_cross_reference_presenter
            ->withTitle($commit->GetTitle(), null)
            ->withAdditionalBadges(
                [
                    new AdditionalBadgePresenter(
                        substr($commit->GetHash(), 0, 10)
                    )
                ]
            );

        return $this->addCreationMetadata($git_commit_reference, $repository, $commit, $user);
    }

    private function addCreationMetadata(
        CrossReferencePresenter $git_commit_reference,
        GitRepository $repository,
        Commit $commit,
        \PFUser $user
    ): CrossReferencePresenter {
        $commit_metadata = $this->commit_metadata_retriever->getMetadataByRepositoryAndCommits(
            $repository,
            $commit,
        );
        if (isset($commit_metadata[0])) {
            $git_commit_reference = $git_commit_reference->withCreationMetadata(
                $this->getCreatedByPresenter($commit_metadata[0], $commit),
                $this->getCreatedOnPresenter($commit, $user)
            );
        }

        return $git_commit_reference;
    }

    private function getCreatedByPresenter(CommitMetadata $commit_metadata, Commit $commit): CreatedByPresenter
    {
        $author = $commit_metadata->getAuthor();
        if ($author) {
            $created_by = new CreatedByPresenter(
                trim($this->user_helper->getDisplayNameFromUser($author) ?? ''),
                $author->hasAvatar(),
                $author->getAvatarUrl(),
            );
        } else {
            $created_by = new CreatedByPresenter(
                $commit->GetAuthorName(),
                false,
                '',
            );
        }

        return $created_by;
    }

    private function getCreatedOnPresenter(Commit $commit, \PFUser $user): TlpRelativeDatePresenter
    {
        $time = (int) $commit->GetAuthorEpoch();

        return $this->relative_date_builder->getTlpRelativeDatePresenterInInlineContext(
            (new \DateTimeImmutable())->setTimestamp($time),
            $user,
        );
    }
}
