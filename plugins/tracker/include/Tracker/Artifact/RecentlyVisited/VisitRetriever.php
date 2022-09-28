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
use Tuleap\Tracker\Artifact\StatusBadgeBuilder;
use Tuleap\User\History\HistoryEntry;
use Tuleap\User\History\HistoryEntryBadge;
use Tuleap\User\History\HistoryEntryCollection;

class VisitRetriever
{
    public function __construct(
        private RecentlyVisitedDao $dao,
        private \Tracker_ArtifactFactory $artifact_factory,
        private GlyphFinder $glyph_finder,
        private StatusBadgeBuilder $status_badge_builder,
    ) {
    }

    /**
     * @return \Tuleap\Tracker\Artifact\Artifact[]
     * @throws \DataAccessException
     */
    public function getMostRecentlySeenArtifacts(\PFUser $user, $nb_maximum_artifacts)
    {
        $user_id               = $user->getId();
        $recently_visited_rows = $this->dao->searchVisitByUserId($user_id, $nb_maximum_artifacts);
        if ($recently_visited_rows === false) {
            throw new \RuntimeException(sprintf('Could not search tracker visits for user #%d', $user_id));
        }
        $artifacts_id = [];
        foreach ($recently_visited_rows as $recently_visited_row) {
            $artifacts_id[] = $recently_visited_row['artifact_id'];
        }

        return $this->artifact_factory->getArtifactsByArtifactIdList($artifacts_id);
    }

    /**
     * @throws \DataAccessException
     */
    public function getVisitHistory(HistoryEntryCollection $entry_collection, int $max_length_history, \PFUser $user): void
    {
        $user_id               = $entry_collection->getUser()->getId();
        $recently_visited_rows = $this->dao->searchVisitByUserId(
            $user_id,
            $max_length_history
        );
        if ($recently_visited_rows === false) {
            throw new \RuntimeException(sprintf('Could not search tracker visits for user #%d', $user_id));
        }

        foreach ($recently_visited_rows as $recently_visited_row) {
            $artifact = $this->artifact_factory->getArtifactById($recently_visited_row['artifact_id']);
            if ($artifact === null) {
                continue;
            }

            $collection = new SwitchToLinksCollection($artifact, $entry_collection->getUser());
            \EventManager::instance()->processEvent($collection);
            $tracker = $artifact->getTracker();

            $entry_collection->addEntry(
                new HistoryEntry(
                    $recently_visited_row['created_on'],
                    $collection->getXRef(),
                    $collection->getMainUri(),
                    $artifact->getTitle() ?? '',
                    $tracker->getColor()->getName(),
                    $this->glyph_finder->get('tuleap-tracker-small'),
                    $this->glyph_finder->get('tuleap-tracker'),
                    $collection->getIconName(),
                    $tracker->getProject(),
                    $collection->getQuickLinks(),
                    $this->status_badge_builder->buildBadgesFromArtifactStatus(
                        $artifact,
                        $user,
                        static fn (string $label, ?string $color) => new HistoryEntryBadge($label, $color),
                    ),
                )
            );
        }
    }
}
