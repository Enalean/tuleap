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

declare(strict_types=1);

namespace Tuleap\Tracker\Artifact\RecentlyVisited;

use Psr\EventDispatcher\EventDispatcherInterface;
use Tuleap\Glyph\GlyphFinder;
use Tuleap\Glyph\GlyphNotFoundException;
use Tuleap\Tracker\Artifact\StatusBadgeBuilder;
use Tuleap\User\History\HistoryEntry;
use Tuleap\User\History\HistoryEntryBadge;
use Tuleap\User\History\HistoryEntryCollection;

final class VisitRetriever
{
    public const TYPE = 'artifact';

    public function __construct(
        private RecentlyVisitedDao $dao,
        private \Tracker_ArtifactFactory $artifact_factory,
        private GlyphFinder $glyph_finder,
        private StatusBadgeBuilder $status_badge_builder,
        private EventDispatcherInterface $event_manager,
    ) {
    }

    /**
     * @return \Tuleap\Tracker\Artifact\Artifact[]
     * @throws \DataAccessException
     */
    public function getMostRecentlySeenArtifacts(\PFUser $user, int $nb_maximum_artifacts): array
    {
        $user_id               = (int) $user->getId();
        $recently_visited_rows = $this->dao->searchVisitByUserId($user_id, $nb_maximum_artifacts);
        $artifacts_id          = [];
        foreach ($recently_visited_rows as $recently_visited_row) {
            $artifacts_id[] = $recently_visited_row['artifact_id'];
        }

        return $this->artifact_factory->getArtifactsByArtifactIdList($artifacts_id);
    }

    /**
     * @throws \DataAccessException
     * @throws GlyphNotFoundException
     */
    public function getVisitHistory(HistoryEntryCollection $entry_collection, int $max_length_history, \PFUser $user): void
    {
        $user_id               = (int) $entry_collection->getUser()->getId();
        $recently_visited_rows = $this->dao->searchVisitByUserId(
            $user_id,
            $max_length_history
        );

        foreach ($recently_visited_rows as $recently_visited_row) {
            $artifact = $this->artifact_factory->getArtifactById($recently_visited_row['artifact_id']);
            if ($artifact === null) {
                continue;
            }

            $collection = new SwitchToLinksCollection($artifact, $entry_collection->getUser());
            $this->event_manager->dispatch($collection);
            $tracker = $artifact->getTracker();

            $entry_collection->addEntry(
                new HistoryEntry(
                    $recently_visited_row['created_on'],
                    $collection->getXRef(),
                    $collection->getMainUri(),
                    $artifact->getTitle() ?? '',
                    $tracker->getColor()->getName(),
                    self::TYPE,
                    $artifact->getId(),
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
