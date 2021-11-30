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

namespace Tuleap\Reference\ByNature\News;

use Tuleap\News\Exceptions\NewsNotFoundException;
use Tuleap\News\Exceptions\RestrictedNewsAccessException;
use Tuleap\News\NewsRetriever;
use Tuleap\Reference\CrossReferenceByNatureOrganizer;
use Tuleap\Reference\CrossReferencePresenter;
use Tuleap\Reference\CrossReferenceSectionPresenter;

class CrossReferenceNewsOrganizer
{
    /**
     * @var NewsRetriever
     */
    private $news_retriever;

    public function __construct(NewsRetriever $news_retriever)
    {
        $this->news_retriever = $news_retriever;
    }

    public function organizeNewsReference(
        CrossReferencePresenter $cross_reference_presenter,
        CrossReferenceByNatureOrganizer $by_nature_organizer,
    ): void {
        try {
            $news = $this->news_retriever->getNewsUserCanView((int) $cross_reference_presenter->target_value);
            $by_nature_organizer->moveCrossReferenceToSection(
                $cross_reference_presenter->withTitle($news->getSummary(), null),
                CrossReferenceSectionPresenter::UNLABELLED,
            );
        } catch (NewsNotFoundException | RestrictedNewsAccessException $e) {
            $by_nature_organizer->removeUnreadableCrossReference($cross_reference_presenter);
        }
    }
}
