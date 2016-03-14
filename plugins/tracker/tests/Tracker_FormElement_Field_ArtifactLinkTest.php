<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 * Copyright (c) Enalean, 2015 - 2016. All Rights Reserved.
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

require_once('bootstrap.php');
require_once 'common/language/BaseLanguage.class.php';

Mock::generate('BaseLanguage');

Mock::generate('Tracker_Artifact');

Mock::generatePartial(
    'Tracker_FormElement_Field_ArtifactLink',
    'Tracker_FormElement_Field_ArtifactLinkTestVersion', 
    array(
        'getValueDao', 
        'isRequired', 
        'getProperty', 
        'getProperties',
        'getDao',
        'getRuleArtifactId',
        'getLastChangesetValue'
    )
);

Mock::generatePartial(
    'Tracker_FormElement_Field_ArtifactLink', 
    'Tracker_FormElement_Field_ArtifactLinkTestVersion_ForImport', 
    array(
        'getValueDao',
        'getDao',
        'getRuleArtifactId'
    )
);

Mock::generate('Tracker_Artifact_Changeset');

Mock::generate('Tracker_Artifact_ChangesetValue_ArtifactLink');

Mock::generate('Tracker_FormElement_Field_Value_ArtifactLinkDao');

require_once 'common/dao/include/DataAccessResult.class.php';
Mock::generate('DataAccessResult');

Mock::generate('Tracker_Valid_Rule_ArtifactId');

require_once 'common/include/Response.class.php';
Mock::generate('Response');

class Tracker_FormElement_Field_ArtifactLinkTest extends TuleapTestCase {
    
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
                                                'nature' => '',
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

        $f->setReturnReference('getLastChangesetValue', $cv);
        
        $this->assertTrue($f->isValid($a, array('new_values' => '123')));
        $this->assertFalse($f->isValid($a, array('new_values' => '666')));
        $this->assertFalse($f->isValid($a, array('new_values' => '123, 666')));
        $this->assertFalse($f->isValid($a, array('new_values' => '123,666')));
        $this->assertTrue($f->isValid($a, array('new_values' => '123          ,   321, 999')));
        $this->assertTrue($f->isValid($a, array('new_values' => ''))); // existing values
        $this->assertFalse($f->isValid($a, array('new_values' => '123, toto')));
        $this->assertTrue($f->isValidRegardingRequiredProperty($a, null));  // existing values
        $this->assertFalse($f->isValidRegardingRequiredProperty($a, array('new_values' => '', 'removed_values'=> '123')));
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
        $this->assertFalse($f->isValidRegardingRequiredProperty($a, array('new_values' => '')));
        $this->assertFalse($f->isValid($a, array('new_values' => '123, toto')));
        $this->assertFalse($f->isValidRegardingRequiredProperty($a, null));
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
    
    function testIsValid_AddsErrorIfARequiredFieldIsAnArrayWithoutNewValues() {
        $f = new Tracker_FormElement_Field_ArtifactLinkTestVersion();
        $f->setReturnValue('isRequired', true);
        
        $a = new MockTracker_Artifact();
        $a->setReturnValue('getLastChangeset', false);
        $this->assertFalse($f->isValidRegardingRequiredProperty($a, array('new_values' => '')));
        $this->assertTrue($f->hasErrors());

    }
    
    function testIsValid_AddsErrorIfARequiredFieldValueIsAnEmptyString() {
        $f = new Tracker_FormElement_Field_ArtifactLinkTestVersion();
        $f->setReturnValue('isRequired', true);
        
        $a = new MockTracker_Artifact();
        $a->setReturnValue('getLastChangeset', false);
        $this->assertFalse($f->isValidRegardingRequiredProperty($a, ''));
        $this->assertTrue($f->hasErrors());
    }
    
    public function itReturnsAnEmptyListWhenThereAreNoValuesInTheChangeset() {
        $field = anArtifactLinkField()->build();
        $changeset = new MockTracker_Artifact_Changeset();
        $changeset->setReturnValue('getValue', null, array($this));
        $user = aUser()->build();
        
        $artifacts = $field->getLinkedArtifacts($changeset, $user);
        $this->assertIdentical(array(), $artifacts);
    }

    public function itReturnsAnEmptyPaginatedListWhenThereAreNoValuesInTheChangeset() {
        $field = anArtifactLinkField()->build();
        $changeset = new MockTracker_Artifact_Changeset();
        $changeset->setReturnValue('getValue', null, array($this));
        $user = aUser()->build();

        $sliced = $field->getSlicedLinkedArtifacts($changeset, $user, 10, 0);
        $this->assertIdentical(array(), $sliced->getArtifacts());
        $this->assertEqual(0, $sliced->getTotalSize());
    }

