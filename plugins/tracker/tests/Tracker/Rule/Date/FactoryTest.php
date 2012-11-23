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
require_once dirname(__FILE__).'/../../../../include/Tracker/Rule/Date/Dao.class.php';
require_once dirname(__FILE__).'/../../../../include/Tracker/Rule/Date/Factory.class.php';

class Tracker_Rule_Date_FactoryTest extends TuleapTestCase {
    
    /**
     * @var Tracker_Rule_Date_Dao
     */
    protected $date_rule_dao;
    
    /**
     *
     * @var Tracker_Rule_Date_Factory 
     */
    protected $date_rule_factory;
    
    public function setUp() {
        parent::setUp();
        
        $this->date_rule_dao = mock('Tracker_Rule_Date_Dao');
        $this->date_rule_factory = new Tracker_Rule_Date_Factory($this->date_rule_dao);
    }

    public function testCreateRuleDateGeneratesANewObjectThatContainsAllValuesPassed() {
        stub($this->date_rule_dao)->insert()->returns(true);
        
        $source_field_id = 10;
        $target_field_id = 11;
        $tracker_id      = 405;
        $comparator      = Tracker_Rule_Date::COMPARATOR_GREATER_THAN;
        
        $date_rule = $this->date_rule_factory
                ->create($source_field_id, $target_field_id, $tracker_id, $comparator);
        
        $this->assertIsA($date_rule, 'Tracker_Rule_Date');
        $this->assertEqual($date_rule->getTrackerId(), $tracker_id);
        $this->assertEqual($date_rule->getTargetFieldId(), $target_field_id);
        $this->assertEqual($date_rule->getSourceFieldId(), $source_field_id);
        $this->assertEqual($date_rule->getComparator(), $comparator);
    }
    
    public function testSearchByIdReturnsNullIfNoEntryIsFoundByTheDao() {
        stub($this->date_rule_dao)->searchById()->returns(false);
        $date_rule = $this->date_rule_factory
                ->searchById(999);
        
        $this->assertNull($date_rule);
    }
    
    public function testSearchByIdReturnsANewObjectIfOneEntryIsFoundByTheDao() {
        $data = array(
            'comparator'        => Tracker_Rule_Date::COMPARATOR_LESS_THAN_OR_EQUALS,
            'source_field_id'   => 46345,
            'target_field_id'   => 465,
            'tracker_id'        => 5458,
        );
        
        stub($this->date_rule_dao)->searchById()->returns($data);
        $date_rule = $this->date_rule_factory
                ->searchById(999);
        
        $this->assertNotNull($date_rule);
    }
    
    public function testSearchByTrackerIdReturnsNullIfNoEntryIsFoundByTheDao() {
        stub($this->date_rule_dao)->searchByTrackerId()->returnsEmptyDar();
        $date_rule = $this->date_rule_factory
                ->searchByTrackerId(999);
        
        $this->assertTrue(is_array($date_rule));
        $this->assertCount($date_rule, 0);
    }
    
    public function testSearchByTrackerIdReturnsAnArrayOfASingleObjectIfOneEntryIsFoundByTheDao() {
        $data_access_result = mock('DataAccessResult');
                
        $data = array(
            'comparator'        => Tracker_Rule_Date::COMPARATOR_LESS_THAN_OR_EQUALS,
            'source_field_id'   => 46345,
            'target_field_id'   => 465,
            'tracker_id'        => 5458,
        );

        stub($data_access_result)->rowCount()->returns(1);
        stub($data_access_result)->getRow()->at(1)->returns($data);
        stub($data_access_result)->getRow()->at(2)->returns(false);
        
        stub($this->date_rule_dao)->searchByTrackerId()->returnsDar($data);
        $date_rules = $this->date_rule_factory
                ->searchByTrackerId(999);
        
        $this->assertNotNull($date_rules);
        $this->assertIsA($date_rules, 'array');
        $this->assertCount($date_rules, 1);
    }
    
} 
?>
