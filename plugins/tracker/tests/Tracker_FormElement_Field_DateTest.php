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
Mock::generatePartial(
    'Tracker_FormElement_Field_Date',
    'Tracker_FormElement_Field_DateTestVersion_ForImport',
    array(
        'getValueDao',
        'formatDate',
        'getDao',
        '_getUserCSVDateFormat'
    )
);

Mock::generate('Tracker_Artifact_ChangesetValue_Date');

Mock::generate('Tracker_FormElement_Field_Value_DateDao');

Mock::generate('DataAccessResult');

class Tracker_FormElement_Field_DateTest extends TuleapTestCase
{

    /** @var XML_Security */
    protected $xml_security;

    public function setUp()
    {
        parent::setUp();

        $this->xml_security = new XML_Security();
        $this->xml_security->enableExternalLoadOfEntities();
    }

    public function tearDown()
    {
        $this->xml_security->disableExternalLoadOfEntities();

        unset($GLOBALS['sys_incdir']);
        unset($GLOBALS['sys_custom_incdir']);
        unset($GLOBALS['Language']);

        parent::tearDown();
    }

    function testNoDefaultValue()
    {
        $date_field = new Tracker_FormElement_Field_DateTestVersion();
        $this->assertFalse($date_field->hasDefaultValue());
    }

    function testDefaultValue()
    {
        $date_field = new Tracker_FormElement_Field_DateTestVersion();
        $date_field->setReturnValue('getProperty', '1', array('default_value_type')); //custom date
        $date_field->setReturnValue('getProperty', '1234567890', array('default_value'));
        $date_field->setReturnValue('formatDate', '2009-02-13', array('1234567890'));
        $this->assertTrue($date_field->hasDefaultValue());
        $this->assertEqual($date_field->getDefaultValue(), '2009-02-13');
    }

    function testToday()
    {
        $date_field = new Tracker_FormElement_Field_DateTestVersion();
        $date_field->setReturnValue('getProperty', '0', array('default_value_type')); //today
        $date_field->setReturnValue('getProperty', '1234567890', array('default_value'));
        $date_field->setReturnValue('formatDate', '2009-02-13', array('1234567890'));
        $date_field->setReturnValue('formatDate', 'date-of-today', array($_SERVER['REQUEST_TIME']));
        $this->assertTrue($date_field->hasDefaultValue());
        $this->assertEqual($date_field->getDefaultValue(), 'date-of-today');
    }

    public function itDisplayTime()
    {
        $date_field = new Tracker_FormElement_Field_DateTestVersion();
        $date_field->setReturnValue('getProperty', 1, array('display_time'));

        $this->assertTrue($date_field->isTimeDisplayed());
    }

    public function itDontDisplayTime()
    {
        $date_field = new Tracker_FormElement_Field_DateTestVersion();
        $date_field->setReturnValue('getProperty', 0, array('display_time'));

        $this->assertFalse($date_field->isTimeDisplayed());
    }

    function testGetChangesetValue()
    {
        $value_dao = new MockTracker_FormElement_Field_Value_DateDao();
        $dar = new MockDataAccessResult();
        $dar->setReturnValueAt(0, 'getRow', array('id' => 123, 'field_id' => 1, 'value' => '1221221466'));
        $dar->setReturnValue('getRow', false);
        $value_dao->setReturnReference('searchById', $dar);

        $date_field = new Tracker_FormElement_Field_DateTestVersion();
        $date_field->setReturnReference('getValueDao', $value_dao);

        $this->assertIsA($date_field->getChangesetValue(mock('Tracker_Artifact_Changeset'), 123, false), 'Tracker_Artifact_ChangesetValue_Date');
    }

    function testGetChangesetValue_doesnt_exist()
    {
        $value_dao = new MockTracker_FormElement_Field_Value_DateDao();
        $dar = new MockDataAccessResult();
        $dar->setReturnValue('getRow', false);
        $value_dao->setReturnReference('searchById', $dar);

        $date_field = new Tracker_FormElement_Field_DateTestVersion();
        $date_field->setReturnReference('getValueDao', $value_dao);

        $this->assertNull($date_field->getChangesetValue(null, 123, false));
    }

