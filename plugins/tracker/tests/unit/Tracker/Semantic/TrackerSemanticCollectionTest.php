<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tracker_Semantic;
use Tracker_SemanticCollection;

class TrackerSemanticCollectionTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var Tracker_Semantic|\Mockery\MockInterface|Tracker_Semantic
     */
    private $status_semantic;
    /**
     * @var Tracker_Semantic|\Mockery\MockInterface|Tracker_Semantic
     */
    private $title;

    /**
     * @var Tracker_Semantic|\Mockery\MockInterface|Tracker_Semantic
     */
    private $done;

    public function setUp(): void
    {
        parent::setUp();
        $this->status_semantic = Mockery::mock(\Tracker_Semantic::class);
        $this->status_semantic->shouldReceive('getShortName')->andReturn('status');

        $this->title  = Mockery::mock(\Tracker_Semantic::class);
        $this->title->shouldReceive('getShortName')->andReturn('title');

        $this->done  = Mockery::mock(\Tracker_Semantic::class);
        $this->done->shouldReceive('getShortName')->andReturn('done');
    }

    public function testItAppendsSemanticAtTheEndOfTheCollection()
    {
        $collection = new Tracker_SemanticCollection();
        $collection->add($this->status_semantic);
        $collection->add($this->title);

        $this->assertSemanticsCollectionIsIdenticalTo($collection, [$this->status_semantic, $this->title]);
    }

    public function testItInsertSemanticAfterAnotherOne()
    {
        $collection = new Tracker_SemanticCollection();
        $collection->add($this->status_semantic);
        $collection->add($this->title);
        $collection->insertAfter('status', $this->done);

        $this->assertSemanticsCollectionIsIdenticalTo($collection, [$this->status_semantic, $this->done, $this->title]);
    }

    public function testItInsertSemanticAtTheBeginningWhenItemIsNotFound()
    {
        $collection = new Tracker_SemanticCollection();
        $collection->add($this->status_semantic);
        $collection->add($this->title);
        $collection->insertAfter('unknown', $this->done);

        $this->assertSemanticsCollectionIsIdenticalTo($collection, [$this->done, $this->status_semantic, $this->title]);
    }

    public function testItRetrievesSemanticByItsShortName()
    {
        $collection = new Tracker_SemanticCollection();
        $collection->add($this->status_semantic);
        $collection->insertAfter('status', $this->title);

        $this->assertEquals($this->status_semantic, $collection['status']);
        $this->assertEquals($this->title, $collection['title']);
    }

    public function testItRemovesASemanticFromCollection()
    {
        $collection = new Tracker_SemanticCollection();
        $collection->add($this->status_semantic);
        $collection->add($this->title);

        unset($collection['status']);

        $this->assertSemanticsCollectionIsIdenticalTo($collection, [$this->title]);
        $this->assertFalse(isset($collection['status']));
    }

    private function assertSemanticsCollectionIsIdenticalTo(Tracker_SemanticCollection $collection, array $expected)
    {
        $index = 0;
        foreach ($collection as $semantic) {
            $this->assertEquals($expected[$index++], $semantic);
        }
    }
}
