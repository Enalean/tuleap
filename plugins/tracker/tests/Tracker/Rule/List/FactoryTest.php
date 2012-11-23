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
require_once dirname(__FILE__).'/../../../../include/Tracker/Rule/List/List.class.php';
require_once dirname(__FILE__).'/../../../../include/Tracker/Rule/List/Dao.class.php';
require_once dirname(__FILE__).'/../../../../include/Tracker/Rule/List/Factory.class.php';

class Tracker_Rule_List_FactoryTest extends TuleapTestCase {
    
    /**
     * @var Tracker_Rule_List_Dao
     */
    protected $list_rule_dao;
    
    /**
     *
     * @var Tracker_Rule_List_Factory 
     */
    protected $list_rule_factory;
    
    public function setUp() {
        parent::setUp();
        
        $this->list_rule_dao = mock('Tracker_Rule_List_Dao');
        $this->list_rule_factory = new Tracker_Rule_List_Factory($this->list_rule_dao);
    }

    public function testCreateRuleListGeneratesANewObjectThatContainsAllValuesPassed() {
        stub($this->list_rule_dao)->insert()->returns(true);
        
        $source_field_id = 10;
        $target_field_id = 11;
        $tracker_id      = 405;
        $source_value    = 101;
        $target_value    = 102;
        
        $list_rule = $this->list_rule_factory
                ->create($source_field_id, $target_field_id, $tracker_id, $source_value, $target_value);
        
        $this->assertIsA($list_rule, 'Tracker_Rule_List');
        $this->assertEqual($list_rule->getTrackerId(), $tracker_id);
        $this->assertEqual($list_rule->getTargetFieldId(), $target_field_id);
        $this->assertEqual($list_rule->getSourceFieldId(), $source_field_id);
        $this->assertEqual($list_rule->getSourceValue(), $source_value);
        $this->assertEqual($list_rule->getTargetValue(), $target_value);
    }
    
    public function testSearchByIdReturnsNullIfNoEntryIsFoundByTheDao() {
        stub($this->list_rule_dao)->searchById()->returns(false);
        $list_rule = $this->list_rule_factory
                ->searchById(999);
        
        $this->assertNull($list_rule);
    }
    
    public function testSearchByIdReturnsANewObjectIfOneEntryIsFoundByTheDao() {
        $data = array(
            'source_field_id'   => 46345,
            'target_field_id'   => 465,
            'tracker_id'        => 5458,
            'source_value_id'   => '46345gfv',
            'target_value_id'   => '465',
        );
        
        stub($this->list_rule_dao)->searchById()->returns($data);
        $list_rule = $this->list_rule_factory
                ->searchById(999);
        
        $this->assertNotNull($list_rule);
    }
    
    public function testSearchByTrackerIdReturnsNullIfNoEntryIsFoundByTheDao() {
        stub($this->list_rule_dao)->searchByTrackerId()->returnsEmptyDar();
        $list_rule = $this->list_rule_factory
                ->searchByTrackerId(999);
        
        $this->assertTrue(is_array($list_rule));
        $this->assertCount($list_rule, 0);
    }
    
    public function testSearchByTrackerIdReturnsAnArrayOfASingleObjectIfOneEntryIsFoundByTheDao() {
        $data_access_result = mock('DataAccessResult');
                
        $data = array(
            'source_field_id'   => 46345,
            'target_field_id'   => 465,
            'tracker_id'        => 5458,
            'source_value_id'   => '46345gfv',
            'target_value_id'   => '465',
        );
        
        stub($data_access_result)->rowCount()->returns(1);
        stub($data_access_result)->getRow()->at(1)->returns($data);
        stub($data_access_result)->getRow()->at(2)->returns(false);
        
        stub($this->list_rule_dao)->searchByTrackerId()->returnsDar($data);
        $list_rules = $this->list_rule_factory
                ->searchByTrackerId(999);
        
        $this->assertNotNull($list_rules);
        $this->assertIsA($list_rules, 'array');
        $this->assertCount($list_rules, 1);
    }
    
} 
?>