    public function itCreatesAListOfArtifactsBasedOnTheIdsInTheChangesetField() {
        $user = aUser()->build();
        
        $artifact_1 = mock('Tracker_Artifact');
        stub($artifact_1)->getId()->returns(123);
        stub($artifact_1)->userCanView()->returns(true);
        $artifact_2 = mock('Tracker_Artifact');
        stub($artifact_2)->getId()->returns(345);
        stub($artifact_2)->userCanView()->returns(true);
        
        $field = anArtifactLinkField()->build();
        $field->setArtifactFactory($this->GivenAnArtifactFactory(array($artifact_1, $artifact_2)));
        
        $changeset = $this->GivenAChangesetValueWithArtifactIds($field, array(123, 345));

        $artifacts          = $field->getLinkedArtifacts($changeset, $user);
        $expected_artifacts = array(
            $artifact_1,
            $artifact_2
        );
        $this->assertEqual($expected_artifacts, $artifacts);
    }

    public function itCreatesAPaginatedListOfArtifactsBasedOnTheIdsInTheChangesetField() {
        $user = aUser()->build();

        $artifact_1 = mock('Tracker_Artifact');
        stub($artifact_1)->getId()->returns(123);
        stub($artifact_1)->userCanView()->returns(true);
        $artifact_2 = mock('Tracker_Artifact');
        stub($artifact_2)->getId()->returns(345);
        stub($artifact_2)->userCanView()->returns(true);

        $field = anArtifactLinkField()->build();
        $field->setArtifactFactory($this->GivenAnArtifactFactory(array($artifact_1, $artifact_2)));

        $changeset = $this->GivenAChangesetValueWithArtifactIds($field, array(123, 345));

        $sliced             = $field->getSlicedLinkedArtifacts($changeset, $user, 10, 0);
        $expected_artifacts = array(
            $artifact_1,
            $artifact_2
        );
        $this->assertEqual($expected_artifacts, $sliced->getArtifacts());
        $this->assertEqual(2, $sliced->getTotalSize());
    }

    public function itCreatesAFirstPageOfPaginatedListOfArtifactsBasedOnTheIdsInTheChangesetField() {
        $user = aUser()->build();

        $artifact_1 = mock('Tracker_Artifact');
        stub($artifact_1)->getId()->returns(123);
        stub($artifact_1)->userCanView()->returns(true);
        $artifact_2 = mock('Tracker_Artifact');
        stub($artifact_2)->getId()->returns(345);
        stub($artifact_2)->userCanView()->returns(true);

        $field = anArtifactLinkField()->build();
        $field->setArtifactFactory($this->GivenAnArtifactFactory(array($artifact_1, $artifact_2)));

        $changeset = $this->GivenAChangesetValueWithArtifactIds($field, array(123, 345));

        $sliced             = $field->getSlicedLinkedArtifacts($changeset, $user, 1, 0);
        $expected_artifacts = array(
            $artifact_1
        );
        $this->assertEqual($expected_artifacts, $sliced->getArtifacts());
        $this->assertEqual(2, $sliced->getTotalSize());
    }

    public function itCreatesASecondPageOfPaginatedListOfArtifactsBasedOnTheIdsInTheChangesetField() {
        $user = aUser()->build();

        $artifact_1 = mock('Tracker_Artifact');
        stub($artifact_1)->getId()->returns(123);
        stub($artifact_1)->userCanView()->returns(true);
        $artifact_2 = mock('Tracker_Artifact');
        stub($artifact_2)->getId()->returns(345);
        stub($artifact_2)->userCanView()->returns(true);

        $field = anArtifactLinkField()->build();
        $field->setArtifactFactory($this->GivenAnArtifactFactory(array($artifact_1, $artifact_2)));

        $changeset = $this->GivenAChangesetValueWithArtifactIds($field, array(123, 345));

        $sliced             = $field->getSlicedLinkedArtifacts($changeset, $user, 1, 1);
        $expected_artifacts = array(
            $artifact_2
        );
        $this->assertEqual($expected_artifacts, $sliced->getArtifacts());
        $this->assertEqual(2, $sliced->getTotalSize());
    }

    public function itIgnoresIdsThatDontExist() {
        $user     = aUser()->build();
        $artifact = mock('Tracker_Artifact');
        stub($artifact)->getId()->returns(123);
        stub($artifact)->userCanView()->returns(true);
        
        $field = anArtifactLinkField()->build();
        $field->setArtifactFactory($this->GivenAnArtifactFactory(array($artifact)));
        
        $non_existing_id = 666;
        $changeset = $this->GivenAChangesetValueWithArtifactIds($field, array(123, $non_existing_id));
        
        $artifacts          = $field->getLinkedArtifacts($changeset, $user);
        $expected_artifacts = array($artifact);
        $this->assertEqual($expected_artifacts, $artifacts);
    }

    public function itIgnoresInPaginatedListIdsThatDontExist() {
        $user     = aUser()->build();
        $artifact = mock('Tracker_Artifact');
        stub($artifact)->getId()->returns(123);
        stub($artifact)->userCanView()->returns(true);

        $field = anArtifactLinkField()->build();
        $field->setArtifactFactory($this->GivenAnArtifactFactory(array($artifact)));

        $non_existing_id = 666;
        $changeset = $this->GivenAChangesetValueWithArtifactIds($field, array(123, $non_existing_id));

        $sliced             = $field->getSlicedLinkedArtifacts($changeset, $user, 10, 0);
        $expected_artifacts = array($artifact);
        $this->assertEqual($expected_artifacts, $sliced->getArtifacts());
        $this->assertEqual(2, $sliced->getTotalSize());
    }

