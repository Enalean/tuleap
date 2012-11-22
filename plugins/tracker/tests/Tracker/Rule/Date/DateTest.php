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
require_once dirname(__FILE__).'/../../../builders/all.php';
require_once dirname(__FILE__).'/../../../../include/constants.php';
require_once dirname(__FILE__).'/../../../../include/Tracker/Rule/Date/Date.class.php';
require_once dirname(__FILE__).'/../../../../include/Tracker/FormElement/Tracker_FormElement_Field_Date.class.php';

class Tracker_Rule_Date_DateTest extends TuleapTestCase {

    /**
     *
     * @var Tracker_Rule_Date
     */
   protected $date_rule;



   public function setUp() {
        parent::setUp();
        $this->date_rule = new Tracker_Rule_Date();
    }

    /*
     * Source Field tests
     */
    public function testSetSourceFieldIdReturnsModelObject() {
        $set = $this->date_rule->setSourceFieldId(123);
        $this->assertEqual($this->date_rule, $set);
    }

    public function testGetSourceFieldIdReturnsFieldIdSet() {
        $this->date_rule->setSourceFieldId(45);
        $this->assertEqual(45, $this->date_rule->getSourceFieldId());
    }

    /*
     * Target Field tests
     */
    public function testSetTargetFieldIdReturnsModelObject() {
        $set = $this->date_rule->setSourceFieldId(123);
        $this->assertEqual($this->date_rule, $set);
    }

    public function testGetTargetFieldIdReturnsTargetIdSet() {
        $this->date_rule->setTargetFieldId(45);
        $this->assertEqual(45, $this->date_rule->getTargetFieldId());
    }

    /*
     * Tracker Field tests
     */
    public function testSetTrackerFieldIdReturnsModelObject() {
        $set = $this->date_rule->setTrackerId(123);
        $this->assertEqual($this->date_rule, $set);
    }

    public function testGetTrackerFieldIdReturnsTrackerIdSet() {
        $this->date_rule->setTrackerId(45);
        $this->assertEqual(45, $this->date_rule->getTrackerId());
    }





}
?>
