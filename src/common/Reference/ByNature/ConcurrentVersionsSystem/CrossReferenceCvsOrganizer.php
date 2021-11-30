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

namespace Tuleap\Reference\ByNature\ConcurrentVersionsSystem;

use ProjectManager;
use Tuleap\ConcurrentVersionsSystem\CvsDao;
use Tuleap\Date\TlpRelativeDatePresenter;
use Tuleap\Date\TlpRelativeDatePresenterBuilder;
use Tuleap\Reference\AdditionalBadgePresenter;
use Tuleap\Reference\CrossReferenceByNatureOrganizer;
use Tuleap\Reference\CrossReferencePresenter;
use Tuleap\Reference\CrossReferenceSectionPresenter;
use Tuleap\Reference\Metadata\CreatedByPresenter;

class CrossReferenceCvsOrganizer
{
    /**
     * @var ProjectManager
     */
    private $project_manager;
    /**
     * @var CvsDao
     */
    private $dao;
    /**
     * @var TlpRelativeDatePresenterBuilder
     */
    private $date_presenter_builder;
    /**
     * @var \UserHelper
     */
    private $user_helper;
    /**
     * @var \UserManager
     */
    private $user_manager;

    public function __construct(
        ProjectManager $project_manager,
        CvsDao $dao,
        TlpRelativeDatePresenterBuilder $date_presenter_builder,
        \UserManager $user_manager,
        \UserHelper $user_helper,
    ) {
        $this->project_manager        = $project_manager;
        $this->dao                    = $dao;
        $this->date_presenter_builder = $date_presenter_builder;
        $this->user_manager           = $user_manager;
        $this->user_helper            = $user_helper;
    }

    public function organizeCvsReference(
        CrossReferencePresenter $cross_reference_presenter,
        CrossReferenceByNatureOrganizer $by_nature_organizer,
    ): void {
        $project = $this->project_manager->getProject($cross_reference_presenter->target_gid);

        $commit_row = $this->dao->searchCommit(
            (int) $cross_reference_presenter->target_value,
            $project->getUnixNameMixedCase()
        );
        if (! $commit_row) {
            $by_nature_organizer->removeUnreadableCrossReference($cross_reference_presenter);

            return;
        }

        $by_nature_organizer->moveCrossReferenceToSection(
            $this->getCvsCrossReferencePresenter(
                $cross_reference_presenter,
                $commit_row,
                $by_nature_organizer->getCurrentUser()
            ),
            CrossReferenceSectionPresenter::UNLABELLED,
        );
    }

    /**
     * @psalm-param array{"revision": string, "description": string, "whoid": int, "comm_when": string} $commit_row
     */
    private function getCvsCrossReferencePresenter(
        CrossReferencePresenter $cross_reference_presenter,
        array $commit_row,
        \PFUser $user,
    ): CrossReferencePresenter {
        $additional_badges = [];
        if ($commit_row['revision']) {
            $additional_badges[] = AdditionalBadgePresenter::buildSecondary($commit_row['revision']);
        }

        $cvs_cross_reference_presenter = $cross_reference_presenter
            ->withAdditionalBadges($additional_badges);

        $description = trim($commit_row['description']);
        [$title]     = explode("\n", $description);
        if (trim($title)) {
            $cvs_cross_reference_presenter = $cvs_cross_reference_presenter->withTitle(trim($title), null);
        }

        $created_by_presenter = $this->getCreatedByPresenter($commit_row);
        if (! $created_by_presenter) {
            return $cvs_cross_reference_presenter;
        }

        return $cvs_cross_reference_presenter->withCreationMetadata(
            $created_by_presenter,
            $this->getCreatedOnPresenter($commit_row, $user),
        );
    }

    /**
     * @psalm-param array{"revision": string, "description": string, "whoid": int, "comm_when": string} $commit_row
     */
    private function getCreatedByPresenter(array $commit_row): ?CreatedByPresenter
    {
        $user = $this->user_manager->getUserById($commit_row['whoid']);
        if (! $user) {
            return null;
        }

        return new CreatedByPresenter(
            $this->user_helper->getDisplayNameFromUser($user),
            $user->hasAvatar(),
            $user->getAvatarUrl(),
        );
    }

    /**
     * @psalm-param array{"revision": string, "description": string, "whoid": int, "comm_when": string} $commit_row
     */
    private function getCreatedOnPresenter(array $commit_row, \PFUser $user): TlpRelativeDatePresenter
    {
        $date = new \DateTimeImmutable($commit_row['comm_when']);

        return $this->date_presenter_builder->getTlpRelativeDatePresenterInInlineContext(
            $date,
            $user,
        );
    }
}