    public function itReturnsOnlyArtifactsAccessibleByGivenUser() {
        $user = aUser()->build();
        
        $artifact_1 = mock('Tracker_Artifact');
        stub($artifact_1)->getId()->returns(123);
        $artifact_2 = mock('Tracker_Artifact');
        stub($artifact_2)->getId()->returns(345);
        stub($artifact_2)->userCanView($user)->returns(true);
        
        $field = anArtifactLinkField()->build();
        $field->setArtifactFactory($this->GivenAnArtifactFactory(array($artifact_1, $artifact_2)));
        
        $changeset = $this->GivenAChangesetValueWithArtifactIds($field, array(123, 345));

        $artifacts          = $field->getLinkedArtifacts($changeset, $user);
        $expected_artifacts = array(
            $artifact_2
        );
        $this->assertEqual($expected_artifacts, $artifacts);
    }

    public function itReturnsOnlyPaginatedArtifactsAccessibleByGivenUser() {
        $user = aUser()->build();

        $artifact_1 = mock('Tracker_Artifact');
        stub($artifact_1)->getId()->returns(123);
        $artifact_2 = mock('Tracker_Artifact');
        stub($artifact_2)->getId()->returns(345);
        stub($artifact_2)->userCanView($user)->returns(true);

        $field = anArtifactLinkField()->build();
        $field->setArtifactFactory($this->GivenAnArtifactFactory(array($artifact_1, $artifact_2)));

        $changeset = $this->GivenAChangesetValueWithArtifactIds($field, array(123, 345));

        $sliced             = $field->getSlicedLinkedArtifacts($changeset, $user, 10, 0);
        $expected_artifacts = array(
            $artifact_2
        );
        $this->assertEqual($expected_artifacts, $sliced->getArtifacts());
        $this->assertEqual(2, $sliced->getTotalSize());
    }

    public function itReturnsAFirstPageOfOnlyPaginatedArtifactsAccessibleByGivenUser() {
        $user = aUser()->build();

        $artifact_1 = mock('Tracker_Artifact');
        stub($artifact_1)->getId()->returns(123);
        $artifact_2 = mock('Tracker_Artifact');
        stub($artifact_2)->getId()->returns(345);
        stub($artifact_2)->userCanView($user)->returns(true);

        $field = anArtifactLinkField()->build();
        $field->setArtifactFactory($this->GivenAnArtifactFactory(array($artifact_1, $artifact_2)));

        $changeset = $this->GivenAChangesetValueWithArtifactIds($field, array(123, 345));

        $sliced             = $field->getSlicedLinkedArtifacts($changeset, $user, 1, 0);
        $expected_artifacts = array(
        );
        $this->assertEqual($expected_artifacts, $sliced->getArtifacts());
        $this->assertEqual(2, $sliced->getTotalSize());
    }

    public function itReturnsASecondPageOfOnlyPaginatedArtifactsAccessibleByGivenUser() {
        $user = aUser()->build();

        $artifact_1 = mock('Tracker_Artifact');
        stub($artifact_1)->getId()->returns(123);
        $artifact_2 = mock('Tracker_Artifact');
        stub($artifact_2)->getId()->returns(345);
        stub($artifact_2)->userCanView($user)->returns(true);

        $field = anArtifactLinkField()->build();
        $field->setArtifactFactory($this->GivenAnArtifactFactory(array($artifact_1, $artifact_2)));

        $changeset = $this->GivenAChangesetValueWithArtifactIds($field, array(123, 345));

        $sliced             = $field->getSlicedLinkedArtifacts($changeset, $user, 1, 1);
        $expected_artifacts = array(
            $artifact_2
        );
        $this->assertEqual($expected_artifacts, $sliced->getArtifacts());
        $this->assertEqual(2, $sliced->getTotalSize());
    }

    public function itReturnsPaginatedListOfArtifacts() {

    }

    private function GivenAChangesetValueWithArtifactIds($field, $ids) {
        $changeset_value = new MockTracker_Artifact_ChangesetValue_ArtifactLink();
        $changeset_value->setReturnValue('getArtifactIds', $ids);
        $changeset = new MockTracker_Artifact_Changeset();
        $changeset->setReturnValue('getValue', $changeset_value, array($field));
        return $changeset;
        
    }

    private function GivenAnArtifactFactory($artifacts) {
        $factory = mock('Tracker_ArtifactFactory');
        foreach ($artifacts as $a) {
            $factory->setReturnValue('getArtifactById', $a, array($a->getId()));
        }
        return $factory;
        
    }
}

