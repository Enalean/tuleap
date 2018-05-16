<?php
/**
  * Copyright (c) Enalean, 2012. All rights reserved
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
  * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
  * GNU General Public License for more details.
  *
  * You should have received a copy of the GNU General Public License
  * along with Tuleap. If not, see <http://www.gnu.org/licenses/
  */
require_once __DIR__.'/../../../bootstrap.php';
class Tracker_Rule_List_ListTest extends TuleapTestCase {

    /**
     *
     * @var Tracker_Rule_List
     */
   protected $list_rule;



   public function setUp() {
        parent::setUp();
        $this->list_rule = new Tracker_Rule_List();
    }

    /*
     * Source Field tests
     */
    public function testSetSourceFieldIdReturnsModelObject() {
        $set = $this->list_rule->setSourceFieldId(123);
        $this->assertEqual($this->list_rule, $set);
    }

    public function testGetSourceFieldIdReturnsFieldIdSet() {
        $this->list_rule->setSourceFieldId(45);
        $this->assertEqual(45, $this->list_rule->getSourceFieldId());
    }

    /*
     * Target Field tests
     */
    public function testSetTargetFieldIdReturnsModelObject() {
        $set = $this->list_rule->setSourceFieldId(123);
        $this->assertEqual($this->list_rule, $set);
    }

    public function testGetTargetFieldIdReturnsTargetIdSet() {
        $this->list_rule->setTargetFieldId(45);
        $this->assertEqual(45, $this->list_rule->getTargetFieldId());
    }

    /*
     * Tracker Field tests
     */
    public function testSetTrackerFieldIdReturnsModelObject() {
        $set = $this->list_rule->setTrackerId(123);
        $this->assertEqual($this->list_rule, $set);
    }

    public function testGetTrackerFieldIdReturnsTrackerIdSet() {
        $this->list_rule->setTrackerId(45);
        $this->assertEqual(45, $this->list_rule->getTrackerId());
    }

    /*
     * Source Field value tests
     */
    public function testSetSourceValueReturnsModelObject() {
        $set = $this->list_rule->setSourceValue(123);
        $this->assertEqual($this->list_rule, $set);
    }

    public function testGetSourceValueReturnsFieldIdSet() {
        $this->list_rule->setSourceValue(45);
        $this->assertEqual(45, $this->list_rule->getSourceValue());
    }

    /*
     * Target Field value tests
     */
    public function testSetTargetValueReturnsModelObject() {
        $set = $this->list_rule->setSourceValue(123);
        $this->assertEqual($this->list_rule, $set);
    }

    public function testGetTargetValueReturnsTargetIdSet() {
        $this->list_rule->setTargetValue(45);
        $this->assertEqual(45, $this->list_rule->getTargetValue());
    }
}
?>