    function testIsValidRequiredField()
    {
        $f = new Tracker_FormElement_Field_DateTestVersion();
        $f->setReturnValue('isRequired', true);
        $a = new MockTracker_Artifact();
        $this->assertTrue($f->isValid($a, '2009-08-31'));
        $this->assertFalse($f->isValid($a, '2009-08-45'));
        $this->assertFalse($f->isValid($a, '2009-13-06'));
        $this->assertFalse($f->isValid($a, '20091306'));
        $this->assertFalse($f->isValid($a, '06/12/2009'));
        $this->assertFalse($f->isValid($a, '06-12-2009'));
        $this->assertFalse($f->isValid($a, 'foobar'));
        $this->assertFalse($f->isValid($a, 06/12/2009));
        $this->assertFalse($f->isValidRegardingRequiredProperty($a, ''));
        $this->assertFalse($f->isValidRegardingRequiredProperty($a, null));
    }

    function testIsValidNotRequiredField()
    {
        $f = new Tracker_FormElement_Field_DateTestVersion();
        $f->setReturnValue('isRequired', false);
        $a = new MockTracker_Artifact();
        $this->assertTrue($f->isValid($a, ''));
        $this->assertTrue($f->isValid($a, null));
    }

    function testGetFieldData()
    {
        $f = new Tracker_FormElement_Field_DateTestVersion();
        $this->assertEqual('2010-04-30', $f->getFieldData('2010-04-30'));
        $this->assertNull($f->getFieldData('03-04-2010'));
    }

    function testGetFieldDataAsTimestamp()
    {
        $f = new Tracker_FormElement_Field_DateTestVersion();
        $this->assertEqual('2010-04-30', $f->getFieldData((string)mktime(5, 3, 2, 4, 30, 2010)));
        $this->assertNull($f->getFieldData('1.5'));
    }

    function testGetFieldDataForCSVPreview()
    {
        $f = new Tracker_FormElement_Field_DateTestVersion();
        $f->setReturnValueAt(0, '_getUserCSVDateFormat', 'day_month_year');
        $f->setReturnValueAt(1, '_getUserCSVDateFormat', 'month_day_year');
        $f->setReturnValueAt(2, '_getUserCSVDateFormat', 'day_month_year');
        $f->setReturnValueAt(3, '_getUserCSVDateFormat', 'day_month_year');

        $this->assertEqual('1981-04-25', $f->getFieldDataForCSVPreview('25/04/1981'));
        $this->assertEqual('1981-04-25', $f->getFieldDataForCSVPreview('04/25/1981'));
        $this->assertNull($f->getFieldDataForCSVPreview('35/44/1981'));  // this function check date validity!
        $this->assertNull($f->getFieldDataForCSVPreview(''));
    }

    function testExplodeXlsDateFmtDDMMYYYY()
    {
        $f = new Tracker_FormElement_Field_DateTestVersion();
        $f->setReturnValue('_getUserCSVDateFormat', 'day_month_year');
        $this->assertEqual(array('1981', '04', '25', '0', '0', '0'), $f->explodeXlsDateFmt('25/04/1981'));
        $this->assertArrayEmpty($f->explodeXlsDateFmt('04/25/1981'));
        $this->assertArrayEmpty($f->explodeXlsDateFmt('04/25/81'));
        $this->assertArrayEmpty($f->explodeXlsDateFmt('25/04/81'));
        $this->assertArrayEmpty($f->explodeXlsDateFmt('25/04/81 10AM'));
    }

