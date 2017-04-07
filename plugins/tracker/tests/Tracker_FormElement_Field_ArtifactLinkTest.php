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

use Tuleap\Tracker\FormElement\Field\ArtifactLink\Nature\NaturePresenterFactory;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Nature\NaturePresenter;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Nature\NatureDao;

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
        'getLastChangesetValue',
        'getNaturePresenterFactory',
        'getArtifactFactory'
    )
);

Mock::generatePartial(
    'Tracker_FormElement_Field_ArtifactLink', 
    'Tracker_FormElement_Field_ArtifactLinkTestVersion_ForImport', 
    array(
        'getValueDao',
        'getArtifactFactory',
        'getDao'
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

    public function setUp() {
        parent::setUp();

        $this->artifact_factory = mock('Tracker_ArtifactFactory');
        Tracker_ArtifactFactory::setInstance($this->artifact_factory);

        $artifact = mock('Tracker_Artifact');
        $this->changeset = stub('Tracker_Artifact_Changeset')->getArtifact()->returns($artifact);
    }

    public function tearDown() {
        Tracker_ArtifactFactory::clearInstance();

        parent::tearDown();
    }

    public function testNoDefaultValue() {
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
        stub($value_dao)->searchReverseLinksById()->returnsEmptyDar();

        $field = new Tracker_FormElement_Field_ArtifactLinkTestVersion();
        $field->setReturnReference('getValueDao', $value_dao);

        $this->assertIsA($field->getChangesetValue($this->changeset, 123, false), 'Tracker_Artifact_ChangesetValue_ArtifactLink');
    }

    function testGetChangesetValue_doesnt_exist() {
        $value_dao = new MockTracker_FormElement_Field_Value_ArtifactLinkDao();
        $dar = new MockDataAccessResult();
        $dar->setReturnValue('getRow', false);
        $value_dao->setReturnReference('searchById', $dar);
        stub($value_dao)->searchReverseLinksById()->returnsEmptyDar();

        $field = new Tracker_FormElement_Field_ArtifactLinkTestVersion();
        $field->setReturnReference('getValueDao', $value_dao);

        $this->assertNotNull($field->getChangesetValue($this->changeset, 123, false));
    }

    function testFetchRawValue() {
        $f = new Tracker_FormElement_Field_ArtifactLinkTestVersion();
        $art_ids = array('123, 132, 999');
        $value = new MockTracker_Artifact_ChangesetValue_ArtifactLink();
        $value->setReturnReference('getArtifactIds', $art_ids);
        $this->assertEqual($f->fetchRawValue($value), '123, 132, 999');
    }

    public function testIsValidRequiredFieldWithExistingValues()
    {
        $field = new Tracker_FormElement_Field_ArtifactLinkTestVersion();
        $field->setReturnValue('isRequired', true);

        $ids = array(123);
        $cv  = new MockTracker_Artifact_ChangesetValue_ArtifactLink();
        $cv->setReturnReference('getArtifactIds', $ids);
        $c = new MockTracker_Artifact_Changeset();
        $c->setReturnReference('getValue', $cv);

        $artifact = mock('Tracker_Artifact');
        $tracker  = mock('Tracker');
        stub($artifact)->getLastChangeset()->returns($c);
        stub($artifact)->getTracker()->returns($tracker);

        $field->setReturnReference('getLastChangesetValue', $cv);

        $this->assertTrue($field->isValidRegardingRequiredProperty($artifact, null));  // existing values
        $this->assertFalse($field->isValidRegardingRequiredProperty($artifact, array('new_values' => '', 'removed_values' => '123')));
    }

    public function testIsValidRequiredFieldWithoutExistingValues()
    {
        $field = new Tracker_FormElement_Field_ArtifactLinkTestVersion();
        $field->setReturnValue('isRequired', true);

        $ids = array();
        $cv = new MockTracker_Artifact_ChangesetValue_ArtifactLink();
        $cv->setReturnReference('getArtifactIds', $ids);
        $c = new MockTracker_Artifact_Changeset();
        $c->setReturnReference('getValue', $cv);
        $a = new MockTracker_Artifact();
        $a->setReturnReference('getLastChangeset', $c);

        $this->assertFalse($field->isValidRegardingRequiredProperty($a, array('new_values' => '')));
        $this->assertFalse($field->isValidRegardingRequiredProperty($a, null));
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

class Tracker_FormElement_Field_ArtifactLink_CatchLinkDirectionTest extends TuleapTestCase {
    
    public function setUp() {
        parent::setUp();

        $hierarchy_factory = mock('Tracker_HierarchyFactory');
        Tracker_HierarchyFactory::setInstance($hierarchy_factory);
        $artifact_factory = mock('Tracker_ArtifactFactory');
        Tracker_ArtifactFactory::setInstance($artifact_factory);

        $tracker        = aTracker()->withId(10)->build();
        $parent_tracker = aTracker()->withId(11)->build();
        stub($hierarchy_factory)->getChildren($parent_tracker->getId())->returns(array($tracker));
        stub($hierarchy_factory)->getChildren($tracker->getId())->returns(array());

        $this->modified_artifact_id = 223;
        $this->modified_artifact    = anArtifact()->withId($this->modified_artifact_id)->withTracker($tracker)->build();
        $this->old_changeset    = null;
        $this->new_changeset_id = 4444;
        $this->submitted_value  = array('new_values'     => '123, 124', 
                                        'removed_values' => array(345 => array('345'),
                                                                  346 => array('346')));
        $this->submitter        = aUser()->build();
        $this->new_changeset_value_id = 66666;

        $this->artifact_123 = mock('Tracker_Artifact');
        stub($this->artifact_123)->getId()->returns(123);
        stub($this->artifact_123)->getTracker()->returns($parent_tracker);
        stub($this->artifact_123)->getTrackerId()->returns($parent_tracker->getId());
        stub($this->artifact_123)->getLastChangeset()->returns(
            stub('Tracker_Artifact_Changeset')->getId()->returns(1231)
        );

        $this->artifact_124 = mock('Tracker_Artifact');
        stub($this->artifact_124)->getId()->returns(124);
        stub($this->artifact_124)->getTracker()->returns($tracker);
        stub($this->artifact_124)->getTrackerId()->returns($tracker->getId());
        stub($this->artifact_124)->getLastChangeset()->returns(
            stub('Tracker_Artifact_Changeset')->getId()->returns(1241)
        );

        stub($artifact_factory)->getArtifactById(123)->returns($this->artifact_123);
        stub($artifact_factory)->getArtifactById(124)->returns($this->artifact_124);
        
        $this->all_artifacts = array($this->artifact_123, $this->artifact_124);
        
        $this->field = TestHelper::getPartialMock(
            'Tracker_FormElement_Field_ArtifactLink',
            array(
                'getArtifactsFromChangesetValue',
                'saveValue',
                'getValueDao',
                'getChangesetValueDao',
                'userCanUpdate',
                'isValid',
                'getProcessChildrenTriggersCommand'
            )
        );

        $value_dao = mock('Tracker_FormElement_Field_Value_ArtifactLinkDao');
        stub($this->field)->getValueDao()->returns($value_dao);
        $changeset_value_dao = stub('Tracker_Artifact_Changeset_ValueDao')->save()->returns($this->new_changeset_value_id);
        stub($this->field)->getChangesetValueDao()->returns($changeset_value_dao);
        stub($this->field)->userCanUpdate()->returns(true);
        stub($this->field)->isValid()->returns(true);
        
        stub($this->field)->getProcessChildrenTriggersCommand()->returns(mock('Tracker_FormElement_Field_ArtifactLink_ProcessChildrenTriggersCommand'));
    }

    public function tearDown() {
        Tracker_HierarchyFactory::clearInstance();
        Tracker_ArtifactFactory::clearInstance();
        parent::tearDown();
    }
    
    public function itPostponeSavesChangesetInSourceArtifact() {
        expect($this->artifact_123)->linkArtifact()->never();
        
        // Then update the artifact with other links
        $remaining_submitted_value = array(
            'new_values' => '123, 124',
            'removed_values' => array(
                345 => array('345'),
                346 => array('346')
            ),
            'list_of_artifactlinkinfo' =>
            array(
                124 => Tracker_ArtifactLinkInfo::buildFromArtifact($this->artifact_124, '')
            )
        );
        $this->field->expectOnce('saveValue', array($this->modified_artifact, $this->new_changeset_value_id, $remaining_submitted_value, null));
        
        $this->field->saveNewChangeset($this->modified_artifact, $this->old_changeset, $this->new_changeset_id, $this->submitted_value, $this->submitter);
    }

    public function itDoesntFailIfSubmittedValueIsNull() {
        $this->field->saveNewChangeset($this->modified_artifact, $this->old_changeset, $this->new_changeset_id, null, $this->submitter);
    }

    public function itSavesChangesetInSourceArtifact() {
        expect($this->artifact_123)->linkArtifact($this->modified_artifact_id, $this->submitter)->once();
        stub($this->artifact_123)->linkArtifact()->returns(true);

        $this->field->saveNewChangeset($this->modified_artifact, $this->old_changeset, $this->new_changeset_id, $this->submitted_value, $this->submitter);
        $this->field->postSaveNewChangeset($this->modified_artifact, $this->submitter, mock('Tracker_Artifact_Changeset'));
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

class Tracker_FormElement_Field_ArtifactLink_AugmentDataFromRequestTest extends TuleapTestCase {

    public function setUp() {
        $this->art_link_id = 555;
        $this->tracker     = mock('Tracker');

        $this->field = anArtifactLinkField()
                ->withId($this->art_link_id)
                ->withTracker($this->tracker)
                ->build();
    }

    public function itDoesNothingWhenThereAreNoParentsInRequest() {
        $new_values  = '32';
        $fields_data = array(
            $this->art_link_id => array(
                'new_values' => $new_values
            )
        );

        stub($this->tracker)->isProjectAllowedToUseNature()->returns(false);

        $this->field->augmentDataFromRequest($fields_data);

        $this->assertEqual($fields_data[$this->art_link_id]['new_values'], $new_values);
    }

    public function itSetsParentAsNewValues() {
        $new_values  = '';
        $parent_id   = '657';
        $fields_data = array(
            $this->art_link_id => array(
                'new_values' => $new_values,
                'parent'     => $parent_id
            )
        );

        stub($this->tracker)->isProjectAllowedToUseNature()->returns(false);

        $this->field->augmentDataFromRequest($fields_data);

        $this->assertEqual($fields_data[$this->art_link_id]['new_values'], $parent_id);
    }

    public function itAppendsParentToNewValues() {
        $new_values  = '356';
        $parent_id   = '657';
        $fields_data = array(
            $this->art_link_id => array(
                'new_values' => $new_values,
                'parent'     => $parent_id
            )
        );

        stub($this->tracker)->isProjectAllowedToUseNature()->returns(false);

        $this->field->augmentDataFromRequest($fields_data);

        $this->assertEqual($fields_data[$this->art_link_id]['new_values'], "$new_values,$parent_id");
    }

    public function itDoesntAppendPleaseChooseOption() {
        $new_values  = '356';
        $parent_id   = '';
        $fields_data = array(
            $this->art_link_id => array(
                'new_values' => $new_values,
                'parent'     => $parent_id
            )
        );

        stub($this->tracker)->isProjectAllowedToUseNature()->returns(false);

        $this->field->augmentDataFromRequest($fields_data);

        $this->assertEqual($fields_data[$this->art_link_id]['new_values'], $new_values);
    }

    public function itDoesntAppendCreateNewOption() {
        $new_values  = '356';
        $parent_id   = '-1';
        $fields_data = array(
            $this->art_link_id => array(
                'new_values' => $new_values,
                'parent'     => $parent_id
            )
        );

        stub($this->tracker)->isProjectAllowedToUseNature()->returns(false);

        $this->field->augmentDataFromRequest($fields_data);

        $this->assertEqual($fields_data[$this->art_link_id]['new_values'], $new_values);
    }

    public function itAddsLinkWithNature() {
        $new_values  = '356';
        $nature      = '_is_child';
        $fields_data = array(
            $this->art_link_id => array(
                'new_values' => $new_values,
                'nature'     => $nature
            )
        );

        stub($this->tracker)->isProjectAllowedToUseNature()->returns(true);

        $this->field->augmentDataFromRequest($fields_data);

        $this->assertEqual($fields_data[$this->art_link_id]['natures'], array('356' => '_is_child'));
    }

    public function itReturnsEmptyArrayIfNoParentAndNoNewValues() {
        $fields_data = array();

        stub($this->tracker)->isProjectAllowedToUseNature()->returns(true);

        $this->field->augmentDataFromRequest($fields_data);

        $this->assertEqual(
            $fields_data,
            array()
        );
    }
}

class Tracker_FormElement_Field_ArtifactLink_getFieldData extends TuleapTestCase
{
    /**
     * @var Tracker_FormElement_Field_ArtifactLink
     */
    private $field;

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

    public function itOnlyAddNewValuesWhenNoArifactGiven()
    {
        $this->assertEqual(
            $this->field->getFieldData('55'),
            array('new_values' => '55', 'removed_values' => array(), 'natures' => array())
        );
    }

    public function itAddsOneValue()
    {
        stub($this->field)->getChangesetValues()->returns(array());
        $this->assertEqual(
            $this->field->getFieldData('55', $this->artifact),
            array('new_values' => '55', 'removed_values' => array(), 'natures' => array())
        );
    }

    public function itAddsTwoNewValues()
    {
        stub($this->field)->getChangesetValues()->returns(array());
        $this->assertEqual(
            $this->field->getFieldData('55, 66', $this->artifact),
            array('new_values' => '55,66', 'removed_values' => array(), 'natures' => array())
        );
    }

    public function itAddsTwoNewValuesWithNatures()
    {
        stub($this->field)->getChangesetValues()->returns(array());

        $new_values = array(
            "links" => array(
                array('id' =>'55', 'type' => '_is_child'),
                array('id' =>'66', 'type' => 'custom'),
                array('id' =>'77', 'type' => '')
            )
        );

        $this->assertEqual(
            $this->field->getFieldDataFromRESTValue($new_values, $this->artifact),
            array(
                'new_values' => '55,66,77',
                'removed_values' => array(),
                'natures' => array(
                    '55' => '_is_child',
                    '66' => 'custom',
                    '77' => '',
                )
            )
        );
    }

    public function itIgnoresAddOfArtifactThatAreAlreadyLinked()
    {
        stub($this->field)->getChangesetValues()->returns(
            array(
                new Tracker_ArtifactLinkInfo(55, '', '', '', '', '')
            )
        );

        $this->assertEqual(
            $this->field->getFieldData('55, 66', $this->artifact),
            array('new_values' => '66', 'removed_values' => array(), 'natures' => array())
        );
    }

    public function itRemovesAllExistingArtifactLinks()
    {
        stub($this->field)->getChangesetValues()->returns(
            array(
                new Tracker_ArtifactLinkInfo(55, '', '', '', '', ''),
                new Tracker_ArtifactLinkInfo(66, '', '', '', '', ''),
            )
        );

        $this->assertEqual(
            $this->field->getFieldData('', $this->artifact),
            array('new_values'     => '',
                  'removed_values' => array(55 => array('55'), 66 => array('66')),
                  'natures'        => array()
            )
        );
    }

    public function itRemovesFirstArtifactLink()
    {
        stub($this->field)->getChangesetValues()->returns(
            array(
                new Tracker_ArtifactLinkInfo(55, '', '', '', '', ''),
                new Tracker_ArtifactLinkInfo(66, '', '', '', '', ''),
                new Tracker_ArtifactLinkInfo(77, '', '', '', '', ''),
            )
        );

        $this->assertEqual(
            $this->field->getFieldData('66,77', $this->artifact),
            array('new_values' => '', 'removed_values' => array(55 => array('55')), 'natures' => array())
        );
    }

    public function itRemovesMiddleArtifactLink()
    {
        stub($this->field)->getChangesetValues()->returns(
            array(
                new Tracker_ArtifactLinkInfo(55, '', '', '', '', ''),
                new Tracker_ArtifactLinkInfo(66, '', '', '', '', ''),
                new Tracker_ArtifactLinkInfo(77, '', '', '', '', ''),
            )
        );

        $this->assertEqual(
            $this->field->getFieldData('55,77', $this->artifact),
            array('new_values' => '', 'removed_values' => array(66 => array('66')), 'natures' => array())
        );
    }

    public function itRemovesLastArtifactLink()
    {
        stub($this->field)->getChangesetValues()->returns(
            array(
                new Tracker_ArtifactLinkInfo(55, '', '', '', '', ''),
                new Tracker_ArtifactLinkInfo(66, '', '', '', '', ''),
                new Tracker_ArtifactLinkInfo(77, '', '', '', '', ''),
            )
        );

        $this->assertEqual(
            $this->field->getFieldData('55,66', $this->artifact),
            array('new_values' => '', 'removed_values' => array(77 => array('77')), 'natures' => array())
        );
    }

    public function itAddsAndRemovesInOneCall()
    {
        stub($this->field)->getChangesetValues()->returns(
            array(
                new Tracker_ArtifactLinkInfo(55, '', '', '', '', ''),
                new Tracker_ArtifactLinkInfo(66, '', '', '', '', ''),
                new Tracker_ArtifactLinkInfo(77, '', '', '', '', ''),
            )
        );

        $this->assertEqual(
            $this->field->getFieldData('55,66,88', $this->artifact),
            array('new_values' => '88', 'removed_values' => array(77 => array('77')), 'natures' => array())
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
