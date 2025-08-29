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

namespace Tuleap\Tracker\FormElement;

use Psr\EventDispatcher\EventDispatcherInterface;
use Tuleap\Search\ItemToIndexQueue;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\FormElement\Field\TrackerField;

class FieldContentIndexer
{
    public const INDEX_TYPE_FIELD_CONTENT = 'plugin_artifact_field';

    public function __construct(
        private ItemToIndexQueue $index_queue,
        private EventDispatcherInterface $event_dispatcher,
    ) {
    }

    /**
     * @psalm-param \Tuleap\Search\ItemToIndex::CONTENT_TYPE_* $content_type
     */
    public function indexFieldContent(Artifact $artifact, TrackerField $field, string $value, string $content_type): void
    {
        $this->index_queue->addItemToQueue(
            new \Tuleap\Search\ItemToIndex(
                self::INDEX_TYPE_FIELD_CONTENT,
                (int) $field->getTracker()->getGroupId(),
                $value,
                $content_type,
                [
                    'field_id'    => (string) $field->getId(),
                    'artifact_id' => (string) $artifact->getId(),
                    'tracker_id'  => (string) $field->getTrackerId(),
                ]
            )
        );
    }

    public function askForDeletionOfIndexedFieldsFromArtifact(Artifact $artifact): void
    {
        $this->event_dispatcher->dispatch(
            new \Tuleap\Search\IndexedItemsToRemove(
                self::INDEX_TYPE_FIELD_CONTENT,
                [
                    'artifact_id'  => (string) $artifact->getId(),
                ]
            )
        );
    }
}
