<?php
/**
 * Copyright (c) Enalean, 2014. All rights reserved
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
require_once __DIR__.'/../../bootstrap.php';

class TestTracker_Report_REST extends Tracker_Report_REST
{
    public $rest_criteria = array();
}

class Tracker_Report_RESTTest_setRESTCriteria extends TuleapTestCase
{

    public function setUp()
    {
        $current_user        = mock('PFUser');
        $tracker             = mock('Tracker');
        $permissions_manager = mock('PermissionsManager');
        $dao                 = mock('Tracker_ReportDao');
        $formelement_factory = mock('Tracker_FormElementFactory');

        $this->report = new TestTracker_Report_REST($current_user, $tracker, $permissions_manager, $dao, $formelement_factory);
    }

    public function itThrowsAnExceptionForBadJSON()
    {
        $this->expectException("Tracker_Report_InvalidRESTCriterionException");

        $this->report->setRESTCriteria("{fvf");
    }

    public function itThrowsAnExceptionForAnInvalidQueryWithMissingOperator()
    {
        $this->expectException("Tracker_Report_InvalidRESTCriterionException");

        $query = new ArrayObject(array(
            "my_field" => new ArrayObject(array(
                Tracker_Report_REST::VALUE_PROPERTY_NAME => "true"
            ))
        ));

        $this->report->setRESTCriteria(json_encode($query));
    }

    public function itThrowsAnExceptionForAnInvalidQueryWithMissingValue()
    {
        $this->expectException("Tracker_Report_InvalidRESTCriterionException");

        $query = new ArrayObject(array(
            "my_field" => new ArrayObject(array(
                Tracker_Report_REST::OPERATOR_PROPERTY_NAME => Tracker_Report_REST::DEFAULT_OPERATOR
            ))
        ));

        $this->report->setRESTCriteria(json_encode($query));
    }

    public function itThrowsAnExceptionForAnInvalidQueryWithInvlidOperator()
    {
        $this->expectException("Tracker_Report_InvalidRESTCriterionException");

        $query = new ArrayObject(array(
            "my_field" => new ArrayObject(array(
                Tracker_Report_REST::VALUE_PROPERTY_NAME    => "true",
                Tracker_Report_REST::OPERATOR_PROPERTY_NAME => Tracker_Report_REST::DEFAULT_OPERATOR . 'xxx'
            ))
        ));

        $this->report->setRESTCriteria(json_encode($query));
    }

    public function itTransformsBasicCriteriaToTheCorrectFormat()
    {
        $query = new ArrayObject(array(
            "my_field" => new ArrayObject(array(
                Tracker_Report_REST::VALUE_PROPERTY_NAME    => "true",
                Tracker_Report_REST::OPERATOR_PROPERTY_NAME => Tracker_Report_REST::DEFAULT_OPERATOR
            )),
            "my_other_field" => "bla"
        ));

        $this->report->setRESTCriteria(json_encode($query));

        $this->assertCount($this->report->rest_criteria, 2);
    }
}

class Tracker_Report_RESTTest_getCriteria extends TuleapTestCase
{

    /**
     * @var Tracker_FormElementFactory
     */
    private $formelement_factory;

    /**
     * @var Tracker
     */
    private $tracker;

    public function setUp()
    {
        $current_user        = mock('PFUser');
        $permissions_manager = mock('PermissionsManager');
        $dao                 = mock('Tracker_ReportDao');
        $this->tracker             = mock('Tracker');
        $this->formelement_factory = mock('Tracker_FormElementFactory');

        $this->tracker_id = 444;
        stub($this->tracker)->getId()->returns($this->tracker_id);

        $this->report = new Tracker_Report_REST($current_user, $this->tracker, $permissions_manager, $dao, $this->formelement_factory);

        $query = new ArrayObject(array(
            "my_field"       => new ArrayObject(array(
                Tracker_Report_REST::VALUE_PROPERTY_NAME    => "true",
                Tracker_Report_REST::OPERATOR_PROPERTY_NAME => Tracker_Report_REST::DEFAULT_OPERATOR
            )),
            "my_other_field" => "bla",
            "137"            => "my_value"
        ));

        $this->report->setRESTCriteria(json_encode($query));
    }

    public function itChoosesTheFormElementIdOverTheShortName()
    {
        stub($this->formelement_factory)->getFormElementById('my_field')->returns(null);
        stub($this->formelement_factory)->getFormElementById('my_other_field')->returns(null);
        stub($this->formelement_factory)->getFormElementById(137)->returns(mock('Tracker_FormElement_Field_Integer'));
        stub($this->formelement_factory)->getFormElementByName()->count(2);

        $this->report->getCriteria();
    }

    public function itFetchesByNameIfTheFormElementByIdDoesNotExist()
    {
        stub($this->formelement_factory)->getFormElementById(137)->returns(null);
        stub($this->formelement_factory)->getFormElementByName(137)->count(3);

        $this->report->getCriteria();
    }

    public function itOnlyAddsCrieriaOnFieldsUserCanSee()
    {
        $field_1 = mock('Tracker_FormElement_Field_Integer');
        $field_2 = mock('Tracker_FormElement_Field_Integer');
        $field_3 = mock('Tracker_FormElement_Field_Integer');

        stub($this->formelement_factory)->getFormElementById('my_field')->returns($field_1);
        stub($this->formelement_factory)->getFormElementById('my_other_field')->returns($field_2);
        stub($this->formelement_factory)->getFormElementById(137)->returns($field_3);

        stub($field_1)->userCanRead()->returns(false);
        stub($field_2)->userCanRead()->returns(false);
        stub($field_3)->userCanRead()->returns(true);

        stub($field_1)->getId()->returns(111);
        stub($field_2)->getId()->returns(222);
        stub($field_3)->getId()->returns(333);

        stub($field_3)->setCriteriaValueFromREST()->returns(true);

        $criteria = $this->report->getCriteria();

        $this->assertCount($criteria, 1);

        $criteria_field_ids = array_keys($criteria);
        $this->assertEqual($criteria_field_ids[0], 333);
    }

    public function itAddsCriteria()
    {
        $integer = mock('Tracker_FormElement_Field_Integer');
        stub($integer)->getId()->returns(22);
        stub($integer)->userCanRead()->returns(true);

        $label = mock('Tracker_FormElement_Field_Text');
        stub($label)->getId()->returns(44);
        stub($label)->userCanRead()->returns(true);

        stub($this->formelement_factory)->getFormElementById(137)->returns($integer);
        stub($this->formelement_factory)->getFormElementByName($this->tracker_id, "my_field")->returns($label);
        stub($this->formelement_factory)->getFormElementByName("my_other_field")->returns(null);

        stub($integer)->setCriteriaValueFromREST()->once()->returns(true);
        stub($label)->setCriteriaValueFromREST()->once()->returns(false);

        $criteria = $this->report->getCriteria();

        $this->assertCount($criteria, 1);
    }
}