// Special class for test to expose 'isSourceOfAssociation method'
class  Tracker_FormElement_Field_ArtifactLink_TestIsSourceOfAssociation extends  Tracker_FormElement_Field_ArtifactLink {
    public function isSourceOfAssociation(Tracker_Artifact $artifact_to_check, Tracker_Artifact $artifact_reference) {
        return parent::isSourceOfAssociation($artifact_to_check, $artifact_reference);
    }
}

class Tracker_FormElement_Field_ArtifactLink_IsSourceOfAssociationTest extends TuleapTestCase {
    
    public function itIsSourceOfAssociationIfThereIsAHierarchyAndArtifactIsInParentTracker() {
        $release_tracker = aTracker()->withId(123)->build();
        $sprint_tracker  = aTracker()->withId(565)->build();
        
        $release = anArtifact()->withTracker($release_tracker)->build();
        $sprint  = anArtifact()->withTracker($sprint_tracker)->build();
        
        $this->field = TestHelper::getPartialMock('Tracker_FormElement_Field_ArtifactLink_TestIsSourceOfAssociation', array('getTrackerChildrenFromHierarchy'));
        
        stub($this->field)->getTrackerChildrenFromHierarchy($release_tracker)->returns(array($sprint_tracker));
        
        $this->assertTrue($this->field->isSourceOfAssociation($release, $sprint));
    }
}

class Tracker_FormElement_Field_ArtifactLink_CatchLinkDirectionTest extends TuleapTestCase {
    
    public function setUp() {
        parent::setUp();

        $this->modified_artifact_id = 223;
        $this->modified_artifact    = anArtifact()->withId($this->modified_artifact_id)->build();
        $this->old_changeset    = null;
        $this->new_changeset_id = 4444;
        $this->submitted_value  = array('new_values'     => '123, 124', 
                                        'removed_values' => array(345 => array('345'),
                                                                  346 => array('346')));
        $this->submitter        = aUser()->build();
        $this->new_changeset_value_id = 66666;
        
        $this->artifact_123 = stub('Tracker_Artifact')->getId()->returns(123);
        $this->artifact_124 = stub('Tracker_Artifact')->getId()->returns(124);
        
        $this->all_artifacts = array($this->artifact_123, $this->artifact_124);
        
        $this->field = TestHelper::getPartialMock(
            'Tracker_FormElement_Field_ArtifactLink_TestIsSourceOfAssociation', 
            array(
                'isSourceOfAssociation',
                'getArtifactsFromChangesetValue',
                'saveValue',
                'getChangesetValueDao',
                'userCanUpdate',
                'isValid',
                'getProcessChildrenTriggersCommand'
            )
        );
        $changeset_value_dao = stub('Tracker_Artifact_Changeset_ValueDao')->save()->returns($this->new_changeset_value_id);
        stub($this->field)->getChangesetValueDao()->returns($changeset_value_dao);
        stub($this->field)->userCanUpdate()->returns(true);
        stub($this->field)->isValid()->returns(true);
        
        stub($this->field)->isSourceOfAssociation($this->artifact_123, $this->modified_artifact)->returns(true);
        stub($this->field)->isSourceOfAssociation($this->artifact_124, $this->modified_artifact)->returns(false);
        
        stub($this->field)->getArtifactsFromChangesetValue($this->submitted_value, $this->old_changeset)->returns($this->all_artifacts);

        stub($this->field)->getProcessChildrenTriggersCommand()->returns(mock('Tracker_FormElement_Field_ArtifactLink_ProcessChildrenTriggersCommand'));
    }
    
    public function itPostponeSavesChangesetInSourceArtifact() {
        expect($this->artifact_123)->linkArtifact()->never();
        
        // Then update the artifact with other links
        $remaining_submitted_value = array('new_values' => '124',
                                           'removed_values' => array(345 => array('345'),
                                                                     346 => array('346')));
        $this->field->expectOnce('saveValue', array($this->modified_artifact, $this->new_changeset_value_id, $remaining_submitted_value, null));
        
        $this->field->saveNewChangeset($this->modified_artifact, $this->old_changeset, $this->new_changeset_id, $this->submitted_value, $this->submitter);
    }

    public function itSavesChangesetInSourceArtifact() {
        expect($this->artifact_123)->linkArtifact($this->modified_artifact_id, $this->submitter)->once();
        stub($this->artifact_123)->linkArtifact()->returns(true);

        $this->field->saveNewChangeset($this->modified_artifact, $this->old_changeset, $this->new_changeset_id, $this->submitted_value, $this->submitter);
        $this->field->postSaveNewChangeset($this->modified_artifact, $this->submitter, mock('Tracker_Artifact_Changeset'));
    }

