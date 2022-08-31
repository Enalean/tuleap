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

use Tracker;
use Tracker_FormElement_Field;
use Tuleap\Search\ItemToIndex;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Test\Stub\EventDispatcherStub;
use Tuleap\Tracker\Artifact\Artifact;

final class FieldContentIndexerTest extends TestCase
{
    public function testIndexesFieldContent(): void
    {
        $event_dispatcher = EventDispatcherStub::withCallback(
            static function (ItemToIndex $item): ItemToIndex {
                self::assertEquals(
                    new ItemToIndex(
                        'plugin_artifact_field',
                        'value',
                        [
                            'field_id'    => '1',
                            'artifact_id' => '2',
                            'tracker_id'  => '3',
                            'project_id'  => '4',
                        ]
                    ),
                    $item
                );
                return $item;
            }
        );

        $indexer = new FieldContentIndexer($event_dispatcher);

        $field = $this->createStub(Tracker_FormElement_Field::class);
        $field->method('getId')->willReturn(1);
        $field->method('getTrackerId')->willReturn(3);
        $tracker = $this->createStub(Tracker::class);
        $tracker->method('getGroupId')->willReturn(4);
        $field->method('getTracker')->willReturn($tracker);
        $indexer->indexFieldContent(
            new Artifact(2, 3, 0, 0, true),
            $field,
            'value'
        );

        self::assertEquals(1, $event_dispatcher->getCallCount());
    }
}
