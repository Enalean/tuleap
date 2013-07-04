<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 *
 * This file is a part of Codendi.
 *
 * Codendi is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Codendi is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Codendi. If not, see <http://www.gnu.org/licenses/>.
 */
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
);Mock::generatePartial(
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

require_once('common/dao/include/DataAccessResult.class.php');
Mock::generate('DataAccessResult');

require_once('common/valid/Rule.class.php');    // unit test not really unit...

class Tracker_FormElement_Field_DateTest extends TuleapTestCase {
    
    function testNoDefaultValue() {
        $date_field = new Tracker_FormElement_Field_DateTestVersion();
        $this->assertFalse($date_field->hasDefaultValue());
    }
    
    function testDefaultValue() {
        $date_field = new Tracker_FormElement_Field_DateTestVersion();
        $date_field->setReturnValue('getProperty', '1', array('default_value_type')); //custom date
        $date_field->setReturnValue('getProperty', '1234567890', array('default_value'));
        $date_field->setReturnValue('formatDate', '2009-02-13', array('1234567890'));
        $this->assertTrue($date_field->hasDefaultValue());
        $this->assertEqual($date_field->getDefaultValue(), '2009-02-13');
    }
    
    function testToday() {
        $date_field = new Tracker_FormElement_Field_DateTestVersion();
        $date_field->setReturnValue('getProperty', '0', array('default_value_type')); //today
        $date_field->setReturnValue('getProperty', '1234567890', array('default_value'));
        $date_field->setReturnValue('formatDate', '2009-02-13', array('1234567890'));
        $date_field->setReturnValue('formatDate', 'date-of-today', array($_SERVER['REQUEST_TIME']));
        $this->assertTrue($date_field->hasDefaultValue());
        $this->assertEqual($date_field->getDefaultValue(), 'date-of-today');
    }
    
    function testGetChangesetValue() {
        $value_dao = new MockTracker_FormElement_Field_Value_DateDao();
        $dar = new MockDataAccessResult();
        $dar->setReturnValueAt(0, 'getRow', array('id' => 123, 'field_id' => 1, 'value' => '1221221466'));
        $dar->setReturnValue('getRow', false);
        $value_dao->setReturnReference('searchById', $dar);
        
        $date_field = new Tracker_FormElement_Field_DateTestVersion();
        $date_field->setReturnReference('getValueDao', $value_dao);
        
        $this->assertIsA($date_field->getChangesetValue(null, 123, false), 'Tracker_Artifact_ChangesetValue_Date');
    }
    
    function testGetChangesetValue_doesnt_exist() {
        $value_dao = new MockTracker_FormElement_Field_Value_DateDao();
        $dar = new MockDataAccessResult();
        $dar->setReturnValue('getRow', false);
        $value_dao->setReturnReference('searchById', $dar);
        
        $date_field = new Tracker_FormElement_Field_DateTestVersion();
        $date_field->setReturnReference('getValueDao', $value_dao);
        
        $this->assertNull($date_field->getChangesetValue(null, 123, false));
    }
    
    function testIsValidRequiredField() {
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
        $this->assertFalse($f->isValid($a, ''));
        $this->assertFalse($f->isValid($a, null));
    }
    
    function testIsValidNotRequiredField() {
        $f = new Tracker_FormElement_Field_DateTestVersion();
        $f->setReturnValue('isRequired', false);
        $a = new MockTracker_Artifact();
        $this->assertTrue($f->isValid($a, ''));
        $this->assertTrue($f->isValid($a, null));
    }
    
    function testSoapAvailableValues() {
        $f = new Tracker_FormElement_Field_DateTestVersion();
        $this->assertNull($f->getSoapAvailableValues());
    }
    
    function testGetFieldData() {
        $f = new Tracker_FormElement_Field_DateTestVersion();
        $this->assertEqual('2010-04-30', $f->getFieldData('2010-04-30'));
        $this->assertNull($f->getFieldData('03-04-2010'));
    }
    
    function testGetFieldDataAsTimestamp() {
        $f = new Tracker_FormElement_Field_DateTestVersion();
        $this->assertEqual('2010-04-30', $f->getFieldData((string)mktime(5,3,2,4,30,2010)));
        $this->assertNull($f->getFieldData('1.5'));
    }

    function testGetFieldDataForCSVPreview() {
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
    
    function testExplodeXlsDateFmtDDMMYYYY() {
        $f = new Tracker_FormElement_Field_DateTestVersion();
        $f->setReturnValue('_getUserCSVDateFormat', 'day_month_year');
        $this->assertEqual(array('1981', '04', '25', '0', '0'), $f->explodeXlsDateFmt('25/04/1981'));
        $this->assertNull($f->explodeXlsDateFmt('04/25/1981'));
        $this->assertNull($f->explodeXlsDateFmt('04/25/81'));
        $this->assertNull($f->explodeXlsDateFmt('25/04/81'));
        $this->assertNull($f->explodeXlsDateFmt('25/04/81 10AM'));
    }
    
    function testExplodeXlsDateFmtMMDDYYYY() {
        $f = new Tracker_FormElement_Field_DateTestVersion();
        $f->setReturnValue('_getUserCSVDateFormat', 'month_day_year');
        $this->assertEqual(array('1981', '04', '25', '0', '0'), $f->explodeXlsDateFmt('04/25/1981'));
        $this->assertNull($f->explodeXlsDateFmt('25/04/1981'));
        $this->assertNull($f->explodeXlsDateFmt('25/04/81'));
        $this->assertNull($f->explodeXlsDateFmt('04/25/81'));
        $this->assertNull($f->explodeXlsDateFmt('04/25/81 10AM'));
    }
    
    function testNbDigits() {
        $f = new Tracker_FormElement_Field_DateTestVersion();
        $this->assertEqual(1, $f->_nbDigits(1));
        $this->assertEqual(2, $f->_nbDigits(15));
        $this->assertEqual(3, $f->_nbDigits(101));
        $this->assertEqual(4, $f->_nbDigits(1978));
        $this->assertEqual(5, $f->_nbDigits(12345));
        $this->assertEqual(1, $f->_nbDigits(001));
        $this->assertEqual(1, $f->_nbDigits('001'));
    }
    
    function testExportPropertiesToXMLNoDefaultValue() {
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
    
    function testExportPropertiesToXMLNoDefaultValue2() {
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
    
    function testExportPropertiesToXMLDefaultValueToday() {
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
    
    function testExportPropertiesToXMLDefaultValueSpecificDate() {
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
    
    function testImport_realdate() {
        $xml = new SimpleXMLElement('<?xml version="1.0" standalone="yes"?>
            <formElement type="date" ID="F0" rank="20" required="1">
                <name>field_name</name>
                <label>field_label</label>
                <description>field_description</description>
                <properties default_value="1234567890" />
            </formElement>'
        );
        
        $mapping = array();
        
        $date = new Tracker_FormElement_Field_DateTestVersion_ForImport();
        $date->setReturnValue('formatDate', '2009-02-13', array('1234567890'));
        $date->setReturnValue('formatDate', 'date-of-today', array($_SERVER['REQUEST_TIME']));
        
        $date->continueGetInstanceFromXML($xml, $mapping);
        
        $this->assertEqual($date->getDefaultValue(), '2009-02-13');
    }
    
    function testImport_today() {
        $xml = new SimpleXMLElement('<?xml version="1.0" standalone="yes"?>
            <formElement type="date" ID="F0" rank="20" required="1">
                <name>field_name</name>
                <label>field_label</label>
                <description>field_description</description>
                <properties default_value="today" />
            </formElement>'
        );
        
        $mapping = array();
        
        $date = new Tracker_FormElement_Field_DateTestVersion_ForImport();
        $date->setReturnValue('formatDate', '2009-02-13', array('1234567890'));
        $date->setReturnValue('formatDate', 'date-of-today', array($_SERVER['REQUEST_TIME']));
        
        $date->continueGetInstanceFromXML($xml, $mapping);
        
        $this->assertEqual($date->getDefaultValue(), 'date-of-today');
    }
    
    function testImport_nodefault() {
        $xml = new SimpleXMLElement('<?xml version="1.0" standalone="yes"?>
            <formElement type="date" ID="F0" rank="20" required="1">
                <name>field_name</name>
                <label>field_label</label>
                <description>field_description</description>
                <properties />
            </formElement>'
        );
        
        $mapping = array();
        
        $date = new Tracker_FormElement_Field_DateTestVersion_ForImport();
        $date->setReturnValue('formatDate', '2009-02-13', array('1234567890'));
        $date->setReturnValue('formatDate', 'date-of-today', array($_SERVER['REQUEST_TIME']));
        
        $date->continueGetInstanceFromXML($xml, $mapping);
        
        $this->assertEqual($date->getDefaultValue(), '');
    }
    
    function testImport_empty() {
        $xml = new SimpleXMLElement('<?xml version="1.0" standalone="yes"?>
            <formElement type="date" ID="F0" rank="20" required="1">
                <name>field_name</name>
                <label>field_label</label>
                <description>field_description</description>
                <properties default_value="" />
            </formElement>'
        );
        
        $mapping = array();
        
        $date = new Tracker_FormElement_Field_DateTestVersion_ForImport();
        $date->setReturnValue('formatDate', '2009-02-13', array('1234567890'));
        $date->setReturnValue('formatDate', 'date-of-today', array($_SERVER['REQUEST_TIME']));
        
        $date->continueGetInstanceFromXML($xml, $mapping);
        
        $this->assertEqual($date->getDefaultValue(), '');
    }
    
    function testFieldDateShouldSendEmptyMailValueWhenValueIsEmpty() {
        $artifact = new MockTracker_Artifact();
        $date = new Tracker_FormElement_Field_DateTestVersion();
        $this->assertEqual('-', $date->fetchMailArtifactValue($artifact, null, null));
    }
    
    function testFieldDateShouldSendAMailWithAReadableDate() {
        $artifact = new MockTracker_Artifact();
        
        $date = new Tracker_FormElement_Field_DateTestVersion();
        $date->setReturnValue('formatDate', '2011-12-01', array(1322752769));
        
        $value = new MockTracker_Artifact_ChangesetValue_Date();
        $value->setReturnValue('getTimestamp', 1322752769);
        
        $this->assertEqual('2011-12-01', $date->fetchMailArtifactValue($artifact, $value, 'text'));
        $this->assertEqual('2011-12-01', $date->fetchMailArtifactValue($artifact, $value, 'html'));
    }
    
    function testFieldDateShouldSendEmptyMailWhenThereIsNoDateDefined() {
        $artifact = new MockTracker_Artifact();
        
        $date = new Tracker_FormElement_Field_DateTestVersion();
        
        $value = new MockTracker_Artifact_ChangesetValue_Date();
        $value->setReturnValue('getTimestamp', 0);
        
        $this->assertEqual('-', $date->fetchMailArtifactValue($artifact, $value, 'text'));
        $this->assertEqual('-', $date->fetchMailArtifactValue($artifact, $value, 'html'));
    }
}

?>