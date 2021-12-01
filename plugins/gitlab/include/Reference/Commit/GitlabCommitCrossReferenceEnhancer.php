<?php
/*
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

namespace Tuleap\Gitlab\Reference\Commit;

use Tuleap\Date\TlpRelativeDatePresenter;
use Tuleap\Date\TlpRelativeDatePresenterBuilder;
use Tuleap\Reference\AdditionalBadgePresenter;
use Tuleap\Reference\CrossReferencePresenter;
use Tuleap\Reference\Metadata\CreatedByPresenter;

class GitlabCommitCrossReferenceEnhancer
{
    /**
     * @var \UserManager
     */
    private $user_manager;
    /**
     * @var \UserHelper
     */
    private $user_helper;
    /**
     * @var TlpRelativeDatePresenterBuilder
     */
    private $relative_date_builder;

    public function __construct(
        \UserManager $user_manager,
        \UserHelper $user_helper,
        TlpRelativeDatePresenterBuilder $relative_date_builder,
    ) {
        $this->user_manager          = $user_manager;
        $this->user_helper           = $user_helper;
        $this->relative_date_builder = $relative_date_builder;
    }

    public function getCrossReferencePresenterWithCommitInformation(
        CrossReferencePresenter $basic_cross_reference_presenter,
        GitlabCommit $gitlab_commit,
        \PFUser $user,
    ): CrossReferencePresenter {
        return $basic_cross_reference_presenter
            ->withTitle($gitlab_commit->getCommitTitle(), null)
            ->withAdditionalBadges(
                $this->getAdditionalBadgesPresenters($gitlab_commit)
            )->withCreationMetadata(
                $this->getCreatedByPresenter($gitlab_commit),
                $this->getCreatedOnPresenter($gitlab_commit, $user)
            );
    }

    /**
     * @return AdditionalBadgePresenter[]
     */
    private function getAdditionalBadgesPresenters(GitlabCommit $gitlab_commit): array
    {
        $branch_badge = $this->getBadgePresenterForBranch($gitlab_commit);
        $commit_badge = AdditionalBadgePresenter::buildSecondary(
            substr($gitlab_commit->getCommitSha1(), 0, 10),
        );

        if ($branch_badge === null) {
            return [$commit_badge];
        }

        return [
            $branch_badge,
            $commit_badge,
        ];
    }

    private function getBadgePresenterForBranch(GitlabCommit $gitlab_commit): ?AdditionalBadgePresenter
    {
        if ($gitlab_commit->getCommitBranchName() === '') {
            return null;
        }

        return AdditionalBadgePresenter::buildPrimary($gitlab_commit->getCommitBranchName());
    }

    private function getCreatedOnPresenter(GitlabCommit $gitlab_commit, \PFUser $user): TlpRelativeDatePresenter
    {
        return $this->relative_date_builder->getTlpRelativeDatePresenterInInlineContext(
            (new \DateTimeImmutable())->setTimestamp($gitlab_commit->getCommitDate()),
            $user
        );
    }

    private function getCreatedByPresenter(GitlabCommit $gitlab_commit): CreatedByPresenter
    {
        $tuleap_user = $this->user_manager->getUserByEmail($gitlab_commit->getCommitAuthorEmail());

        if ($tuleap_user === null) {
            return new CreatedByPresenter(
                $gitlab_commit->getCommitAuthorName(),
                false,
                ''
            );
        }

        return new CreatedByPresenter(
            trim($this->user_helper->getDisplayNameFromUser($tuleap_user) ?? ''),
            $tuleap_user->hasAvatar(),
            $tuleap_user->getAvatarUrl()
        );
    }
}
