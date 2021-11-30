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

namespace Tuleap\Reference\ByNature\Wiki;

use ProjectManager;
use Tuleap\Reference\CrossReferenceByNatureOrganizer;
use Tuleap\Reference\CrossReferencePresenter;
use Tuleap\Reference\CrossReferenceSectionPresenter;

class CrossReferenceWikiOrganizer
{
    /**
     * @var ProjectManager
     */
    private $project_manager;
    /**
     * @var WikiPageFromReferenceValueRetriever
     */
    private $wiki_page_retriever;

    public function __construct(
        ProjectManager $project_manager,
        WikiPageFromReferenceValueRetriever $wiki_page_retriever,
    ) {
        $this->project_manager     = $project_manager;
        $this->wiki_page_retriever = $wiki_page_retriever;
    }

    public function organizeWikiReference(
        CrossReferencePresenter $cross_reference_presenter,
        CrossReferenceByNatureOrganizer $by_nature_organizer,
    ): void {
        $project = $this->project_manager->getProject($cross_reference_presenter->target_gid);

        $page = $this->wiki_page_retriever->getWikiPageUserCanView(
            $project,
            $by_nature_organizer->getCurrentUser(),
            $cross_reference_presenter->target_value
        );
        if (! $page) {
            $by_nature_organizer->removeUnreadableCrossReference($cross_reference_presenter);

            return;
        }

        $by_nature_organizer->moveCrossReferenceToSection(
            $cross_reference_presenter,
            CrossReferenceSectionPresenter::UNLABELLED,
        );
    }
}
