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
 
require_once('common/language/BaseLanguage.class.php');
Mock::generate('BaseLanguage');

require_once(dirname(__FILE__).'/../include/Tracker/Artifact/Tracker_Artifact.class.php');
Mock::generate('Tracker_Artifact');

require_once(dirname(__FILE__).'/../include/Tracker/FormElement/Tracker_FormElement_Field_ArtifactLink.class.php');
Mock::generatePartial(
    'Tracker_FormElement_Field_ArtifactLink', 
    'Tracker_FormElement_Field_ArtifactLinkTestVersion', 
    array(
        'getValueDao', 
        'isRequired', 
        'getProperty', 
        'getProperties',
        'getDao',
        'getRuleArtifactId'
    )
);Mock::generatePartial(
    'Tracker_FormElement_Field_ArtifactLink', 
    'Tracker_FormElement_Field_ArtifactLinkTestVersion_ForImport', 
    array(
        'getValueDao',
        'getDao',
        'getRuleArtifactId'
    )
);

require_once(dirname(__FILE__).'/../include/Tracker/Artifact/Tracker_Artifact_Changeset.class.php');
Mock::generate('Tracker_Artifact_Changeset');

require_once(dirname(__FILE__).'/../include/Tracker/Artifact/Tracker_Artifact_ChangesetValue_ArtifactLink.class.php');
Mock::generate('Tracker_Artifact_ChangesetValue_ArtifactLink');

require_once(dirname(__FILE__).'/../include/Tracker/FormElement/dao/Tracker_FormElement_Field_Value_ArtifactLinkDao.class.php');
Mock::generate('Tracker_FormElement_Field_Value_ArtifactLinkDao');

require_once('common/dao/include/DataAccessResult.class.php');
Mock::generate('DataAccessResult');

require_once(dirname(__FILE__).'/../include/Tracker/Tracker_Valid_Rule.class.php');
Mock::generate('Tracker_Valid_Rule_ArtifactId');

require_once('common/include/Response.class.php');
Mock::generate('Response');

class Tracker_FormElement_Field_ArtifactLinkTest extends UnitTestCase {
    
    function setUp() {
        $GLOBALS['Response'] = new MockResponse();
        $GLOBALS['Language'] = new MockBaseLanguage();
    }
    
    function tearDrop() {
        unset($GLOBALS['Response']);
    }
    
    function testNoDefaultValue() {
        $field = new Tracker_FormElement_Field_ArtifactLinkTestVersion();
        $this->assertFalse($field->hasDefaultValue());
    }
    
    function testGetChangesetValue() {
        $value_dao = new MockTracker_FormElement_Field_Value_ArtifactLinkDao();
        $dar = new MockDataAccessResult();
        $dar->setReturnValueAt(0, 'valid', true);
        $dar->setReturnValueAt(0, 'getRow', array(
                                                'id' => 123, 
                                                'field_id' => 1, 
                                                'artifact_id' => '999', 
                                                'keyword' => 'bug', 
                                                'group_id' => '102',
                                                'tracker_id' => '456', 
                                                'last_changeset_id' => '789'));
        $dar->setReturnValue('getRow', false);
        $value_dao->setReturnReference('searchById', $dar);
        
        $field = new Tracker_FormElement_Field_ArtifactLinkTestVersion();
        $field->setReturnReference('getValueDao', $value_dao);
        
        $this->assertIsA($field->getChangesetValue(null, 123, false), 'Tracker_Artifact_ChangesetValue_ArtifactLink');
    }
    
    function testGetChangesetValue_doesnt_exist() {
        $value_dao = new MockTracker_FormElement_Field_Value_ArtifactLinkDao();
        $dar = new MockDataAccessResult();
        $dar->setReturnValue('getRow', false);
        $value_dao->setReturnReference('searchById', $dar);
        
        $field = new Tracker_FormElement_Field_ArtifactLinkTestVersion();
        $field->setReturnReference('getValueDao', $value_dao);
        
        $this->assertNotNull($field->getChangesetValue(null, 123, false));
    }
    
    function testFetchRawValue() {
        $f = new Tracker_FormElement_Field_ArtifactLinkTestVersion();
        $art_ids = array('123, 132, 999');
        $value = new MockTracker_Artifact_ChangesetValue_ArtifactLink();
        $value->setReturnReference('getArtifactIds', $art_ids);
        $this->assertEqual($f->fetchRawValue($value), '123, 132, 999');
    }
    