    public function itRemovesFromSubmittedValuesArtifactsThatWereUpdatedByDirectionChecking() {
        $submitted_value  = array('new_values' => '123, 124');
        $artifact_id_already_linked = array(123);
        $submitted_value = $this->field->removeArtifactsFromSubmittedValue($submitted_value, $artifact_id_already_linked);
        $this->assertEqual($submitted_value, array('new_values' => '124'));
        
        $submitted_value  = array('new_values' => '');
        $artifact_id_already_linked = array();
        $submitted_value = $this->field->removeArtifactsFromSubmittedValue($submitted_value, $artifact_id_already_linked);
        $this->assertEqual($submitted_value, array('new_values' => ''));
        
        $submitted_value  = array('new_values' => '124, 123, 136');
        $artifact_id_already_linked = array(123);
        $submitted_value = $this->field->removeArtifactsFromSubmittedValue($submitted_value, $artifact_id_already_linked);
        $this->assertEqual($submitted_value, array('new_values' => '124,136'));
        
        $submitted_value  = array('new_values' => '123');
        $artifact_id_already_linked = array(123);
        $submitted_value = $this->field->removeArtifactsFromSubmittedValue($submitted_value, $artifact_id_already_linked);
        $this->assertEqual($submitted_value, array('new_values' => ''));
        
        $submitted_value  = array('new_values' => '124, 123');
        $artifact_id_already_linked = array(123);
        $submitted_value = $this->field->removeArtifactsFromSubmittedValue($submitted_value, $artifact_id_already_linked);
        $this->assertEqual($submitted_value, array('new_values' => '124'));
    }
}

class Tracker_FormElement_Field_ArtifactLink_postSaveNewChangesetTest extends TuleapTestCase {

    public function itExecutesProcessChildrenTriggersCommand() {
        $artifact           = anArtifact()->build();
        $user               = aUser()->build();
        $new_changeset      = mock('Tracker_Artifact_Changeset');
        $previous_changeset = null;
        $command            = mock('Tracker_FormElement_Field_ArtifactLink_ProcessChildrenTriggersCommand');
        $field              = partial_mock('Tracker_FormElement_Field_ArtifactLink', array('getProcessChildrenTriggersCommand'));
        stub($field)->getProcessChildrenTriggersCommand()->returns($command);

        expect($command)->execute($artifact, $user, $new_changeset, $previous_changeset)->once();

        $field->postSaveNewChangeset($artifact, $user, $new_changeset, $previous_changeset);
    }
}

class Tracker_FormElement_Field_ArtifactLink_TestUpdateCrossRef extends Tracker_FormElement_Field_ArtifactLink {
    public function updateCrossReferences($artifact, $values) {
        parent::updateCrossReferences($artifact, $values);
    }
}

class Tracker_FormElement_Field_ArtifactLink_UpdateCrossRefTest extends TuleapTestCase {
    private $reference_manager;
    private $field;
    private $artifact_factory;
    private $current_user_id;

    public function setUp() {
        parent::setUp();

        $this->current_user_id   = 852;
        $this->artifact_factory  = mock('Tracker_ArtifactFactory');
        $this->reference_manager = mock('Tracker_ReferenceManager');

        $this->field = partial_mock(
            'Tracker_FormElement_Field_ArtifactLink_TestUpdateCrossRef',
            array(
                'getArtifactFactory',
                'getTrackerReferenceManager',
                'getCurrentUser'
            )
        );

        $this->user = aUser()->withId($this->current_user_id)->build();

        stub($this->field)->getCurrentUser()->returns($this->user);
        stub($this->field)->getTrackerReferenceManager()->returns($this->reference_manager);
        stub($this->field)->getArtifactFactory()->returns($this->artifact_factory);

        $this->art_567             = anArtifact()->withId(567)->build();
        stub($this->artifact_factory)->getArtifactById(567)->returns($this->art_567);

        $this->source_artifact     = anArtifact()->withId(123)->build();
    }

    public function itAddsACrossReferenceOnTheTargetArtifact() {
        $values  = array(
            'new_values'     => '567',
        );

        expect($this->reference_manager)->insertBetweenTwoArtifacts(
            $this->source_artifact,
            $this->art_567,
            $this->user
        )->once();

        $this->field->updateCrossReferences($this->source_artifact, $values);
    }

    public function itRemoveACrossReferenceFromTheTargetArtifact() {
        $values  = array(
            'new_values'     => '',
            'removed_values' => array(
                567 => array('567')
            )
        );

        expect($this->reference_manager)->removeBetweenTwoArtifacts(
            $this->source_artifact,
            $this->art_567,
            $this->user
        )->once();

        $this->field->updateCrossReferences($this->source_artifact, $values);
    }
}

class Tracker_FormElement_Field_ArtifactLink_AugmentDataFromRequestTest extends TuleapTestCase {

    public function itDoesNothingWhenThereAreNoParentsInRequest() {
        $new_values  = '32';
        $art_link_id = 555;
        $fields_data = array(
            $art_link_id => array(
                'new_values' => $new_values
            )
        );
        $field = anArtifactLinkField()->withId($art_link_id)->build();
        $field->augmentDataFromRequest($fields_data);

        $this->assertEqual($fields_data[$art_link_id]['new_values'], $new_values);
    }

    public function itSetParentHasNewValues() {
        $new_values  = '';
        $parent_id   = '657';
        $art_link_id = 555;
        $fields_data = array(
            $art_link_id => array(
                'new_values' => $new_values,
                'parent'     => $parent_id
            )
        );
        $field = anArtifactLinkField()->withId($art_link_id)->build();
        $field->augmentDataFromRequest($fields_data);

        $this->assertEqual($fields_data[$art_link_id]['new_values'], $parent_id);
    }