    function testExplodeXlsDateFmtMMDDYYYY()
    {
        $f = new Tracker_FormElement_Field_DateTestVersion();
        $f->setReturnValue('_getUserCSVDateFormat', 'month_day_year');
        $this->assertEqual(array('1981', '04', '25', '0', '0', '0'), $f->explodeXlsDateFmt('04/25/1981'));
        $this->assertArrayEmpty($f->explodeXlsDateFmt('25/04/1981'));
        $this->assertArrayEmpty($f->explodeXlsDateFmt('25/04/81'));
        $this->assertArrayEmpty($f->explodeXlsDateFmt('04/25/81'));
        $this->assertArrayEmpty($f->explodeXlsDateFmt('04/25/81 10AM'));
    }

    public function itExplodesDateWithHoursInDDMMYYYYFormat()
    {
        $field = new Tracker_FormElement_Field_DateTestVersion();
        $field->setReturnValue('_getUserCSVDateFormat', 'day_month_year');

        $this->assertEqual(
            array('1981', '04', '25', '10', '00', '00'),
            $field->explodeXlsDateFmt('25/04/1981 10:00:01')
        );
    }

    public function itExplodesDateWithHoursInMMDDYYYYFormat()
    {
        $field = new Tracker_FormElement_Field_DateTestVersion();
        $field->setReturnValue('_getUserCSVDateFormat', 'month_day_year');

        $this->assertEqual(
            array('1981', '04', '25', '01', '02', '00'),
            $field->explodeXlsDateFmt('04/25/1981 01:02:03')
        );
    }

    public function itExplodesDateWithHoursInMMDDYYYYHHSSFormatWithoutGivenSeconds()
    {
        $field = new Tracker_FormElement_Field_DateTestVersion();
        $field->setReturnValue('_getUserCSVDateFormat', 'month_day_year');

        $this->assertEqual(
            array('1981', '04', '25', '01', '02', '00'),
            $field->explodeXlsDateFmt('04/25/1981 01:02')
        );
    }

    function testNbDigits()
    {
        $f = new Tracker_FormElement_Field_DateTestVersion();
        $this->assertEqual(1, $f->_nbDigits(1));
        $this->assertEqual(2, $f->_nbDigits(15));
        $this->assertEqual(3, $f->_nbDigits(101));
        $this->assertEqual(4, $f->_nbDigits(1978));
        $this->assertEqual(5, $f->_nbDigits(12345));
        $this->assertEqual(1, $f->_nbDigits(001));
        $this->assertEqual(1, $f->_nbDigits('001'));
    }

    function testExportPropertiesToXMLNoDefaultValue()
    {
        $xml_test = simplexml_load_file(dirname(__FILE__) . '/_fixtures/ImportTrackerFormElementDatePropertiesNoDefaultValueTest.xml');

        $date_field = new Tracker_FormElement_Field_DateTestVersion();
        $properties = array(
            'default_value_type' => array(
                'type'    => 'radio',
                'value'   => 1,    // specific date
                'choices' => array(
                    'default_value_today' => array(
                        'radio_value' => 0,
                        'type'        => 'label',
                        'value'       => 'today',
                    ),
                    'default_value' => array(
                        'radio_value' => 1,
                        'type'  => 'date',
                        'value' => '',          // no default value
                    ),
                )
            )
        );
        $date_field->setReturnReference('getProperties', $properties);
        $root = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><tracker />');
        $date_field->exportPropertiesToXML($root);
        $this->assertEqual((string)$xml_test->properties, (string)$root->properties);
        $this->assertEqual(count($root->properties->attributes()), 0);
    }

    function testExportPropertiesToXMLNoDefaultValue2()
    {
        // another test if value = '0' instead of ''
        $xml_test = simplexml_load_file(dirname(__FILE__) . '/_fixtures/ImportTrackerFormElementDatePropertiesNoDefaultValueTest.xml');

        $date_field = new Tracker_FormElement_Field_DateTestVersion();
        $properties = array(
            'default_value_type' => array(
                'type'    => 'radio',
                'value'   => 1,    // specific date
                'choices' => array(
                    'default_value_today' => array(
                        'radio_value' => 0,
                        'type'        => 'label',
                        'value'       => 'today',
                    ),
                    'default_value' => array(
                        'radio_value' => 1,
                        'type'  => 'date',
                        'value' => '0',          // no default value
                    ),
                )
            )
        );
        $date_field->setReturnReference('getProperties', $properties);
        $root = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><tracker />');
        $date_field->exportPropertiesToXML($root);
        $this->assertEqual((string)$xml_test->properties, (string)$root->properties);
        $this->assertEqual(count($root->properties->attributes()), 0);
    }