    function testIsValidRequiredFieldWithExistingValues() {
        $f = new Tracker_FormElement_Field_ArtifactLinkTestVersion();
        $f->setReturnValue('isRequired', true);
        
        $rai = new MockTracker_Valid_Rule_ArtifactId();
        $rai->setReturnValue('isValid', true, array('123'));
        $rai->setReturnValue('isValid', true, array('321'));
        $rai->setReturnValue('isValid', true, array('999'));
        $rai->setReturnValue('isValid', false, array('666'));
        $rai->setReturnValue('isValid', false, array('toto'));
        $f->setReturnReference('getRuleArtifactId', $rai);
        
    
        $ids = array(123);
        $cv = new MockTracker_Artifact_ChangesetValue_ArtifactLink();
        $cv->setReturnReference('getArtifactIds', $ids);
        $c = new MockTracker_Artifact_Changeset();
        $c->setReturnReference('getValue', $cv);
        $a = new MockTracker_Artifact();
        $a->setReturnReference('getLastChangeset', $c);
        
        $this->assertTrue($f->isValid($a, array('new_values' => '123')));
        $this->assertFalse($f->isValid($a, array('new_values' => '666')));
        $this->assertFalse($f->isValid($a, array('new_values' => '123, 666')));
        $this->assertFalse($f->isValid($a, array('new_values' => '123,666')));
        $this->assertTrue($f->isValid($a, array('new_values' => '123          ,   321, 999')));
        $this->assertTrue($f->isValid($a, array('new_values' => ''))); // existing values
        $this->assertFalse($f->isValid($a, array('new_values' => '123, toto')));
        $this->assertTrue($f->isValid($a, null));  // existing values
        $this->assertFalse($f->isValid($a, array('new_values' => '', 'removed_values'=> '123')));
    }
    
    function testIsValidRequiredFieldWithoutExistingValues() {
        $f = new Tracker_FormElement_Field_ArtifactLinkTestVersion();
        $f->setReturnValue('isRequired', true);
        
        $rai = new MockTracker_Valid_Rule_ArtifactId();
        $rai->setReturnValue('isValid', true, array('123'));
        $rai->setReturnValue('isValid', true, array('321'));
        $rai->setReturnValue('isValid', true, array('999'));
        $rai->setReturnValue('isValid', false, array('666'));
        $rai->setReturnValue('isValid', false, array('toto'));
        $f->setReturnReference('getRuleArtifactId', $rai);
        
        $ids = array();
        $cv = new MockTracker_Artifact_ChangesetValue_ArtifactLink();
        $cv->setReturnReference('getArtifactIds', $ids);
        $c = new MockTracker_Artifact_Changeset();
        $c->setReturnReference('getValue', $cv);
        $a = new MockTracker_Artifact();
        $a->setReturnReference('getLastChangeset', $c);
        
        $this->assertTrue($f->isValid($a, array('new_values' => '123')));
        $this->assertFalse($f->isValid($a, array('new_values' => '666')));
        $this->assertFalse($f->isValid($a, array('new_values' => '123, 666')));
        $this->assertFalse($f->isValid($a, array('new_values' => '123,666')));
        $this->assertTrue($f->isValid($a, array('new_values' => '123          ,   321, 999')));
        $this->assertFalse($f->isValid($a, array('new_values' => '')));
        $this->assertFalse($f->isValid($a, array('new_values' => '123, toto')));
        $this->assertFalse($f->isValid($a, null));
    }
    
    function testIsValidNotRequiredField() {
        $f = new Tracker_FormElement_Field_ArtifactLinkTestVersion();
        $f->setReturnValue('isRequired', false);
        
        $rai = new MockTracker_Valid_Rule_ArtifactId();
        $f->setReturnReference('getRuleArtifactId', $rai);
        
        $a = new MockTracker_Artifact();
        $this->assertTrue($f->isValid($a, array('new_values' => '')));
        $this->assertTrue($f->isValid($a, null));
    }
    
    function testSoapAvailableValues() {
        $f = new Tracker_FormElement_Field_ArtifactLinkTestVersion();
        $this->assertNull($f->getSoapAvailableValues());
    }
    
    function testGetFieldData() {
        $f = new Tracker_FormElement_Field_ArtifactLinkTestVersion();
        $res = array('new_values' => '1,3,9');
        $this->assertEqual($res, $f->getFieldData('1,3,9'));
    }
    
    function testIsValid_AddsErrorIfARequiredFieldIsAnArrayWithoutNewValues() {
        $f = new Tracker_FormElement_Field_ArtifactLinkTestVersion();
        $f->setReturnValue('isRequired', true);
        
        $a = new MockTracker_Artifact();
        $a->setReturnValue('getLastChangeset', false);
        $this->assertFalse($f->isValid($a, array('new_values' => '')));
        $this->assertTrue($f->hasErrors());

    }
    
    function testIsValid_AddsErrorIfARequiredFieldValueIsAnEmptyString() {
        $f = new Tracker_FormElement_Field_ArtifactLinkTestVersion();
        $f->setReturnValue('isRequired', true);
        
        $a = new MockTracker_Artifact();
        $a->setReturnValue('getLastChangeset', false);
        $this->assertFalse($f->isValid($a, ''));
        $this->assertTrue($f->hasErrors());
    }

}

?>
