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

use Tuleap\Search\IndexedItemsToRemove;
use Tuleap\Search\ItemToIndex;
use Tuleap\Search\ItemToIndexQueueStub;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Test\Stubs\EventDispatcherStub;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\FormElement\Field\TrackerField;
use Tuleap\Tracker\Tracker;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class FieldContentIndexerTest extends TestCase
{
    public function testIndexesFieldContent(): void
    {
        $has_been_called = false;
        $callback        = static function (ItemToIndex $item) use (&$has_been_called): void {
            $has_been_called = true;
            self::assertEquals(
                new ItemToIndex(
                    'plugin_artifact_field',
                    4,
                    'value',
                    'plaintext',
                    [
                        'field_id'    => '1',
                        'artifact_id' => '2',
                        'tracker_id'  => '3',
                    ]
                ),
                $item
            );
        };

        $indexer = new FieldContentIndexer(ItemToIndexQueueStub::withCallback($callback), EventDispatcherStub::withIdentityCallback());

        $field = $this->createStub(TrackerField::class);
        $field->method('getId')->willReturn(1);
        $field->method('getTrackerId')->willReturn(3);
        $tracker = $this->createStub(Tracker::class);
        $tracker->method('getGroupId')->willReturn(4);
        $field->method('getTracker')->willReturn($tracker);
        $indexer->indexFieldContent(
            new Artifact(2, 3, 0, 0, true),
            $field,
            'value',
            ItemToIndex::CONTENT_TYPE_PLAINTEXT,
        );

        self::assertTrue($has_been_called);
    }

    public function testAskForDeletionFromAnArtifact(): void
    {
        $event_dispatcher = EventDispatcherStub::withCallback(
            static function (IndexedItemsToRemove $items_to_remove): IndexedItemsToRemove {
                self::assertEquals(
                    new IndexedItemsToRemove(
                        'plugin_artifact_field',
                        [
                            'artifact_id'  => '77',
                        ]
                    ),
                    $items_to_remove
                );
                return $items_to_remove;
            }
        );

        $indexer = new FieldContentIndexer(ItemToIndexQueueStub::noop(), $event_dispatcher);

        $indexer->askForDeletionOfIndexedFieldsFromArtifact(new Artifact(77, 3, 0, 0, true));
    }
}
