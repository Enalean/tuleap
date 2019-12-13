<?php
/**
 * Copyright (c) Enalean, 2014 - Present. All Rights Reserved.
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
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

use Tuleap\Tracker\XML\TrackerXmlImportFeedbackCollector;
use Tuleap\Tracker\Semantic\Timeframe\ArtifactTimeframeHelper;

require_once('bootstrap.php');
Mock::generate('Tracker_Artifact');

Mock::generatePartial(
    'Tracker_FormElement_Field_Date',
    'Tracker_FormElement_Field_DateTestVersion',
    array(
        'getValueDao',
        'isRequired',
        'getProperty',
        'getProperties',
        'formatDate',
        'getDao',
        '_getUserCSVDateFormat'
    )
);

Mock::generate('Tracker_Artifact_ChangesetValue_Date');

Mock::generate('Tracker_FormElement_Field_Value_DateDao');

Mock::generate('DataAccessResult');

class Tracker_FormElement_Field_DateTest_setCriteriaValueFromREST extends TuleapTestCase
{

    public function setUp()
    {
        $this->date = new Tracker_FormElement_Field_DateTestVersion();
    }

    public function itAddsAnEqualsCrterion()
    {
        $date                 = '2014-04-05';
        $criteria             = mock('Tracker_Report_Criteria');
        $criteria->report     = mock('Tracker_Report');
        $criteria->report->id = 1;
        $values               = array(
            Tracker_Report_REST::VALUE_PROPERTY_NAME    => $date,
            Tracker_Report_REST::OPERATOR_PROPERTY_NAME => Tracker_Report_REST::OPERATOR_EQUALS
        );

        $this->date->setCriteriaValueFromREST($criteria, $values);
        $res = $this->date->getCriteriaValue($criteria);

        $this->assertCount($res, 3);
        $this->assertEqual($res['op'], '=');
        $this->assertEqual($res['from_date'], 0);
        $this->assertEqual($res['to_date'], strtotime($date));
    }

    public function itAddsAGreaterThanCrterion()
    {
        $date                 = '2014-04-05T00:00:00-05:00';
        $criteria             = mock('Tracker_Report_Criteria');
        $criteria->report     = mock('Tracker_Report');
        $criteria->report->id = 1;
        $values               = array(
            Tracker_Report_REST::VALUE_PROPERTY_NAME    => $date,
            Tracker_Report_REST::OPERATOR_PROPERTY_NAME => Tracker_Report_REST::OPERATOR_GREATER_THAN
        );

        $this->date->setCriteriaValueFromREST($criteria, $values);
        $res = $this->date->getCriteriaValue($criteria);

        $this->assertCount($res, 3);
        $this->assertEqual($res['op'], '>');
        $this->assertEqual($res['from_date'], 0);
        $this->assertEqual($res['to_date'], strtotime($date));
    }

    public function itAddsALessThanCrterion()
    {
        $date                 = '2014-04-05';
        $criteria             = mock('Tracker_Report_Criteria');
        $criteria->report     = mock('Tracker_Report');
        $criteria->report->id = 1;
        $values               = array(
            Tracker_Report_REST::VALUE_PROPERTY_NAME    => array($date),
            Tracker_Report_REST::OPERATOR_PROPERTY_NAME => Tracker_Report_REST::OPERATOR_LESS_THAN
        );

        $this->date->setCriteriaValueFromREST($criteria, $values);
        $res = $this->date->getCriteriaValue($criteria);

        $this->assertCount($res, 3);
        $this->assertEqual($res['op'], '<');
        $this->assertEqual($res['from_date'], 0);
        $this->assertEqual($res['to_date'], strtotime($date));
    }

    public function itAddsABetweenCrterion()
    {
        $from_date            = '2014-04-05';
        $to_date              = '2014-05-12';
        $criteria             = mock('Tracker_Report_Criteria');
        $criteria->report     = mock('Tracker_Report');
        $criteria->report->id = 1;
        $values               = array(
            Tracker_Report_REST::VALUE_PROPERTY_NAME    => array($from_date, $to_date),
            Tracker_Report_REST::OPERATOR_PROPERTY_NAME => Tracker_Report_REST::OPERATOR_BETWEEN
        );

        $this->date->setCriteriaValueFromREST($criteria, $values);
        $res = $this->date->getCriteriaValue($criteria);

        $this->assertCount($res, 3);
        $this->assertEqual($res['op'], '=');
        $this->assertEqual($res['from_date'], strtotime($from_date));
        $this->assertEqual($res['to_date'], strtotime($to_date));
    }

    public function itIgnoresInvalidDates()
    {
        $date     = 'christmas eve';

        $criteria = mock('Tracker_Report_Criteria');
        $values   = array(
            Tracker_Report_REST::VALUE_PROPERTY_NAME    => $date,
            Tracker_Report_REST::OPERATOR_PROPERTY_NAME => Tracker_Report_REST::OPERATOR_BETWEEN
        );

        $res = $this->date->setCriteriaValueFromREST($criteria, $values);
        $this->assertFalse($res);
    }
}

class DayFieldTestVersion extends Tracker_FormElement_Field_Date
{
    public function __construct()
    {
    }

    public function getSQLCompareDate($is_advanced, $op, $from, $to, $column)
    {
        return parent::getSQLCompareDate($is_advanced, $op, $from, $to, $column);
    }

    public function isTimeDisplayed()
    {
        return false;
    }
}

class Tracker_FormElement_Field_DateTest_getSQLCompareDate_DAY extends TuleapTestCase
{

    /** @var DayFieldTestVersion */
    private $day_field;

    public function setUp()
    {
        parent::setUp();

        $this->day_field = new DayFieldTestVersion();

        $data_access = mock(\Tuleap\DB\Compat\Legacy2018\LegacyDataAccessInterface::class);
        stub($data_access)->escapeInt(1404511200)->returns(1404511200);
        stub($data_access)->escapeInt(1404684000)->returns(1404684000);

        CodendiDataAccess::setInstance($data_access);
    }

    public function tearDown()
    {
        CodendiDataAccess::clearInstance();

        parent::tearDown();
    }

    public function itReturnsTheCorrectCriteriaForBetween()
    {
        $is_advanced = true;
        $column      = 'my_date_column';
        $from        = strtotime('2014-07-05');
        $to          = strtotime('2014-07-07');

        $sql = $this->day_field->getSQLCompareDate($is_advanced, "=", $from, $to, $column);
        $this->makeStringCheckable($sql);
        $this->assertEqual($sql, "my_date_column BETWEEN $from AND $to + 86400 - 1");
    }

    public function itReturnsTheCorrectCriteriaForBefore_IncludingTheToDay()
    {
        $is_advanced = true;
        $column      = 'my_date_column';
        $to          = strtotime('2014-07-07');

        $sql = $this->day_field->getSQLCompareDate($is_advanced, "=", null, $to, $column);
        $this->makeStringCheckable($sql);
        $this->assertEqual($sql, "my_date_column <= $to + 86400 - 1");
    }

    public function itReturnsTheCorrectCriteriaForEquals()
    {
        $is_advanced = false;
        $column      = 'my_date_column';
        $from        = null;
        $to          = strtotime('2014-07-07');

        $sql = $this->day_field->getSQLCompareDate($is_advanced, "=", $from, $to, $column);
        $this->makeStringCheckable($sql);
        $this->assertEqual($sql, "my_date_column BETWEEN $to AND $to + 86400 - 1");
    }

    public function itReturnsTheCorrectCriteriaForBefore()
    {
        $is_advanced = false;
        $column      = 'my_date_column';
        $from        = null;
        $to          = strtotime('2014-07-07');

        $sql = $this->day_field->getSQLCompareDate($is_advanced, "<", $from, $to, $column);
        $this->makeStringCheckable($sql);
        $this->assertEqual($sql, "my_date_column < $to");
    }

    public function itReturnsTheCorrectCriteriaForAfter()
    {
        $is_advanced = false;
        $column      = 'my_date_column';
        $from        = null;
        $to          = strtotime('2014-07-07');

        $sql = $this->day_field->getSQLCompareDate($is_advanced, ">", $from, $to, $column);
        $this->makeStringCheckable($sql);
        $this->assertEqual($sql, "my_date_column > $to + 86400");
    }

    private function makeStringCheckable(&$string)
    {
        $string = str_replace(PHP_EOL, ' ', trim($string));

        while (strstr($string, '  ')) {
            $string = str_replace('  ', ' ', $string);
        }
    }
}

class DateTimeFieldTestVersion extends Tracker_FormElement_Field_Date
{
    public function __construct()
    {
    }

    public function getSQLCompareDate($is_advanced, $op, $from, $to, $column)
    {
        return parent::getSQLCompareDate($is_advanced, $op, $from, $to, $column);
    }

    public function isTimeDisplayed()
    {
        return true;
    }
}

class Tracker_FormElement_Field_Date_RESTTests extends TuleapTestCase
{

    public function itThrowsAnExceptionWhenReturningValueIndexedByFieldName()
    {
        $field = new DayFieldTestVersion();

        $this->expectException('Tracker_FormElement_RESTValueByField_NotImplementedException');

        $value = array('some_value');

        $field->getFieldDataFromRESTValueByField($value);
    }
}
