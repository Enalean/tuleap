<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 * Copyright (c) Enalean, 2015 - 2018. All Rights Reserved.
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

class Tracker_FormElement_Field_ArtifactLinkTest extends TuleapTestCase {

    public function setUp() {
        parent::setUp();
        $this->setUpGlobalsMockery();

        $this->artifact_factory = \Mockery::spy(\Tracker_ArtifactFactory::class);
        Tracker_ArtifactFactory::setInstance($this->artifact_factory);

        $artifact = \Mockery::spy(\Tracker_Artifact::class);
        $this->changeset = mockery_stub(\Tracker_Artifact_Changeset::class)->getArtifact()->returns($artifact);
    }

    public function tearDown() {
        Tracker_ArtifactFactory::clearInstance();

        parent::tearDown();
    }

    public function testNoDefaultValue() {
        $field = \Mockery::mock(\Tracker_FormElement_Field_ArtifactLink::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $field->shouldReceive('getProperty')->andReturn(null);
        $this->assertFalse($field->hasDefaultValue());
    }

    function testGetChangesetValue() {
        $value_dao = \Mockery::spy(\Tracker_FormElement_Field_Value_ArtifactLinkDao::class);
        stub($value_dao)->searchById()->returnsDar([
            'id' => 123,
            'field_id' => 1,
            'artifact_id' => '999',
            'keyword' => 'bug',
            'group_id' => '102',
            'tracker_id' => '456',
            'nature' => '',
            'last_changeset_id' => '789'
        ]);
        stub($value_dao)->searchReverseLinksById()->returnsEmptyDar();

        $field = \Mockery::mock(\Tracker_FormElement_Field_ArtifactLink::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $field->shouldReceive('getValueDao')->andReturns($value_dao);

        $this->assertIsA($field->getChangesetValue($this->changeset, 123, false), 'Tracker_Artifact_ChangesetValue_ArtifactLink');
    }

    function testGetChangesetValue_doesnt_exist() {
        $value_dao = \Mockery::spy(\Tracker_FormElement_Field_Value_ArtifactLinkDao::class);
        stub($value_dao)->searchById()->returnsEmptyDar();
        stub($value_dao)->searchReverseLinksById()->returnsEmptyDar();

        $field = \Mockery::mock(\Tracker_FormElement_Field_ArtifactLink::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $field->shouldReceive('getValueDao')->andReturns($value_dao);

        $this->assertNotNull($field->getChangesetValue($this->changeset, 123, false));
    }

    function testFetchRawValue() {
        $f = \Mockery::mock(\Tracker_FormElement_Field_ArtifactLink::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $art_ids = array('123, 132, 999');
        $value = \Mockery::spy(\Tracker_Artifact_ChangesetValue_ArtifactLink::class);
        $value->shouldReceive('getArtifactIds')->andReturns($art_ids);
        $this->assertEqual($f->fetchRawValue($value), '123, 132, 999');
    }

    public function testIsValidRequiredFieldWithExistingValues()
    {
        $field = \Mockery::mock(\Tracker_FormElement_Field_ArtifactLink::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $field->shouldReceive('isRequired')->andReturns(true);

        $ids = array(123);
        $cv  = \Mockery::spy(\Tracker_Artifact_ChangesetValue_ArtifactLink::class);
        $cv->shouldReceive('getArtifactIds')->andReturns($ids);
        $c = \Mockery::spy(\Tracker_Artifact_Changeset::class);
        $c->shouldReceive('getValue')->andReturns($cv);

        $artifact = \Mockery::spy(\Tracker_Artifact::class);
        $tracker  = \Mockery::spy(\Tracker::class);
        stub($artifact)->getLastChangeset()->returns($c);
        stub($artifact)->getTracker()->returns($tracker);

        $field->shouldReceive('getLastChangesetValue')->andReturns($cv);

        $this->assertTrue($field->isValidRegardingRequiredProperty($artifact, null));  // existing values
        $this->assertFalse($field->isValidRegardingRequiredProperty($artifact, array('new_values' => '', 'removed_values' => ['123'])));
    }

    public function testIsValidRequiredFieldWithoutExistingValues()
    {
        $field = \Mockery::mock(\Tracker_FormElement_Field_ArtifactLink::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $field->shouldReceive('isRequired')->andReturns(true);

        $ids = array();
        $cv = \Mockery::spy(\Tracker_Artifact_ChangesetValue_ArtifactLink::class);
        $cv->shouldReceive('getArtifactIds')->andReturns($ids);
        $c = \Mockery::spy(\Tracker_Artifact_Changeset::class);
        $c->shouldReceive('getValue')->andReturns($cv);
        $a = \Mockery::spy(\Tracker_Artifact::class);
        $a->shouldReceive('getLastChangeset')->andReturns($c);

        $this->assertFalse($field->isValidRegardingRequiredProperty($a, array('new_values' => '')));
        $this->assertFalse($field->isValidRegardingRequiredProperty($a, null));
    }

    function testSoapAvailableValues() {
        $f = \Mockery::mock(\Tracker_FormElement_Field_ArtifactLink::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $this->assertNull($f->getSoapAvailableValues());
    }

    function testIsValid_AddsErrorIfARequiredFieldIsAnArrayWithoutNewValues() {
        $f = \Mockery::mock(\Tracker_FormElement_Field_ArtifactLink::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $f->shouldReceive('isRequired')->andReturns(true);

        $a = \Mockery::spy(\Tracker_Artifact::class);
        $a->shouldReceive('getLastChangeset')->andReturns(false);
        $this->assertFalse($f->isValidRegardingRequiredProperty($a, array('new_values' => '')));
        $this->assertTrue($f->hasErrors());

    }

    function testIsValid_AddsErrorIfARequiredFieldValueIsAnEmptyString() {
        $f = \Mockery::mock(\Tracker_FormElement_Field_ArtifactLink::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $f->shouldReceive('isRequired')->andReturns(true);

        $a = \Mockery::spy(\Tracker_Artifact::class);
        $a->shouldReceive('getLastChangeset')->andReturns(false);
        $this->assertFalse($f->isValidRegardingRequiredProperty($a, ''));
        $this->assertTrue($f->hasErrors());
    }

    public function itReturnsAnEmptyListWhenThereAreNoValuesInTheChangeset() {
        $field = anArtifactLinkField()->build();
        $changeset = \Mockery::spy(\Tracker_Artifact_Changeset::class);
        $changeset->shouldReceive('getValue')->with($this)->andReturns(null);
        $user = aUser()->build();

        $artifacts = $field->getLinkedArtifacts($changeset, $user);
        $this->assertIdentical(array(), $artifacts);
    }

    public function itReturnsAnEmptyPaginatedListWhenThereAreNoValuesInTheChangeset() {
        $field = anArtifactLinkField()->build();
        $changeset = \Mockery::spy(\Tracker_Artifact_Changeset::class);
        $changeset->shouldReceive('getValue')->with($this)->andReturns(null);
        $user = aUser()->build();

        $sliced = $field->getSlicedLinkedArtifacts($changeset, $user, 10, 0);
        $this->assertIdentical(array(), $sliced->getArtifacts());
        $this->assertEqual(0, $sliced->getTotalSize());
    }

    public function itCreatesAListOfArtifactsBasedOnTheIdsInTheChangesetField() {
        $user = aUser()->build();

        $artifact_1 = \Mockery::spy(\Tracker_Artifact::class);
        stub($artifact_1)->getId()->returns(123);
        stub($artifact_1)->userCanView()->returns(true);
        $artifact_2 = \Mockery::spy(\Tracker_Artifact::class);
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

        $artifact_1 = \Mockery::spy(\Tracker_Artifact::class);
        stub($artifact_1)->getId()->returns(123);
        stub($artifact_1)->userCanView()->returns(true);
        $artifact_2 = \Mockery::spy(\Tracker_Artifact::class);
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

        $artifact_1 = \Mockery::spy(\Tracker_Artifact::class);
        stub($artifact_1)->getId()->returns(123);
        stub($artifact_1)->userCanView()->returns(true);
        $artifact_2 = \Mockery::spy(\Tracker_Artifact::class);
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

        $artifact_1 = \Mockery::spy(\Tracker_Artifact::class);
        stub($artifact_1)->getId()->returns(123);
        stub($artifact_1)->userCanView()->returns(true);
        $artifact_2 = \Mockery::spy(\Tracker_Artifact::class);
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
        $artifact = \Mockery::spy(\Tracker_Artifact::class);
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
        $artifact = \Mockery::spy(\Tracker_Artifact::class);
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

        $artifact_1 = \Mockery::spy(\Tracker_Artifact::class);
        stub($artifact_1)->getId()->returns(123);
        $artifact_2 = \Mockery::spy(\Tracker_Artifact::class);
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

        $artifact_1 = \Mockery::spy(\Tracker_Artifact::class);
        stub($artifact_1)->getId()->returns(123);
        $artifact_2 = \Mockery::spy(\Tracker_Artifact::class);
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

        $artifact_1 = \Mockery::spy(\Tracker_Artifact::class);
        stub($artifact_1)->getId()->returns(123);
        $artifact_2 = \Mockery::spy(\Tracker_Artifact::class);
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

        $artifact_1 = \Mockery::spy(\Tracker_Artifact::class);
        stub($artifact_1)->getId()->returns(123);
        $artifact_2 = \Mockery::spy(\Tracker_Artifact::class);
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
        $changeset_value = \Mockery::spy(\Tracker_Artifact_ChangesetValue_ArtifactLink::class);
        $changeset_value->shouldReceive('getArtifactIds')->andReturns($ids);
        $changeset = \Mockery::spy(\Tracker_Artifact_Changeset::class);
        $changeset->shouldReceive('getValue')->with($field)->andReturns($changeset_value);
        return $changeset;

    }

    private function GivenAnArtifactFactory($artifacts) {
        $factory = \Mockery::spy(\Tracker_ArtifactFactory::class);
        foreach ($artifacts as $a) {
            $factory->shouldReceive('getArtifactById')->with($a->getId())->andReturns($a);
        }
        return $factory;

    }
}

class Tracker_FormElement_Field_ArtifactLink_CatchLinkDirectionTest extends TuleapTestCase {
    
    public function setUp() {
        parent::setUp();
        $this->setUpGlobalsMockery();

        $hierarchy_factory = \Mockery::spy(\Tracker_HierarchyFactory::class);
        Tracker_HierarchyFactory::setInstance($hierarchy_factory);
        $artifact_factory = \Mockery::spy(\Tracker_ArtifactFactory::class);
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

        $this->artifact_123 = \Mockery::spy(\Tracker_Artifact::class);
        stub($this->artifact_123)->getId()->returns(123);
        stub($this->artifact_123)->getTracker()->returns($parent_tracker);
        stub($this->artifact_123)->getTrackerId()->returns($parent_tracker->getId());
        stub($this->artifact_123)->getLastChangeset()->returns(
            mockery_stub(\Tracker_Artifact_Changeset::class)->getId()->returns(1231)
        );

        $this->artifact_124 = \Mockery::spy(\Tracker_Artifact::class);
        stub($this->artifact_124)->getId()->returns(124);
        stub($this->artifact_124)->getTracker()->returns($tracker);
        stub($this->artifact_124)->getTrackerId()->returns($tracker->getId());
        stub($this->artifact_124)->getLastChangeset()->returns(
            mockery_stub(\Tracker_Artifact_Changeset::class)->getId()->returns(1241)
        );

        stub($artifact_factory)->getArtifactById(123)->returns($this->artifact_123);
        stub($artifact_factory)->getArtifactById(124)->returns($this->artifact_124);
        
        $this->all_artifacts = array($this->artifact_123, $this->artifact_124);
        
        $this->field = \Mockery::mock(\Tracker_FormElement_Field_ArtifactLink::class)->makePartial()->shouldAllowMockingProtectedMethods();

        $value_dao = \Mockery::spy(\Tracker_FormElement_Field_Value_ArtifactLinkDao::class);
        stub($this->field)->getValueDao()->returns($value_dao);
        $changeset_value_dao = mockery_stub(\Tracker_Artifact_Changeset_ValueDao::class)->save()->returns($this->new_changeset_value_id);
        stub($this->field)->getChangesetValueDao()->returns($changeset_value_dao);
        stub($this->field)->userCanUpdate()->returns(true);
        stub($this->field)->isValid()->returns(true);
        
        stub($this->field)->getProcessChildrenTriggersCommand()->returns(\Mockery::spy(\Tracker_FormElement_Field_ArtifactLink_ProcessChildrenTriggersCommand::class));
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
        $this->field->shouldReceive('saveValue')->with($this->modified_artifact, $this->new_changeset_value_id, $remaining_submitted_value, null)->once();

        $this->field->saveNewChangeset($this->modified_artifact, $this->old_changeset, $this->new_changeset_id, $this->submitted_value, $this->submitter);
    }

    public function itDoesntFailIfSubmittedValueIsNull() {
        $this->field->shouldReceive('saveValue')->once();

        $this->field->saveNewChangeset($this->modified_artifact, $this->old_changeset, $this->new_changeset_id, null, $this->submitter);
    }

    public function itSavesChangesetInSourceArtifact() {
        expect($this->artifact_123)->linkArtifact($this->modified_artifact_id, $this->submitter)->once();
        stub($this->artifact_123)->linkArtifact()->returns(true);

        $this->field->shouldReceive('saveValue')->once();

        $this->field->saveNewChangeset($this->modified_artifact, $this->old_changeset, $this->new_changeset_id, $this->submitted_value, $this->submitter);
        $this->field->postSaveNewChangeset($this->modified_artifact, $this->submitter, \Mockery::spy(\Tracker_Artifact_Changeset::class));
    }
}

class Tracker_FormElement_Field_ArtifactLink_postSaveNewChangesetTest extends TuleapTestCase {

    public function itExecutesProcessChildrenTriggersCommand() {
        $artifact           = anArtifact()->build();
        $user               = aUser()->build();
        $new_changeset      = \Mockery::spy(\Tracker_Artifact_Changeset::class);
        $previous_changeset = null;
        $command            = \Mockery::spy(\Tracker_FormElement_Field_ArtifactLink_ProcessChildrenTriggersCommand::class);
        $field              = \Mockery::mock(\Tracker_FormElement_Field_ArtifactLink::class)->makePartial()->shouldAllowMockingProtectedMethods();
        stub($field)->getProcessChildrenTriggersCommand()->returns($command);

        expect($command)->execute($artifact, $user, $new_changeset, $previous_changeset)->once();

        $field->postSaveNewChangeset($artifact, $user, $new_changeset, $previous_changeset);
    }
}

class Tracker_FormElement_Field_ArtifactLink_AugmentDataFromRequestTest extends TuleapTestCase {

    public function setUp() {
        parent::setUp();
        $this->setUpGlobalsMockery();
        $this->art_link_id = 555;
        $this->tracker     = \Mockery::spy(\Tracker::class);

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

    public function setUp()
    {
        parent::setUp();
        $this->setUpGlobalsMockery();
        $this->field = \Mockery::mock(\Tracker_FormElement_Field_ArtifactLink::class)->makePartial()->shouldAllowMockingProtectedMethods();

        $this->last_changset_id = 1234;
        $this->artifact = anArtifact()->build();
        $last_changeset = new Tracker_Artifact_Changeset($this->last_changset_id, $this->artifact, '', '', '');
        $this->artifact->setChangesets(array($last_changeset));
    }

    public function itGetValuesFromArtifactChangesetWhenThereIsAnArtifact()
    {
        $this->field->shouldReceive('getChangesetValues')->with($this->last_changset_id)->once()->andReturn([]);

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
        $this->setUpGlobalsMockery();
        $this->field = \Mockery::mock(\Tracker_FormElement_Field_ArtifactLink::class)->makePartial()->shouldAllowMockingProtectedMethods();
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

        $this->field->shouldReceive('getFieldData')->with('55', $artifact)->once()->andReturn('whatever');

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
