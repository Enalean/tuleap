<?php

/**
 * Copyright (c) Enalean, 2012. All Rights Reserved.
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

require_once dirname(__FILE__).'/../../include/Planning/PlannifiableItem.class.php';
require_once dirname(__FILE__).'/../../../tracker/tests/builders/aMockArtifact.php';

abstract class Planning_ItemTest extends TuleapTestCase {
    
    /**
     *
     * @var Planning_PlannifiableItem
     */
    protected $item;
    
    public function setUp() {
        parent::setUp();
        $this->edit_uri = 'http://someurl';
        $this->xref     = 'some #xref';
        $this->title    = 'do something interresting';
        $this->id       = '234872';
        $this->artifact = aMockArtifact()->withUri($this->edit_uri)
                                   ->withXRef($this->xref)
                                   ->withTitle($this->title)
                                   ->withId($this->id)
                                   ->build();
        
        
    }
    public function itHasAnUri() {
        $this->assertEqual($this->edit_uri, $this->item->getEditUri());
    }
    
    public function itHasAnXRef() {
        $this->assertEqual($this->xref, $this->item->getXRef());
    }
    
    public function itHasATitle() {
        $this->assertEqual($this->title, $this->item->getTitle());
    }
    
    public function itHasAnId() {
        $this->assertEqual($this->id, $this->item->getId());
    }
}

class Planning_PlannifiableItemTest extends Planning_ItemTest {
    
    public function setUp() {
        parent::setUp();
        $this->item = new Planning_PlannifiableItem($this->artifact);
    }
    
    public function itIsPlannifiable() {
        $this->assertTrue($this->item->isPlannifiable());
    }
    
}

class Planning_BacklogItemTest extends Planning_ItemTest {
    
    public function setUp() {
        parent::setUp();
        $this->item = new Planning_BacklogItem($this->artifact);
    }
    
    public function itIsNotPlannifiable() {
        $this->assertFalse($this->item->isPlannifiable());
    }
    
}
?>