    function testExportPropertiesToXMLDefaultValueToday()
    {
        $xml_test = simplexml_load_file(dirname(__FILE__) . '/_fixtures/ImportTrackerFormElementDatePropertiesDefaultValueTodayTest.xml');

        $date_field = new Tracker_FormElement_Field_DateTestVersion();
        $properties = array(
            'default_value_type' => array(
                'type'    => 'radio',
                'value'   => 0,    // today
                'choices' => array(
                    'default_value_today' => array(
                        'radio_value' => 0,
                        'type'        => 'label',
                        'value'       => 'today',
                    ),
                    'default_value' => array(
                        'radio_value' => 1,
                        'type'  => 'date',
                        'value' => '1234567890',
                    ),
                )
            )
        );
        $date_field->setReturnReference('getProperties', $properties);
        $root = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><tracker />');
        $date_field->exportPropertiesToXML($root);
        $this->assertEqual((string)$xml_test->properties, (string)$root->properties);
        $this->assertEqual(count($root->properties->attributes()), 1);
        $attr = $root->properties->attributes();
        $this->assertEqual('today', ((string)$attr->default_value));
    }

    function testExportPropertiesToXMLDefaultValueSpecificDate()
    {
        $xml_test = simplexml_load_file(dirname(__FILE__) . '/_fixtures/ImportTrackerFormElementDatePropertiesDefaultValueSpecificDateTest.xml');

        $date_field = new Tracker_FormElement_Field_DateTestVersion();
        $properties = array(
            'default_value_type' => array(
                'type'    => 'radio',
                'value'   => 1,    // specific date
                'choices' => array(
                    'default_value_today' => array(
                        'radio_value' => 0,
                        'type'        => 'label',
                        'value'       => 'today',
                    ),
                    'default_value' => array(
                        'radio_value' => 1,
                        'type'  => 'date',
                        'value' => '1234567890',  // specific date
                    ),
                )
            )
        );
        $date_field->setReturnReference('getProperties', $properties);
        $root = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><tracker />');
        $date_field->exportPropertiesToXML($root);
        $this->assertEqual((string)$xml_test->properties, (string)$root->properties);
        $this->assertEqual(count($root->properties->attributes()), 1);
        $attr = $root->properties->attributes();
        $this->assertEqual('1234567890', ((string)$attr->default_value));
    }

    public function testExportPropertiesToXMLDisplayTime()
    {
        $xml_test = simplexml_load_file(dirname(__FILE__) . '/_fixtures/ImportTrackerFormElementDatePropertiesDisplayTime.xml');

        $date_field = new Tracker_FormElement_Field_DateTestVersion();
        $properties = array(
            'display_time' => array(
                'type'    => 'checkbox',
                'value'   => 1
            )
        );

        $date_field->setReturnReference('getProperties', $properties);
        $date_field->setReturnValue('getProperty', 1, array('display_time'));

        $root = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><tracker />');
        $date_field->exportPropertiesToXML($root);
        $this->assertEqual((string)$xml_test->properties, (string)$root->properties);
        $this->assertEqual(count($root->properties->attributes()), 1);
        $attr = $root->properties->attributes();
        $this->assertEqual('1', ((string)$attr->display_time));
    }

    public function testExportPropertiesToXMLDisplayTimeWhenDisplayTimeIsZero()
    {
        $xml_test = simplexml_load_file(dirname(__FILE__) . '/_fixtures/ImportTrackerFormElementDatePropertiesDisplayTimeZero.xml');

        $date_field = new Tracker_FormElement_Field_DateTestVersion();
        $properties = array(
            'display_time' => array(
                'type'    => 'checkbox',
                'value'   => 0
            )
        );

        $date_field->setReturnReference('getProperties', $properties);
        $date_field->setReturnValue('getProperty', 0, array('display_time'));

        $root = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><tracker />');
        $date_field->exportPropertiesToXML($root);
        $this->assertEqual((string)$xml_test->properties, (string)$root->properties);
        $this->assertEqual(count($root->properties->attributes()), 1);
        $attr = $root->properties->attributes();
        $this->assertEqual('0', ((string)$attr->display_time));
    }

