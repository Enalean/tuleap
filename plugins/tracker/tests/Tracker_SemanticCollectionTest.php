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

require_once 'bootstrap.php';

class Tracker_SemanticCollectionTest extends TuleapTestCase
{
    public function itAppendsSemanticAtTheEndOfTheCollection()
    {
        $status = stub('Tracker_Semantic')->getShortName()->returns('status');
        $title  = stub('Tracker_Semantic')->getShortName()->returns('title');

        $collection = new Tracker_SemanticCollection();
        $collection->add($status);
        $collection->add($title);

        $this->assertSemanticsCollectionIsIdenticalTo($collection, array($status, $title));
    }

    public function itInsertSemanticAfterAnotherOne()
    {
        $status = stub('Tracker_Semantic')->getShortName()->returns('status');
        $title  = stub('Tracker_Semantic')->getShortName()->returns('title');
        $done   = stub('Tracker_Semantic')->getShortName()->returns('done');

        $collection = new Tracker_SemanticCollection();
        $collection->add($status);
        $collection->add($title);
        $collection->insertAfter('status', $done);

        $this->assertSemanticsCollectionIsIdenticalTo($collection, array($status, $done, $title));
    }

    public function itInsertSemanticAtTheBeginningWhenItemIsNotFound()
    {

        $status = stub('Tracker_Semantic')->getShortName()->returns('status');
        $title  = stub('Tracker_Semantic')->getShortName()->returns('title');
        $done   = stub('Tracker_Semantic')->getShortName()->returns('done');

        $collection = new Tracker_SemanticCollection();
        $collection->add($status);
        $collection->add($title);
        $collection->insertAfter('unknown', $done);

        $this->assertSemanticsCollectionIsIdenticalTo($collection, array($done, $status, $title));
    }

    public function itRetrievesSemanticByItsShortName()
    {
        $status = stub('Tracker_Semantic')->getShortName()->returns('status');
        $title  = stub('Tracker_Semantic')->getShortName()->returns('title');

        $collection = new Tracker_SemanticCollection();
        $collection->add($status);
        $collection->insertAfter('status', $title);

        $this->assertEqual($collection['status'], $status);
        $this->assertEqual($collection['title'], $title);
    }

    public function itRemovesASemanticFromCollection()
    {
        $status = stub('Tracker_Semantic')->getShortName()->returns('status');
        $title  = stub('Tracker_Semantic')->getShortName()->returns('title');

        $collection = new Tracker_SemanticCollection();
        $collection->add($status);
        $collection->add($title);

        unset($collection['status']);

        $this->assertSemanticsCollectionIsIdenticalTo($collection, array($title));
        $this->assertFalse(isset($collection['status']));
    }

    private function assertSemanticsCollectionIsIdenticalTo(Tracker_SemanticCollection $collection, array $expected)
    {
        $index = 0;
        foreach ($collection as $semantic) {
            $this->assertEqual($semantic, $expected[$index++]);
        }
    }
}
