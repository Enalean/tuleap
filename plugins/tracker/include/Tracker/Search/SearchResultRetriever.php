<?php
/**
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Search;

use Psr\EventDispatcher\EventDispatcherInterface;
use Tuleap\Glyph\GlyphFinder;
use Tuleap\Search\IndexedItemFound;
use Tuleap\Search\IndexedItemFoundToSearchResult;
use Tuleap\Search\SearchResultEntry;
use Tuleap\Search\SearchResultEntryBadge;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Artifact\Changeset\Comment\ChangesetCommentIndexer;
use Tuleap\Tracker\Artifact\RecentlyVisited\SwitchToLinksCollection;
use Tuleap\Tracker\Artifact\StatusBadgeBuilder;
use Tuleap\Tracker\FormElement\FieldContentIndexer;

class SearchResultRetriever
{
    public const TYPE = 'artifact';

    public function __construct(
        private \Tracker_ArtifactFactory $artifact_factory,
        private \Tracker_FormElementFactory $form_element_factory,
        private EventDispatcherInterface $event_dispatcher,
        private GlyphFinder $glyph_finder,
        private StatusBadgeBuilder $status_badge_builder,
    ) {
    }

    public function retrieveSearchResult(IndexedItemFoundToSearchResult $indexed_item_convertor): void
    {
        foreach ($indexed_item_convertor->indexed_items as $priority => $item) {
            $search_result = $this->processIndexedItem($indexed_item_convertor->user, $item);
            if ($search_result !== null) {
                $indexed_item_convertor->addSearchResult($priority, $search_result);
            }
        }
    }

    private function processIndexedItem(\PFUser $user, IndexedItemFound $indexed_item): ?SearchResultEntry
    {
        return match ($indexed_item->type) {
            FieldContentIndexer::INDEX_TYPE_FIELD_CONTENT => $this->processIndexedFieldContentItem(
                $user,
                $indexed_item
            ),
            ChangesetCommentIndexer::INDEX_TYPE_CHANGESET_COMMENT => $this->processChangesetCommentItem(
                $user,
                $indexed_item
            ),
            default => null
        };
    }

    private function processIndexedFieldContentItem(\PFUser $user, IndexedItemFound $indexed_item): ?SearchResultEntry
    {
        $metadata = $indexed_item->metadata;
        if (! isset($metadata['artifact_id'], $metadata['field_id'])) {
            return null;
        }

        $artifact = $this->artifact_factory->getArtifactByIdUserCanView($user, (int) $metadata['artifact_id']);
        if ($artifact === null) {
            return null;
        }

        $field = $this->form_element_factory->getUsedFormElementFieldById((int) $metadata['field_id']);
        if ($field === null || ! $field->userCanRead($user)) {
            return null;
        }

        return $this->buildSearchResultEntry($user, $artifact, $indexed_item->cropped_content);
    }

    private function processChangesetCommentItem(\PFUser $user, IndexedItemFound $indexed_item): ?SearchResultEntry
    {
        $metadata = $indexed_item->metadata;
        if (! isset($metadata['artifact_id'])) {
            return null;
        }

        $artifact = $this->artifact_factory->getArtifactByIdUserCanView($user, (int) $metadata['artifact_id']);
        if ($artifact === null) {
            return null;
        }

        return $this->buildSearchResultEntry($user, $artifact, $indexed_item->cropped_content);
    }

    private function buildSearchResultEntry(
        \PFUser $user,
        Artifact $artifact,
        ?string $cropped_content,
    ): SearchResultEntry {
        $collection = $this->event_dispatcher->dispatch(new SwitchToLinksCollection($artifact, $user));
        $tracker    = $artifact->getTracker();


        return new SearchResultEntry(
            $collection->getXRef(),
            $collection->getMainUri(),
            $artifact->getTitle() ?? '',
            $tracker->getColor()->value,
            self::TYPE,
            $artifact->getId(),
            $this->glyph_finder->get('tuleap-tracker-small'),
            $this->glyph_finder->get('tuleap-tracker'),
            $collection->getIconName(),
            $tracker->getProject(),
            $collection->getQuickLinks(),
            $cropped_content,
            $this->status_badge_builder->buildBadgesFromArtifactStatus(
                $artifact,
                $user,
                static fn(string $label, ?string $color) => new SearchResultEntryBadge($label, $color)
            ),
        );
    }
}