    public function itAppendsParentToNewValues() {
        $new_values  = '356';
        $parent_id   = '657';
        $art_link_id = 555;
        $fields_data = array(
            $art_link_id => array(
                'new_values' => $new_values,
                'parent'     => $parent_id
            )
        );
        $field = anArtifactLinkField()->withId($art_link_id)->build();
        $field->augmentDataFromRequest($fields_data);

        $this->assertEqual($fields_data[$art_link_id]['new_values'], "$new_values,$parent_id");
    }

    public function itDoesntAppendPleaseChooseOption() {
        $new_values  = '356';
        $parent_id   = '';
        $art_link_id = 555;
        $fields_data = array(
            $art_link_id => array(
                'new_values' => $new_values,
                'parent'     => $parent_id
            )
        );
        $field = anArtifactLinkField()->withId($art_link_id)->build();
        $field->augmentDataFromRequest($fields_data);

        $this->assertEqual($fields_data[$art_link_id]['new_values'], $new_values);
    }

    public function itDoesntAppendCreateNewOption() {
        $new_values  = '356';
        $parent_id   = '-1';
        $art_link_id = 555;
        $fields_data = array(
            $art_link_id => array(
                'new_values' => $new_values,
                'parent'     => $parent_id
            )
        );
        $field = anArtifactLinkField()->withId($art_link_id)->build();
        $field->augmentDataFromRequest($fields_data);

        $this->assertEqual($fields_data[$art_link_id]['new_values'], $new_values);
    }
}

class Tracker_FormElement_Field_ArtifactLink_getFieldData extends TuleapTestCase {

    public function setUp() {
        parent::setUp();
        $this->field = partial_mock('Tracker_FormElement_Field_ArtifactLink', array('getChangesetValues'));

        $this->last_changset_id = 1234;
        $this->artifact = anArtifact()->build();
        $last_changeset = new Tracker_Artifact_Changeset($this->last_changset_id, $this->artifact, '', '', '');
        $this->artifact->setChangesets(array($last_changeset));
    }

    public function itGetValuesFromArtifactChangesetWhenThereIsAnArtifact() {
        expect($this->field)->getChangesetValues($this->last_changset_id)->once();
        stub($this->field)->getChangesetValues()->returns(array());

        $this->field->getFieldData('55', $this->artifact);
    }

    public function itDoesntFetchValuesWhenNoArtifactGiven() {
        expect($this->field)->getChangesetValues($this->last_changset_id)->never();

        $this->field->getFieldData('55');
    }

    public function itOnlyAddNewValuesWhenNoArifactGiven() {
        $this->assertEqual(
            $this->field->getFieldData('55'),
            array('new_values' => '55', 'removed_values' => array())
        );
    }

    public function itAddsOneValue() {
        stub($this->field)->getChangesetValues()->returns(array());
        $this->assertEqual(
            $this->field->getFieldData('55', $this->artifact),
            array('new_values' => '55', 'removed_values' => array())
        );
    }

    public function itAddsTwoNewValues() {
        stub($this->field)->getChangesetValues()->returns(array());
        $this->assertEqual(
            $this->field->getFieldData('55, 66', $this->artifact),
            array('new_values' => '55,66', 'removed_values' => array())
        );
    }

    public function itIgnoresAddOfArtifactThatAreAlreadyLinked() {
        stub($this->field)->getChangesetValues()->returns(array(
            new Tracker_ArtifactLinkInfo(55, '', '', '', '', '')
        ));

        $this->assertEqual(
            $this->field->getFieldData('55, 66', $this->artifact),
            array('new_values' => '66', 'removed_values' => array())
        );
    }

    public function itRemovesAllExistingArtifactLinks() {
        stub($this->field)->getChangesetValues()->returns(array(
            new Tracker_ArtifactLinkInfo(55, '', '', '', '', ''),
            new Tracker_ArtifactLinkInfo(66, '', '', '', '', ''),
        ));

        $this->assertEqual(
            $this->field->getFieldData('', $this->artifact),
            array('new_values' => '', 'removed_values' => array(55 => array('55'), 66 => array('66')))
        );
    }

    public function itRemovesFirstArtifactLink() {
        stub($this->field)->getChangesetValues()->returns(array(
            new Tracker_ArtifactLinkInfo(55, '', '', '', '', ''),
            new Tracker_ArtifactLinkInfo(66, '', '', '', '', ''),
            new Tracker_ArtifactLinkInfo(77, '', '', '', '', ''),
        ));

        $this->assertEqual(
            $this->field->getFieldData('66,77', $this->artifact),
            array('new_values' => '', 'removed_values' => array(55 => array('55')))
        );
    }

