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

use Git;
use Tuleap\Reference\CrossReferenceByNatureOrganizer;
use UserManager;

class OrganizeableGitCrossReferencesAndTheContributorsCollector
{
    /**
     * @var UserManager
     */
    private $user_manager;
    /**
     * @var CommitDetailsCrossReferenceInformationBuilder
     */
    private $information_builder;

    public function __construct(
        CommitDetailsCrossReferenceInformationBuilder $information_builder,
        UserManager $user_manager,
    ) {
        $this->information_builder = $information_builder;
        $this->user_manager        = $user_manager;
    }

    public function collectOrganizeableGitCrossReferencesAndTheContributorsCollection(
        CrossReferenceByNatureOrganizer $by_nature_organizer,
    ): OrganizeableGitCrossReferencesAndTheContributors {
        $contributor_emails = [];

        $organizeable_cross_references_information = [];
        foreach ($by_nature_organizer->getCrossReferencePresenters() as $cross_reference_presenter) {
            if ($cross_reference_presenter->type !== Git::REFERENCE_NATURE) {
                continue;
            }

            $information = $this->information_builder->getCommitDetailsCrossReferenceInformation(
                $by_nature_organizer->getCurrentUser(),
                $cross_reference_presenter
            );
            if (! $information) {
                $by_nature_organizer->removeUnreadableCrossReference($cross_reference_presenter);

                continue;
            }

            $organizeable_cross_references_information[] = $information;

            $contributor_emails[] = $information->getCommitDetails()->getAuthorEmail();
        }

        $contributors_email_collection = $this->user_manager->getUserCollectionByEmails(
            $this->deduplicateEmails($contributor_emails)
        );

        return new OrganizeableGitCrossReferencesAndTheContributors(
            $organizeable_cross_references_information,
            $contributors_email_collection
        );
    }

    /**
     * @param string[] $emails
     *
     * @return string[]
     */
    private function deduplicateEmails(array $emails): array
    {
        return array_keys(array_flip(array_filter($emails)));
    }
}
