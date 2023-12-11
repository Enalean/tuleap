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

namespace Tuleap\PullRequest\Reference;

use DateTimeImmutable;
use GitRepoNotFoundException;
use GitRepositoryFactory;
use PFUser;
use Project_AccessException;
use ProjectManager;
use pullrequestPlugin;
use Tuleap\Date\TlpRelativeDatePresenter;
use Tuleap\Date\TlpRelativeDatePresenterBuilder;
use Tuleap\PullRequest\Authorization\PullRequestPermissionChecker;
use Tuleap\PullRequest\Exception\UserCannotReadGitRepositoryException;
use Tuleap\PullRequest\PullRequest;
use Tuleap\PullRequest\PullRequestRetriever;
use Tuleap\Reference\AdditionalBadgePresenter;
use Tuleap\Reference\CrossReferenceByNatureOrganizer;
use Tuleap\Reference\CrossReferencePresenter;
use Tuleap\Reference\Metadata\CreatedByPresenter;
use Tuleap\User\RetrieveUserById;
use UserHelper;

final class CrossReferencePullRequestOrganizer
{
    public function __construct(
        private readonly ProjectManager $project_manager,
        private readonly PullRequestRetriever $pull_request_retriever,
        private readonly PullRequestPermissionChecker $permission_checker,
        private readonly GitRepositoryFactory $git_repository_factory,
        private readonly TlpRelativeDatePresenterBuilder $relative_date_builder,
        private readonly RetrieveUserById $user_manager,
        private readonly UserHelper $user_helper,
    ) {
    }

    public function organizePullRequestReferences(CrossReferenceByNatureOrganizer $by_nature_organizer): void
    {
        foreach ($by_nature_organizer->getCrossReferencePresenters() as $cross_reference_presenter) {
            if ($cross_reference_presenter->type !== pullrequestPlugin::REFERENCE_NATURE) {
                continue;
            }

            $this->moveCrossReferenceToRepositorySection($by_nature_organizer, $cross_reference_presenter);
        }
    }

    private function moveCrossReferenceToRepositorySection(
        CrossReferenceByNatureOrganizer $by_nature_organizer,
        CrossReferencePresenter $cross_reference_presenter,
    ): void {
        $user = $by_nature_organizer->getCurrentUser();

        $pull_request_id = (int) $cross_reference_presenter->target_value;
        $this->pull_request_retriever->getPullRequestById($pull_request_id)->match(
            function (PullRequest $pull_request) use ($user, $by_nature_organizer, $cross_reference_presenter) {
                try {
                    $this->permission_checker->checkPullRequestIsReadableByUser(
                        $pull_request,
                        $user
                    );
                } catch (GitRepoNotFoundException | Project_AccessException | UserCannotReadGitRepositoryException) {
                    $by_nature_organizer->removeUnreadableCrossReference($cross_reference_presenter);
                    return;
                }

                $repository = $this->git_repository_factory->getRepositoryById($pull_request->getRepositoryId());
                if (! $repository) {
                    $by_nature_organizer->removeUnreadableCrossReference($cross_reference_presenter);
                    return;
                }

                $project = $this->project_manager->getProject($cross_reference_presenter->target_gid);

                $by_nature_organizer->moveCrossReferenceToSection(
                    $this->withCreationMetadata(
                        $cross_reference_presenter
                            ->withTitle($pull_request->getTitle(), null)
                            ->withAdditionalBadges($this->getAdditionalBadgePresenters($pull_request)),
                        $pull_request,
                        $user,
                    ),
                    $project->getUnixNameLowerCase() . '/' . $repository->getName()
                );
            },
            function () use ($by_nature_organizer, $cross_reference_presenter) {
                $by_nature_organizer->removeUnreadableCrossReference($cross_reference_presenter);
            }
        );
    }

    private function withCreationMetadata(
        CrossReferencePresenter $cross_reference_presenter,
        PullRequest $pull_request,
        PFUser $user,
    ): CrossReferencePresenter {
        $created_by = $this->getCreatedByPresenter($pull_request);
        if (! $created_by) {
            return $cross_reference_presenter;
        }

        return $cross_reference_presenter->withCreationMetadata(
            $created_by,
            $this->getCreatedOnPresenter($pull_request, $user)
        );
    }

    private function getCreatedByPresenter(PullRequest $pull_request): ?CreatedByPresenter
    {
        $author = $this->user_manager->getUserById((int) $pull_request->getUserId());
        if (! $author) {
            return null;
        }

        return new CreatedByPresenter(
            trim($this->user_helper->getDisplayNameFromUser($author) ?? ''),
            $author->hasAvatar(),
            $author->getAvatarUrl(),
        );
    }

    private function getCreatedOnPresenter(PullRequest $pull_request, PFUser $user): TlpRelativeDatePresenter
    {
        $tlp_relative_date_presenter = $this->relative_date_builder->getTlpRelativeDatePresenterInInlineContext(
            new DateTimeImmutable('@' . $pull_request->getCreationDate()),
            $user,
        );

        return $tlp_relative_date_presenter;
    }

    /**
     * @return AdditionalBadgePresenter[]
     */
    private function getAdditionalBadgePresenters(PullRequest $pull_request): array
    {
        $additional_badges = [];
        switch ($pull_request->getStatus()) {
            case PullRequest::STATUS_ABANDONED:
                $additional_badges[] = AdditionalBadgePresenter::buildDanger(
                    dgettext('tuleap-pullrequest', 'Abandonned'),
                );
                break;
            case PullRequest::STATUS_MERGED:
                $additional_badges[] = AdditionalBadgePresenter::buildSuccess(
                    dgettext('tuleap-pullrequest', 'Merged'),
                );
                break;
            case PullRequest::STATUS_REVIEW:
                $additional_badges[] = AdditionalBadgePresenter::buildSecondary(
                    dgettext('tuleap-pullrequest', 'Review'),
                );
                break;
            default:
                break;
        }

        return $additional_badges;
    }
}
