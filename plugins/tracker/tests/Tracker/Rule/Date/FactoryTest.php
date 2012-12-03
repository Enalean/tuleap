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
require_once dirname(__FILE__).'/../../../../include/Tracker/Rule/Date.class.php';
require_once dirname(__FILE__).'/../../../../include/Tracker/Rule/Date/Dao.class.php';
require_once dirname(__FILE__).'/../../../../include/Tracker/Rule/Date/Factory.class.php';

class Tracker_Rule_Date_FactoryTest extends TuleapTestCase {

    protected $source_field_id = 46345;

    /** @var Tracker_Rule_Date_Dao */
    protected $date_rule_dao;

    /** @var Tracker_Rule_Date_Factory */
    protected $date_rule_factory;

    /** @var Tracker_FormElementFactory */
    protected $element_factory;

    public function setUp() {
        parent::setUp();

        $this->date_rule_dao = mock('Tracker_Rule_Date_Dao');
        $this->source_field = mock('Tracker_FormElement_Field_Date');
        stub($this->source_field)->getId()->returns($this->source_field_id);

        $this->target_field = mock('Tracker_FormElement_Field_Date');
        stub($this->target_field)->getId()->returns(465);

        $this->element_factory = mock('Tracker_FormElementFactory');
        stub($this->element_factory)->getFormElementById($this->source_field_id)->returns($this->source_field);
        stub($this->element_factory)->getFormElementById(465)->returns($this->target_field);

        $this->date_rule_factory = new Tracker_Rule_Date_Factory($this->date_rule_dao, $this->element_factory);
    }

    public function testCreateRuleDateGeneratesANewObjectThatContainsAllValuesPassed() {
        stub($this->date_rule_dao)->insert()->returns(20);

        $source_field_id = $this->source_field_id;
        $target_field_id = 465;
        $tracker_id      = 999;
        $comparator      = Tracker_Rule_Date::COMPARATOR_GREATER_THAN;

        $date_rule = $this->date_rule_factory
                ->create($source_field_id, $target_field_id, $tracker_id, $comparator);

        $this->assertIsA($date_rule, 'Tracker_Rule_Date');
        $this->assertEqual($date_rule->getTrackerId(), $tracker_id);
        $this->assertEqual($date_rule->getTargetFieldId(), $target_field_id);
        $this->assertEqual($date_rule->getSourceFieldId(), $source_field_id);
        $this->assertEqual($date_rule->getComparator(), $comparator);
        $this->assertEqual($date_rule->getId(), 20);
    }

    public function testSearchByIdReturnsNullIfNoEntryIsFoundByTheDao() {
        stub($this->date_rule_dao)->searchById()->returnsEmptyDar();
        $date_rule = $this->date_rule_factory
                ->searchById(20);

        $this->assertNull($date_rule);
    }

    public function testSearchByIdReturnsANewObjectIfOneEntryIsFoundByTheDao() {
        $data = array(
            'id'                => 20,
            'comparator'        => Tracker_Rule_Date::COMPARATOR_LESS_THAN_OR_EQUALS,
            'source_field_id'   => $this->source_field_id,
            'target_field_id'   => 465,
            'tracker_id'        => 999,
        );

        stub($this->date_rule_dao)->searchById(20)->returnsDar($data);
        $date_rule = $this->date_rule_factory
                ->searchById(20);

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
        $data = array(
            'comparator'        => Tracker_Rule_Date::COMPARATOR_LESS_THAN_OR_EQUALS,
            'source_field_id'   => $this->source_field_id,
            'target_field_id'   => 465,
            'tracker_id'        => 999,
            'id'                => 20
        );

        stub($this->date_rule_dao)->searchByTrackerId()->returnsDar($data);
        $date_rules = $this->date_rule_factory
                ->searchByTrackerId(999);

        $this->assertNotNull($date_rules);
        $this->assertIsA($date_rules, 'array');
        $this->assertCount($date_rules, 1);

        $rule = $date_rules[0];
        $obtained_source_field = $rule->getSourceField();
        $obtained_target_field = $rule->getTargetField();

        $this->assertEqual($obtained_source_field, $this->source_field);
        $this->assertEqual($obtained_target_field, $this->target_field);
        $this->assertEqual($rule->getId(), 20);

    }

    public function itDelegatesDeletionToDao() {
        $tracker_id = '123';
        $rule_id    = '456';
        expect($this->date_rule_dao)->deleteById($tracker_id, $rule_id)->once();
        $this->date_rule_factory->deleteById($tracker_id, $rule_id);
    }

    public function itDelegatesUsedDateFieldsRetrievalToElementFactory() {
        $tracker          = mock('Tracker');
        $used_date_fields = array('of', 'fields');
        expect($this->element_factory)->getUsedDateFields($tracker)->once()->returns($used_date_fields);
        $this->assertEqual($used_date_fields, $this->date_rule_factory->getUsedDateFields($tracker));
    }

    public function itDelegatesUsedDateFieldByIdRetrievalToElementFactory() {
        $tracker = mock('Tracker');
        expect($this->element_factory)->getUsedDateFieldById($tracker, $this->source_field_id)->once()->returns($this->source_field);
        $this->assertEqual($this->source_field, $this->date_rule_factory->getUsedDateFieldById($tracker, $this->source_field_id));
    }
}
?>
