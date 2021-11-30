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

namespace Tuleap\Docman\Reference;

use Tuleap\Reference\CrossReferenceByNatureOrganizer;
use Tuleap\Reference\CrossReferencePresenter;
use Tuleap\Reference\CrossReferenceSectionPresenter;
use Tuleap\Reference\TitleBadgePresenter;

class CrossReferenceDocmanOrganizer
{
    /**
     * @var \ProjectManager
     */
    private $project_manager;
    /**
     * @var DocumentFromReferenceValueFinder
     */
    private $finder;
    /**
     * @var DocumentIconPresenterBuilder
     */
    private $icon_presenter_builder;

    public function __construct(
        \ProjectManager $project_manager,
        DocumentFromReferenceValueFinder $finder,
        DocumentIconPresenterBuilder $icon_presenter_builder,
    ) {
        $this->project_manager        = $project_manager;
        $this->finder                 = $finder;
        $this->icon_presenter_builder = $icon_presenter_builder;
    }

    public function organizeDocumentReferences(CrossReferenceByNatureOrganizer $by_nature_organizer): void
    {
        foreach ($by_nature_organizer->getCrossReferencePresenters() as $cross_reference_presenter) {
            if ($cross_reference_presenter->type !== \ReferenceManager::REFERENCE_NATURE_DOCUMENT) {
                continue;
            }

            $user    = $by_nature_organizer->getCurrentUser();
            $project = $this->project_manager->getProject($cross_reference_presenter->target_gid);

            $item = $this->finder->findItem($project, $user, $cross_reference_presenter->target_value);
            if (! $item) {
                $by_nature_organizer->removeUnreadableCrossReference($cross_reference_presenter);
                continue;
            }

            $by_nature_organizer->moveCrossReferenceToSection(
                $this->addTitleBadgeOnCrossReference($cross_reference_presenter, $item),
                CrossReferenceSectionPresenter::UNLABELLED
            );
        }
    }

    private function addTitleBadgeOnCrossReference(
        CrossReferencePresenter $cross_reference_presenter,
        \Docman_Item $item,
    ): CrossReferencePresenter {
        $icon_presenter = $this->icon_presenter_builder->buildForItem($item);

        return $cross_reference_presenter
            ->withTitle(
                (string) $item->getTitle(),
                TitleBadgePresenter::buildIconBadge(
                    $icon_presenter->icon,
                    $icon_presenter->color,
                ),
            );
    }
}
