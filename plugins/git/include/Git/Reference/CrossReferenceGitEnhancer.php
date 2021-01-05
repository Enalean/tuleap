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

use Tuleap\Date\TlpRelativeDatePresenter;
use Tuleap\Date\TlpRelativeDatePresenterBuilder;
use Tuleap\Reference\AdditionalBadgePresenter;
use Tuleap\Reference\CrossReferencePresenter;
use Tuleap\Reference\Metadata\CreatedByPresenter;

class CrossReferenceGitEnhancer
{
    /**
     * @var \UserHelper
     */
    private $user_helper;
    /**
     * @var TlpRelativeDatePresenterBuilder
     */
    private $relative_date_builder;

    public function __construct(
        \UserHelper $user_helper,
        TlpRelativeDatePresenterBuilder $relative_date_builder
    ) {
        $this->user_helper           = $user_helper;
        $this->relative_date_builder = $relative_date_builder;
    }

    public function getCrossReferencePresenterWithCommitInformation(
        CrossReferencePresenter $basic_cross_reference_presenter,
        CommitDetails $commit_details,
        \PFUser $user
    ): CrossReferencePresenter {
        $git_commit_reference = $basic_cross_reference_presenter
            ->withTitle($commit_details->getTitle(), null)
            ->withAdditionalBadges($this->getAdditionalBadgesPresenters($commit_details));

        return $git_commit_reference->withCreationMetadata(
            $this->getCreatedByPresenter($commit_details),
            $this->getCreatedOnPresenter($commit_details, $user)
        );
    }

    private function getCreatedByPresenter(CommitDetails $commit_details): CreatedByPresenter
    {
        $author = $commit_details->getAuthor();
        if ($author) {
            $created_by = new CreatedByPresenter(
                trim($this->user_helper->getDisplayNameFromUser($author) ?? ''),
                $author->hasAvatar(),
                $author->getAvatarUrl(),
            );
        } else {
            $created_by = new CreatedByPresenter(
                $commit_details->getAuthorName(),
                false,
                '',
            );
        }

        return $created_by;
    }

    private function getCreatedOnPresenter(CommitDetails $commit_details, \PFUser $user): TlpRelativeDatePresenter
    {
        return $this->relative_date_builder->getTlpRelativeDatePresenterInInlineContext(
            new \DateTimeImmutable('@' . $commit_details->getCommitterEpoch()),
            $user,
        );
    }

    /**
     * @return AdditionalBadgePresenter[]
     */
    private function getAdditionalBadgesPresenters(CommitDetails $commit_details): array
    {
        return array_merge(
            $this->getBadgePresentersForBranchOrTag($commit_details),
            [
                new AdditionalBadgePresenter(
                    substr($commit_details->getHash(), 0, 10),
                    false,
                    false,
                )
            ]
        );
    }

    /**
     * @return AdditionalBadgePresenter[]
     */
    private function getBadgePresentersForBranchOrTag(CommitDetails $commit_details): array
    {
        $first_branch = $commit_details->getFirstBranch();
        if (! empty($first_branch)) {
            return [
                new AdditionalBadgePresenter($first_branch, true, false)
            ];
        }

        $first_tag = $commit_details->getFirstTag();
        if (! empty($first_tag)) {
            return [
                new AdditionalBadgePresenter($first_tag, true, true)
            ];
        }

        return [];
    }
}
