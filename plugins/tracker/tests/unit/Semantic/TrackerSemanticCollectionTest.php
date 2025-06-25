<?php
/**
 * Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Semantic;

use PHPUnit\Framework\MockObject\MockObject;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class TrackerSemanticCollectionTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private TrackerSemantic&MockObject $status_semantic;
    private TrackerSemantic&MockObject $title;
    private TrackerSemantic&MockObject $done;

    public function setUp(): void
    {
        parent::setUp();
        $this->status_semantic = $this->createMock(\Tuleap\Tracker\Semantic\TrackerSemantic::class);
        $this->status_semantic->method('getShortName')->willReturn('status');

        $this->title = $this->createMock(\Tuleap\Tracker\Semantic\TrackerSemantic::class);
        $this->title->method('getShortName')->willReturn('title');

        $this->done = $this->createMock(\Tuleap\Tracker\Semantic\TrackerSemantic::class);
        $this->done->method('getShortName')->willReturn('done');
    }

    public function testItAppendsSemanticAtTheEndOfTheCollection(): void
    {
        $collection = new TrackerSemanticCollection();
        $collection->add($this->status_semantic);
        $collection->add($this->title);

        $this->assertSemanticsCollectionIsIdenticalTo($collection, [$this->status_semantic, $this->title]);
    }

    public function testItInsertSemanticAfterAnotherOne(): void
    {
        $collection = new TrackerSemanticCollection();
        $collection->add($this->status_semantic);
        $collection->add($this->title);
        $collection->insertAfter('status', $this->done);

        $this->assertSemanticsCollectionIsIdenticalTo($collection, [$this->status_semantic, $this->done, $this->title]);
    }

    public function testItInsertSemanticAtTheBeginningWhenItemIsNotFound(): void
    {
        $collection = new TrackerSemanticCollection();
        $collection->add($this->status_semantic);
        $collection->add($this->title);
        $collection->insertAfter('unknown', $this->done);

        $this->assertSemanticsCollectionIsIdenticalTo($collection, [$this->done, $this->status_semantic, $this->title]);
    }

    public function testItRetrievesSemanticByItsShortName(): void
    {
        $collection = new TrackerSemanticCollection();
        $collection->add($this->status_semantic);
        $collection->insertAfter('status', $this->title);

        $this->assertEquals($this->status_semantic, $collection['status']);
        $this->assertEquals($this->title, $collection['title']);
    }

    public function testItRemovesASemanticFromCollection(): void
    {
        $collection = new TrackerSemanticCollection();
        $collection->add($this->status_semantic);
        $collection->add($this->title);

        unset($collection['status']);

        $this->assertSemanticsCollectionIsIdenticalTo($collection, [$this->title]);
        $this->assertFalse(isset($collection['status']));
    }

    private function assertSemanticsCollectionIsIdenticalTo(TrackerSemanticCollection $collection, array $expected): void
    {
        $index = 0;
        foreach ($collection as $semantic) {
            $this->assertEquals($expected[$index++], $semantic);
        }
    }
}