    public function itRemovesMiddleArtifactLink() {
        stub($this->field)->getChangesetValues()->returns(array(
            new Tracker_ArtifactLinkInfo(55, '', '', '', '', ''),
            new Tracker_ArtifactLinkInfo(66, '', '', '', '', ''),
            new Tracker_ArtifactLinkInfo(77, '', '', '', '', ''),
        ));

        $this->assertEqual(
            $this->field->getFieldData('55,77', $this->artifact),
            array('new_values' => '', 'removed_values' => array(66 => array('66')))
        );
    }

    public function itRemovesLastArtifactLink() {
        stub($this->field)->getChangesetValues()->returns(array(
            new Tracker_ArtifactLinkInfo(55, '', '', '', '', ''),
            new Tracker_ArtifactLinkInfo(66, '', '', '', '', ''),
            new Tracker_ArtifactLinkInfo(77, '', '', '', '', ''),
        ));

        $this->assertEqual(
            $this->field->getFieldData('55,66', $this->artifact),
            array('new_values' => '', 'removed_values' => array(77 => array('77')))
        );
    }

    public function itAddsAndRemovesInOneCall() {
        stub($this->field)->getChangesetValues()->returns(array(
            new Tracker_ArtifactLinkInfo(55, '', '', '', '', ''),
            new Tracker_ArtifactLinkInfo(66, '', '', '', '', ''),
            new Tracker_ArtifactLinkInfo(77, '', '', '', '', ''),
        ));

        $this->assertEqual(
            $this->field->getFieldData('55,66,88', $this->artifact),
            array('new_values' => '88', 'removed_values' => array(77 => array('77')))
        );
    }
}

class Tracker_FormElement_Field_ArtifactLink_getFieldDataFromSoapValue extends TuleapTestCase {
    
    public function setUp() {
        parent::setUp();
        $this->field = partial_mock('Tracker_FormElement_Field_ArtifactLink', array('getFieldData'));
    }

    public function itPassesArtifactToGetFieldData() {
        $artifact = anArtifact()->build();

        $soap_value = (object) array(
            'field_name'  => '',
            'field_label' => '',
            'field_value' => (object) array(
                'value' => '55'
            )
        );

        expect($this->field)->getFieldData('55', $artifact)->once();
        stub($this->field)->getFieldData()->returns('whatever');

        $this->assertEqual($this->field->getFieldDataFromSoapValue($soap_value, $artifact), 'whatever');
    }
}

class Tracker_FormElement_Field_ArtifactLink_Testable extends Tracker_FormElement_Field_ArtifactLink {
    public function saveValue($artifact, $changeset_value_id, $value, Tracker_Artifact_ChangesetValue $previous_changesetvalue = null) {
        return parent::saveValue($artifact, $changeset_value_id, $value, $previous_changesetvalue);
    }
}

class Tracker_FormElement_Field_ArtifactLink_SaveValue extends TuleapTestCase {

    /** @var Tracker_FormElement_Field_ArtifactLink_Testable */
    private $artifact_link;

    /** @var Tracker_ReferenceManager */
    private $tracker_referencer_manager;

    /** @var Tracker_Artifact */
    private $some_artifact;

    /** @var Tracker_Artifact */
    private $other_artifact;

    /** @var Tracker_ArtifactFactory */
    private $artifact_factory;

    /** @var Tracker_Artifact_ChangesetValue_ArtifactLink */
    private $previous_changesetvalue;

    public function setUp() {
        parent::setUp();

        $this->artifact_link = partial_mock(
            'Tracker_FormElement_Field_ArtifactLink_Testable',
            array(
                'getValueDao',
                'getArtifactFactory',
                'getArtifactsByArtifactIdList',
                'getTrackerReferenceManager',
                'getCurrentUser',
                'getTracker',
            )
        );

        $this->tracker_referencer_manager = mock('Tracker_ReferenceManager');
        stub($this->artifact_link)->getTrackerReferenceManager()->returns($this->tracker_referencer_manager);

        $this->tracker       = stub('Tracker')->getId()->returns(102);
        $this->tracker_child = stub('Tracker')->getId()->returns(101);

        stub($this->tracker)->getChildren()->returns(array($this->tracker_child));
        stub($this->tracker_child)->getChildren()->returns(array());

        $this->some_artifact = mock('Tracker_Artifact');
        stub($this->some_artifact)->getId()->returns(456);
        stub($this->some_artifact)->getTracker()->returns($this->tracker_child);

        $this->other_artifact = mock('Tracker_Artifact');
        stub($this->other_artifact)->getId()->returns(457);
        stub($this->other_artifact)->getTracker()->returns($this->tracker_child);

        $this->artifact_factory = mock('Tracker_ArtifactFactory');
        $this->dao              = mock('Tracker_FormElement_Field_Value_ArtifactLinkDao');

        stub($this->artifact_link)->getArtifactFactory()->returns($this->artifact_factory);
        stub($this->artifact_link)->getValueDao()->returns($this->dao);

        $this->previous_changesetvalue = mock('Tracker_Artifact_ChangesetValue_ArtifactLink');
        stub($this->previous_changesetvalue)->getArtifactIds()->returns(array(36));
    }