    function testImport_realdate()
    {
        $xml = new SimpleXMLElement('<?xml version="1.0" standalone="yes"?>
            <formElement type="date" ID="F0" rank="20" required="1">
                <name>field_name</name>
                <label>field_label</label>
                <description>field_description</description>
                <properties default_value="1234567890" />
            </formElement>');

        $mapping = array();

        $date = new Tracker_FormElement_Field_DateTestVersion_ForImport();
        $date->setReturnValue('formatDate', '2009-02-13', array('1234567890'));
        $date->setReturnValue('formatDate', 'date-of-today', array($_SERVER['REQUEST_TIME']));

        $feedback_collector = \Mockery::mock(TrackerXmlImportFeedbackCollector::class);

        $date->continueGetInstanceFromXML($xml, $mapping, mock('User\XML\Import\IFindUserFromXMLReference'), $feedback_collector);

        $this->assertEqual($date->getDefaultValue(), '2009-02-13');
    }

    function testImport_today()
    {
        $xml = new SimpleXMLElement('<?xml version="1.0" standalone="yes"?>
            <formElement type="date" ID="F0" rank="20" required="1">
                <name>field_name</name>
                <label>field_label</label>
                <description>field_description</description>
                <properties default_value="today" />
            </formElement>');

        $mapping = array();

        $date = new Tracker_FormElement_Field_DateTestVersion_ForImport();
        $date->setReturnValue('formatDate', '2009-02-13', array('1234567890'));
        $date->setReturnValue('formatDate', 'date-of-today', array($_SERVER['REQUEST_TIME']));

        $feedback_collector = \Mockery::mock(TrackerXmlImportFeedbackCollector::class);

        $date->continueGetInstanceFromXML($xml, $mapping, mock('User\XML\Import\IFindUserFromXMLReference'), $feedback_collector);

        $this->assertEqual($date->getDefaultValue(), 'date-of-today');
    }

    function testImport_nodefault()
    {
        $xml = new SimpleXMLElement('<?xml version="1.0" standalone="yes"?>
            <formElement type="date" ID="F0" rank="20" required="1">
                <name>field_name</name>
                <label>field_label</label>
                <description>field_description</description>
                <properties />
            </formElement>');

        $mapping = array();

        $date = new Tracker_FormElement_Field_DateTestVersion_ForImport();
        $date->setReturnValue('formatDate', '2009-02-13', array('1234567890'));
        $date->setReturnValue('formatDate', 'date-of-today', array($_SERVER['REQUEST_TIME']));

        $feedback_collector = \Mockery::mock(TrackerXmlImportFeedbackCollector::class);

        $date->continueGetInstanceFromXML($xml, $mapping, mock('User\XML\Import\IFindUserFromXMLReference'), $feedback_collector);

        $this->assertEqual($date->getDefaultValue(), '');
    }

    function testImport_empty()
    {
        $xml = new SimpleXMLElement('<?xml version="1.0" standalone="yes"?>
            <formElement type="date" ID="F0" rank="20" required="1">
                <name>field_name</name>
                <label>field_label</label>
                <description>field_description</description>
                <properties default_value="" />
            </formElement>');

        $mapping = array();

        $date = new Tracker_FormElement_Field_DateTestVersion_ForImport();
        $date->setReturnValue('formatDate', '2009-02-13', array('1234567890'));
        $date->setReturnValue('formatDate', 'date-of-today', array($_SERVER['REQUEST_TIME']));

        $feedback_collector = \Mockery::mock(TrackerXmlImportFeedbackCollector::class);

        $date->continueGetInstanceFromXML($xml, $mapping, mock('User\XML\Import\IFindUserFromXMLReference'), $feedback_collector);

        $this->assertEqual($date->getDefaultValue(), '');
    }

    function testFieldDateShouldSendEmptyMailValueWhenValueIsEmpty()
    {
        $user = mock('PFUser');
        $artifact = new MockTracker_Artifact();
        $date = new Tracker_FormElement_Field_DateTestVersion();
        $this->assertEqual('-', $date->fetchMailArtifactValue($artifact, $user, false, null, null));
    }

    function testFieldDateShouldSendAMailWithAReadableDate_EnUS()
    {
        $GLOBALS['sys_incdir'] = TRACKER_BASE_DIR. '/../../../site-content';
        $GLOBALS['sys_custom_incdir'] = TRACKER_BASE_DIR. '/../../../site-content';

        $GLOBALS['Language'] = new BaseLanguage('en_US', 'en_US');
        $GLOBALS['Language']->lang = 'en_US';

        $user     = mock('PFUser');
        $artifact = new MockTracker_Artifact();

        $date = Mockery::mock(Tracker_FormElement_Field_Date::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $date->shouldReceive('formatDateForDisplay')->with('2011-12-01')->andReturn(1322752769);
        $date->shouldReceive('isTimeDisplayed')->andReturnFalse();
        $date->shouldReceive('getArtifactTimeframeHelper')->andReturn(Mockery::mock(ArtifactTimeframeHelper::class, ['artifactHelpShouldBeShownToUser' => false]));

        $value = Mockery::mock(Tracker_Artifact_ChangesetValue_Date::class);
        $value->shouldReceive('getTimestamp')->andReturn(1322752769);

        $this->assertEqual('2011-12-01', $date->fetchMailArtifactValue($artifact, $user, false, $value, 'text'));
        $this->assertEqual('2011-12-01', $date->fetchMailArtifactValue($artifact, $user, false, $value, 'html'));
    }

    function testFieldDateShouldSendAMailWithAReadableDate_frFR()
    {
        $GLOBALS['sys_incdir'] = TRACKER_BASE_DIR. '/../../../site-content';
        $GLOBALS['sys_custom_incdir'] = TRACKER_BASE_DIR. '/../../../site-content';

        $GLOBALS['Language'] = new BaseLanguage('fr_FR', 'fr_FR');
        $GLOBALS['Language']->lang = 'fr_FR';

        $user     = mock('PFUser');
        $artifact = new MockTracker_Artifact();

        $date = Mockery::mock(Tracker_FormElement_Field_Date::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $date->shouldReceive('formatDateForDisplay')->with('2011-12-01')->andReturn(1322752769);
        $date->shouldReceive('isTimeDisplayed')->andReturnFalse();
        $date->shouldReceive('getArtifactTimeframeHelper')->andReturn(Mockery::mock(ArtifactTimeframeHelper::class, ['artifactHelpShouldBeShownToUser' => false]));

        $value = Mockery::mock(Tracker_Artifact_ChangesetValue_Date::class);
        $value->shouldReceive('getTimestamp')->andReturn(1322752769);

        $this->assertEqual('01/12/2011', $date->fetchMailArtifactValue($artifact, $user, false, $value, 'text'));
        $this->assertEqual('01/12/2011', $date->fetchMailArtifactValue($artifact, $user, false, $value, 'html'));
    }

    function testFieldDateShouldSendEmptyMailWhenThereIsNoDateDefined()
    {
        $user     = mock('PFUser');
        $artifact = new MockTracker_Artifact();

        $date = new Tracker_FormElement_Field_DateTestVersion();

        $value = new MockTracker_Artifact_ChangesetValue_Date();
        $value->setReturnValue('getTimestamp', 0);

        $this->assertEqual('-', $date->fetchMailArtifactValue($artifact, $user, false, $value, 'text'));
        $this->assertEqual('-', $date->fetchMailArtifactValue($artifact, $user, false, $value, 'html'));
    }
}

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
