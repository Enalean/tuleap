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

use Tuleap\Search\ItemToIndexQueue;
use Tuleap\Search\ProgressQueueIndexItemCategory;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Artifact\Changeset\Comment\ChangesetCommentIndexer;

class IndexAllArtifactsProcessor
{
    /**
     * @psalm-param callable():\Tracker_ArtifactFactory $artifact_factory_builder
     */
    public function __construct(
        private IndexArtifactDAO $artifact_dao,
        private $artifact_factory_builder,
        private ItemToIndexQueue $index_queue,
        private ChangesetCommentIndexer $changeset_comment_indexer,
    ) {
    }

    public function queueAllPendingArtifactsIntoIndexQueue(ProgressQueueIndexItemCategory $progress_queue_index_item_category): void
    {
        $seen_since_last_clear_cache = 0;
        $artifact_factory            = ($this->artifact_factory_builder)();
        $rows                        = $this->artifact_dao->searchAllPendingArtifactsToIndex();
        $progress_queue_index_item_category->start(count($rows));
        foreach ($rows as $row) {
            $artifact_id = $row['id'];
            $artifact    = $artifact_factory->getArtifactById($artifact_id);
            if ($artifact !== null) {
                $this->queueArtifactInformationIntoIndex($artifact);
            }
            $this->artifact_dao->markPendingArtifactAsProcessed($artifact_id);
            $progress_queue_index_item_category->advance();
            $seen_since_last_clear_cache++;
            if ($seen_since_last_clear_cache > 100) {
                $artifact_factory            = ($this->artifact_factory_builder)();
                $seen_since_last_clear_cache = 0;
            }
        }
        $progress_queue_index_item_category->done();
    }

    private function queueArtifactInformationIntoIndex(Artifact $artifact): void
    {
        $tracker        = $artifact->getTracker();
        $fields         = $tracker->getFormElementFields();
        $last_changeset = $artifact->getLastChangeset();
        if ($last_changeset === null) {
            return;
        }

        foreach ($fields as $field) {
            $changeset_value = $last_changeset->getValue($field);
            if ($changeset_value === null) {
                continue;
            }

            $field->addChangesetValueToSearchIndex($this->index_queue, $changeset_value);
        }

        foreach ($artifact->getChangesets() as $changeset) {
            $this->changeset_comment_indexer->indexChangesetCommentFromChangeset($changeset);
        }
    }
}