    public function tearDown() {
        parent::tearDown();

        unset($this->artifact_link);
        unset($this->tracker_referencer_manager);
        unset($this->some_artifact);
        unset($this->artifact_factory);
        unset($this->previous_changesetvalue);
    }

    public function itRemovesACrossReference() {;
        $artifact_to_unlink = $this->some_artifact;
        $artifact           = mock('Tracker_Artifact');
        $changeset_value_id = 56;
        $value = array(
            'new_values' => '',
            'removed_values' => array(
                36 => 1
            )
        );

        stub($this->artifact_link)->getTracker()->returns($this->tracker);
        stub($this->artifact_factory)->getArtifactsByArtifactIdList(array())->at(0)->returns(array());
        stub($this->artifact_factory)->getArtifactsByArtifactIdList(array(36))->at(1)->returns(array($artifact_to_unlink));
        
        expect($this->tracker_referencer_manager)->removeBetweenTwoArtifacts()->once();
        expect($this->dao)->create()->never();

        $this->artifact_link->saveValue($artifact, $changeset_value_id, $value, $this->previous_changesetvalue);
    }

    public function itAddsACrossReference() {
        $artifact_to_link   = $this->some_artifact;
        $artifact           = stub('Tracker_Artifact')->getTracker()->returns($this->tracker_child);
        $changeset_value_id = 56;

        $value = array(
            'new_values' => 36,
            'removed_values' => array()
        );

        stub($this->dao)->create()->returns(true);
        stub($this->artifact_link)->getTracker()->returns($this->tracker);
        stub($this->artifact_factory)->getArtifactsByArtifactIdList(array(36))->at(0)->returns(array($artifact_to_link));
        stub($this->artifact_factory)->getArtifactsByArtifactIdList(array(36))->at(1)->returns(array());

        expect($this->tracker_referencer_manager)->insertBetweenTwoArtifacts()->once();
        expect($this->dao)->create()->once();

        $this->artifact_link->saveValue($artifact, $changeset_value_id, $value, $this->previous_changesetvalue);
    }

    public function itCallsOnlyOneTimeCreateInDBIfAllArtifactsAreInTheSameTracker() {
        $artifact_to_link   = array($this->some_artifact, $this->other_artifact);
        $artifact           = mock('Tracker_Artifact');
        $changeset_value_id = 56;
         $value = array(
            'new_values' => '36,37',
            'removed_values' => array()
        );

        stub($this->artifact_link)->getTracker()->returns($this->tracker);
        stub($this->artifact_factory)->getArtifactsByArtifactIdList(array(36,37))->at(0)->returns($artifact_to_link);
        stub($this->artifact_factory)->getArtifactsByArtifactIdList(array(36,37))->at(1)->returns(array());

        expect($this->dao)->create()->once();

        $this->artifact_link->saveValue($artifact, $changeset_value_id, $value, $this->previous_changesetvalue);
    }

    public function itUsesArtifactLinkNature() {
        $artifact           = mock('Tracker_Artifact');
        $artifact_to_link   = array($this->some_artifact, $this->other_artifact);

        $changeset_value_id = 56;
        $value = array(
            'new_values' => '36,37',
            'removed_values' => array()
        );

        stub($this->artifact_link)->getTracker()->returns($this->tracker);
        stub($this->artifact_factory)->getArtifactsByArtifactIdList(array(36,37))->at(0)->returns($artifact_to_link);
        stub($this->artifact_factory)->getArtifactsByArtifactIdList(array(36,37))->at(1)->returns(array());

        expect($this->dao)->create('*', '_is_child', '*', '*', '*')->once();

        $this->artifact_link->saveValue($artifact, $changeset_value_id, $value, $this->previous_changesetvalue);
    }

    public function itUsesDefaultArtifactLinkNature() {
        $artifact           = mock('Tracker_Artifact');
        $artifact_to_link   = array($this->some_artifact, $this->other_artifact);

        $changeset_value_id = 56;
        $value = array(
            'new_values' => '36,37',
            'removed_values' => array()
        );

        stub($this->artifact_link)->getTracker()->returns($this->tracker_child);
        stub($this->artifact_factory)->getArtifactsByArtifactIdList(array(36,37))->at(0)->returns($artifact_to_link);
        stub($this->artifact_factory)->getArtifactsByArtifactIdList(array(36,37))->at(1)->returns(array());

        expect($this->dao)->create('*', NULL, '*', '*', '*')->once();

        $this->artifact_link->saveValue($artifact, $changeset_value_id, $value, $this->previous_changesetvalue);
    }
}

class Tracker_FormElement_Field_ArtifactLink_RESTTests extends TuleapTestCase {

    public function itThrowsAnExceptionWhenReturningValueIndexedByFieldName() {
        $field = new Tracker_FormElement_Field_ArtifactLink(
            1,
            101,
            null,
            'field_artlink',
            'Field ArtLink',
            '',
            1,
            'P',
            true,
            '',
            1
        );

        $this->expectException('Tracker_FormElement_RESTValueByField_NotImplementedException');

        $value = 'some_value';

        $field->getFieldDataFromRESTValueByField($value);
    }
}
