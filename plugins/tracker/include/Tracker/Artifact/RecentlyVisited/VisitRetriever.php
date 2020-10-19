<?php
/**
 * Copyright (c) Enalean, 2017 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Artifact\RecentlyVisited;

use Tuleap\Glyph\GlyphFinder;
use Tuleap\User\History\HistoryEntry;
use Tuleap\User\History\HistoryEntryCollection;

class VisitRetriever
{
    /**
     * @var RecentlyVisitedDao
     */
    private $dao;
    /**
     * @var \Tracker_ArtifactFactory
     */
    private $artifact_factory;
    /**
     * @var GlyphFinder
     */
    private $glyph_finder;

    public function __construct(
        RecentlyVisitedDao $dao,
        \Tracker_ArtifactFactory $artifact_factory,
        GlyphFinder $glyph_finder
    ) {
        $this->dao              = $dao;
        $this->artifact_factory = $artifact_factory;
        $this->glyph_finder     = $glyph_finder;
    }

    /**
     * @return \Tuleap\Tracker\Artifact\Artifact[]
     * @throws \DataAccessException
     */
    public function getMostRecentlySeenArtifacts(\PFUser $user, $nb_maximum_artifacts)
    {
        $recently_visited_rows = $this->dao->searchVisitByUserId($user->getId(), $nb_maximum_artifacts);
        $artifacts_id = [];
        foreach ($recently_visited_rows as $recently_visited_row) {
            $artifacts_id[] = $recently_visited_row['artifact_id'];
        }

        return $this->artifact_factory->getArtifactsByArtifactIdList($artifacts_id);
    }

    /**
     * @throws \DataAccessException
     */
    public function getVisitHistory(HistoryEntryCollection $entry_collection, int $max_length_history): void
    {
        $recently_visited_rows = $this->dao->searchVisitByUserId(
            $entry_collection->getUser()->getId(),
            $max_length_history
        );

        foreach ($recently_visited_rows as $recently_visited_row) {
            $artifact = $this->artifact_factory->getArtifactById($recently_visited_row['artifact_id']);
            if ($artifact === null) {
                continue;
            }

            $collection = new HistoryQuickLinkCollection($artifact, $entry_collection->getUser());
            \EventManager::instance()->processEvent($collection);
            $tracker = $artifact->getTracker();

            $entry_collection->addEntry(
                new HistoryEntry(
                    $recently_visited_row['created_on'],
                    $artifact->getXRef(),
                    $artifact->getUri(),
                    $artifact->getTitle(),
                    $tracker->getColor()->getName(),
                    $this->glyph_finder->get('tuleap-tracker-small'),
                    $this->glyph_finder->get('tuleap-tracker'),
                    '',
                    $tracker->getProject(),
                    $collection->getLinks()
                )
            );
        }
    }
}
