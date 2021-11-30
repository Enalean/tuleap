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

use Tuleap\Reference\CrossReferenceByNatureOrganizer;

final class CrossReferenceGitOrganizer
{
    /**
     * @var CrossReferenceGitEnhancer
     */
    private $cross_reference_git_enhancer;
    /**
     * @var OrganizeableGitCrossReferencesAndTheContributorsCollector
     */
    private $collector;

    public function __construct(
        OrganizeableGitCrossReferencesAndTheContributorsCollector $collector,
        CrossReferenceGitEnhancer $cross_reference_git_filler,
    ) {
        $this->collector                    = $collector;
        $this->cross_reference_git_enhancer = $cross_reference_git_filler;
    }

    public function organizeGitReferences(CrossReferenceByNatureOrganizer $by_nature_organizer): void
    {
        $collection = $this->collector->collectOrganizeableGitCrossReferencesAndTheContributorsCollection(
            $by_nature_organizer
        );

        $contributors_email_collection = $collection->getContributorsEmailCollection();

        foreach ($collection->getOrganizeableCrossReferencesInformationCollection() as $information) {
            $by_nature_organizer->moveCrossReferenceToSection(
                $this->cross_reference_git_enhancer->getCrossReferencePresenterWithCommitInformation(
                    $information->getCrossReferencePresenter(),
                    $information->getCommitDetails(),
                    $by_nature_organizer->getCurrentUser(),
                    $contributors_email_collection
                ),
                $information->getSectionLabel(),
            );
        }
    }
}
