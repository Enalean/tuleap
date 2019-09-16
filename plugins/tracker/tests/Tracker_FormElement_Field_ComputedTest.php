<?php
/**
 * Copyright (c) Enalean, 2012 - Present. All Rights Reserved.
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

use Tuleap\Tracker\DAO\ComputedDao;
use Tuleap\Tracker\FormElement\ComputedFieldCalculator;
use Tuleap\Tracker\FormElement\Field\File\CreatedFileURLMapping;
use Tuleap\Tracker\FormElement\FieldCalculator;

require_once('bootstrap.php');

class Tracker_FormElement_Field_Computed_StorableValue extends TuleapTestCase
{
    private $field;
    private $artifact;
    private $old_changeset;
    private $new_changeset_id;
    private $submitter;
    private $value_dao;
    private $new_changeset_value_id;
    /**
     * @var \Mockery\MockInterface|CreatedFileURLMapping
     */
    private $url_mapping;

    public function setUp()
    {
        $this->field = TestHelper::getPartialMock(
            'Tracker_FormElement_Field_Computed',
            array(
                'getProperty',
                'getDao',
                'getChangesetValueDao',
                'userCanUpdate',
                'isValid',
                'getValueDao',
                'getCurrentUser'
            )
        );

        $tracker                = aTracker()->withId(10)->build();
        $this->artifact         = aMockArtifact()->withId(123)->withTracker($tracker)->build();
        $this->old_changeset    = null;
        $this->new_changeset_id = 4444;
        $this->submitter        = aUser()->build();
        $this->new_changeset_value_id = 66666;

        $this->url_mapping = \Mockery::mock(CreatedFileURLMapping::class);

        $this->value_dao = mock(ComputedDao::class);
        stub($this->field)->getValueDao()->returns($this->value_dao);
        $changeset_value_dao = stub('Tracker_Artifact_Changeset_ValueDao')->save()->returns($this->new_changeset_value_id);
        stub($this->field)->getChangesetValueDao()->returns($changeset_value_dao);
        stub($this->field)->userCanUpdate()->returns(true);
        stub($this->field)->isValid()->returns(true);
        stub($this->field)->getCurrentUser()->returns(mock('PFUser'));
    }

    public function itCanRetrieveManualValuesWhenArrayIsGiven()
    {
        $value = array(
            'manual_value'    => 20,
            'is_autocomputed' => 0
        );
        $this->value_dao->expectOnce('create', array($this->new_changeset_value_id, 20));
        $this->field->saveNewChangeset(
            $this->artifact,
            $this->old_changeset,
            $this->new_changeset_id,
            $value,
            $this->submitter,
            false,
            false,
            $this->url_mapping
        );
    }

    public function itCanRetrieveManualValueWhenDataComesFromJson()
    {
        $value = json_encode(
            $value = array(
                'manual_value'    => 20,
                'is_autocomputed' => 0
            )
        );
        $this->value_dao->expectOnce('create', array($this->new_changeset_value_id, 20));
        $this->field->saveNewChangeset(
            $this->artifact,
            $this->old_changeset,
            $this->new_changeset_id,
            $value,
            $this->submitter,
            false,
            false,
            $this->url_mapping
        );
    }

    public function itRetrieveEmptyValueWhenDataIsIncorrect()
    {
        $value = 'aaa';
        $this->value_dao->expectOnce('create', array($this->new_changeset_value_id, null));
        $this->field->saveNewChangeset(
            $this->artifact,
            $this->old_changeset,
            $this->new_changeset_id,
            $value,
            $this->submitter,
            false,
            false,
            $this->url_mapping
        );
    }
}

class Tracker_FormElement_Field_Computed_HasChanges extends TuleapTestCase
{
    private $artifact;
    private $old_value;
    private $field;

    public function setUp()
    {
        $this->artifact  = mock('Tracker_Artifact');
        $this->old_value = mock('Tuleap\Tracker\Artifact\ChangesetValueComputed');

        $this->field = partial_mock('Tracker_FormElement_Field_Computed', array('getNumeric'));
    }


    public function itDetectsChangeWhenBackToAutocompute()
    {
        stub($this->old_value)->getNumeric()->returns(1.0);
        stub($this->old_value)->isManualValue()->returns(true);
        $submitted_value = array(
            'manual_value'    => '',
            'is_autocomputed' => true
        );

        $this->assertTrue($this->field->hasChanges($this->artifact, $this->old_value, $submitted_value));
    }

    public function itDetectsChangeWhenBackToManualValue()
    {
        stub($this->old_value)->getNumeric()->returns(null);
        stub($this->old_value)->isManualValue()->returns(false);
        $submitted_value = array(
            'manual_value'    => '123',
            'is_autocomputed' => false
        );

        $this->assertTrue($this->field->hasChanges($this->artifact, $this->old_value, $submitted_value));
    }

    public function itDetectsChangeWhenBackToAutocomputeWhenManualValueIs0()
    {
        stub($this->old_value)->getNumeric()->returns(0.0);
        stub($this->old_value)->isManualValue()->returns(true);
        $submitted_value = array(
            'manual_value'    => '',
            'is_autocomputed' => true
        );

        $this->assertTrue($this->field->hasChanges($this->artifact, $this->old_value, $submitted_value));
    }

    public function itHasChangesWhenANewManualValueIsSet()
    {
        stub($this->old_value)->getNumeric()->returns(7.0);
        $new_value = array(
            'is_autocomputed' => '',
            'manual_value'    => 5
        );

        $this->assertTrue($this->field->hasChanges($this->artifact, $this->old_value, $new_value));
    }

    public function itHasNotChangesWhenANewManualValueIsEqualToOldChangesetValue()
    {
        stub($this->old_value)->getNumeric()->returns(7.0);
        $new_value = array(
            'is_autocomputed' => '',
            'manual_value'    => 7
        );

        $this->assertFalse($this->field->hasChanges($this->artifact, $this->old_value, $new_value));
    }

    public function itHasNotChangesIfYouAreStillInAutocomputedMode()
    {
        stub($this->old_value)->getNumeric()->returns(null);
        stub($this->old_value)->isManualValue()->returns(false);
        $new_value = array(
            'is_autocomputed' => '1',
            'manual_value'    => ''
        );

        $this->assertFalse($this->field->hasChanges($this->artifact, $this->old_value, $new_value));
    }

    public function itHasNotChangesIfYouAreStillInAutocomputedModeWithAProvidedManualValueByHTMLForm()
    {
        stub($this->old_value)->getNumeric()->returns(null);
        stub($this->old_value)->isManualValue()->returns(false);
        $new_value = array(
            'is_autocomputed' => '1',
            'manual_value'    => '999999'
        );

        $this->assertFalse($this->field->hasChanges($this->artifact, $this->old_value, $new_value));
    }

    public function itHasNotChangesWhenANewManualIsAStringAndValueIsEqualToOldChangesetValue()
    {
        stub($this->old_value)->getNumeric()->returns(7.0);
        $new_value = array(
            'is_autocomputed' => '',
            'manual_value'    => '7'
        );

        $this->assertFalse($this->field->hasChanges($this->artifact, $this->old_value, $new_value));
    }

    public function itCanAdd0ToManualValueFromAutocomputed()
    {
        stub($this->old_value)->getNumeric()->returns(null);
        $new_value = array(
            'is_autocomputed' => '',
            'manual_value'    => '0'
        );

        $this->assertTrue($this->field->hasChanges($this->artifact, $this->old_value, $new_value));
    }
}

class Tracker_FormElement_Field_Computed_BackToAutoComputed extends TuleapTestCase
{
    private $user;
    private $field;
    private $formelement_factory;
    private $dao;
    private $artifact_factory;
    private $artifact;

    public function setUp()
    {
        parent::setUp();
        $this->user       = mock('PFUser');
        $this->dao        = mock('Tracker_FormElement_Field_ComputedDao');
        $this->manual_dao = mock('Tuleap\Tracker\DAO\ComputedDao');
        $this->field      = $this->getComputedField();

        $this->artifact_factory = mock('Tracker_ArtifactFactory');
        Tracker_ArtifactFactory::setInstance($this->artifact_factory);

        $this->formelement_factory = mock('Tracker_FormElementFactory');
        Tracker_FormElementFactory::setInstance($this->formelement_factory);

        $this->artifact = stub('Tracker_Artifact')->getId()->returns(233);
        stub($this->artifact)->userCanView()->returns(true);
        stub($this->artifact)->getTracker()->returns(aMockTracker()->withId(12)->build());
    }

    private function getComputedField()
    {
        $field = partial_mock(
            'Tracker_FormElement_Field_Computed',
            array(
                'getProperty',
                'getDao',
                'getId',
                'getValueDao',
                'getStandardCalculationMode',
                'getFieldEmptyMessage',
                'getStopAtManualSetFieldMode',
                'getName'
            )
        );
        stub($field)->getProperty('fast_compute')->returns(0);
        stub($field)->getDao()->returns($this->dao);
        stub($field)->getValueDao()->returns($this->manual_dao);
        stub($field)->getId()->returns(23);
        stub($field)->getName()->returns('effort');
        return $field;
    }

    public function tearDown()
    {
        parent::tearDown();
        Tracker_FormElementFactory::clearInstance();
        Tracker_ArtifactFactory::clearInstance();
    }

    public function itCallsStandardCalculWhenFieldsAreIntAndNoValuesAreSet()
    {
        stub($this->dao)->getComputedFieldValues(array(233), 'effort', 23, false)->returnsDar(
            array('id' => 750, 'type' => 'int'),
            array('id' => 750, 'type' => 'int')
        );

        $artifact  = stub('Tracker_Artifact')->getId()->returns(233);
        $changeset = stub($artifact)->getLastChangeset()->returns(mock('Tracker_Artifact_Changeset'));
        $changeset = stub($changeset)->getId()->returns(101);

        stub($this->manual_dao)->getManuallySetValueForChangeset()->returns(array('value' => null));
        expect($this->field)->getStandardCalculationMode()->once();

        $this->field->getComputedValueWithNoStopOnManualValue($artifact);
    }

    public function itCallsStandardCalculWhenFieldsAreComputedAndNoValuesAreSet()
    {
        stub($this->dao)->getComputedFieldValues(array(233), 'effort', 23, false)->returnsDar(
            array('id' => 766, 'type' => 'computed'),
            array('id' => 777, 'type' => 'computed')
        );

        $artifact  = stub('Tracker_Artifact')->getId()->returns(233);
        $changeset = stub($artifact)->getLastChangeset()->returns(mock('Tracker_Artifact_Changeset'));
        $changeset = stub($changeset)->getId()->returns(101);

        stub($this->manual_dao)->getManuallySetValueForChangeset()->returns(array('value' => null));
        expect($this->field)->getStandardCalculationMode()->once();

        $this->field->getComputedValueWithNoStopOnManualValue($artifact);
    }

    public function itCallsStandardCalculWhenAComputedValueIsSet()
    {
        stub($this->dao)->getComputedFieldValues(array(233), 'effort', 23, false)->returnsDar(
            array('id' => 766, 'type' => 'computed', 'value' => '6'),
            array('id' => 777, 'type' => 'computed')
        );

        $artifact  = stub('Tracker_Artifact')->getId()->returns(233);
        $changeset = stub($artifact)->getLastChangeset()->returns(mock('Tracker_Artifact_Changeset'));
        $changeset = stub($changeset)->getId()->returns(101);

        stub($this->manual_dao)->getManuallySetValueForChangeset()->returns(array('value' => null));
        expect($this->field)->getStandardCalculationMode()->once();

        $this->field->getComputedValueWithNoStopOnManualValue($artifact);
    }

    public function itCallsStandardCalculWhenIntFieldsAreSet()
    {
        stub($this->dao)->getComputedFieldValues(array(233), 'effort', 23, false)->returnsDar(
            array('id' => 750, 'type' => 'int', 'int_value' => 10),
            array('id' => 751, 'type' => 'int', 'int_value' => 5)
        );

        $artifact  = stub('Tracker_Artifact')->getId()->returns(233);
        $changeset = stub($artifact)->getLastChangeset()->returns(mock('Tracker_Artifact_Changeset'));
        $changeset = stub($changeset)->getId()->returns(101);

        stub($this->manual_dao)->getManuallySetValueForChangeset()->returns(array('value' => null));
        expect($this->field)->getStandardCalculationMode()->once();

        $this->field->getComputedValueWithNoStopOnManualValue($artifact);
    }

    public function itReturnsNullWhenNoManualValueIsSetAndNoChildrenExists()
    {
        stub($this->dao)->getComputedFieldValues(array(233), 'effort', 23, false)->returnsDar(null);

        $artifact  = stub('Tracker_Artifact')->getId()->returns(233);
        $changeset = stub($artifact)->getLastChangeset()->returns(mock('Tracker_Artifact_Changeset'));
        $changeset = stub($changeset)->getId()->returns(101);

        stub($this->manual_dao)->getManuallySetValueForChangeset()->returns(array('value' => null));

        $result = $this->field->getComputedValueWithNoStopOnManualValue($artifact);

        $this->assertIdentical(null, $result);
    }

    public function itCalculatesAutocomputedAndintFieldsEvenIfAParentIsSet()
    {
        stub($this->dao)->getComputedFieldValues(array(233), 'effort', 23, false)->returnsDar(
            array('id' => 750, 'type' => 'int', 'int_value' => 10),
            array('id' => 751, 'type' => 'int', 'int_value' => 5),
            array('id' => 766, 'type' => 'computed', 'value' => '6'),
            array('id' => 777, 'type' => 'computed', 'value' => '6')
        );

        $artifact  = stub('Tracker_Artifact')->getId()->returns(233);
        $changeset = stub($artifact)->getLastChangeset()->returns(mock('Tracker_Artifact_Changeset'));
        stub($changeset)->getId()->returns(101);

        stub($this->manual_dao)->getManuallySetValueForChangeset()->returns(array('value' => 12));
        expect($this->field)->getStopAtManualSetFieldMode()->once();
        expect($this->field)->getStandardCalculationMode()->once();

        $this->field->getComputedValueWithNoStopOnManualValue($artifact);
    }

    public function itReturnsComputedValuesAndIntValuesWhenBothAreOneSameChildrenLevel()
    {
        stub($this->dao)->getComputedFieldValues(array(233), 'effort', 23, false)->returnsDar(
            array('id' => 750, 'type' => 'int', 'int_value' => 10),
            array('id' => 751, 'type' => 'int', 'int_value' => 5),
            array('id' => 766, 'type' => 'computed', 'value' => '6'),
            array('id' => 777, 'type' => 'computed', 'value' => '6')
        );

        $artifact  = stub('Tracker_Artifact')->getId()->returns(233);
        $changeset = stub($artifact)->getLastChangeset()->returns(mock('Tracker_Artifact_Changeset'));
        $changeset = stub($changeset)->getId()->returns(101);

        stub($this->manual_dao)->getManuallySetValueForChangeset()->returns(array('value' => null));
        expect($this->field)->getStandardCalculationMode()->once();

        $this->field->getComputedValueWithNoStopOnManualValue($artifact);
    }
}

class Tracker_FormElement_Field_Compute_FastComputeTest extends TuleapTestCase
{
    private $user;
    private $field;
    private $formelement_factory;
    /**
     * @var Tracker_FormElement_Field_ComputedDao
     */
    private $dao;
    private $artifact_factory;

    public function setUp()
    {
        parent::setUp();
        $this->user       = mock('PFUser');
        $this->dao        = mock('Tracker_FormElement_Field_ComputedDao');
        $this->manual_dao = mock('Tuleap\Tracker\DAO\ComputedDao');
        $this->field      = TestHelper::getPartialMock(
            'Tracker_FormElement_Field_Computed',
            array(
                'getProperty',
                'getDao',
                'getName',
                'getId',
                'getValueDao',
                'getStandardCalculationMode',
                'getCalculator'
            )
        );
        stub($this->field)->getName()->returns('effort');
        stub($this->field)->getProperty('fast_compute')->returns(1);
        stub($this->field)->getDao()->returns($this->dao);
        stub($this->field)->getValueDao()->returns($this->manual_dao);
        stub($this->field)->getId()->returns(23);
        stub($this->field)->getCalculator()->returns(new FieldCalculator(new ComputedFieldCalculator($this->dao)));

        $this->artifact_factory = mock('Tracker_ArtifactFactory');
        Tracker_ArtifactFactory::setInstance($this->artifact_factory);

        $this->formelement_factory = mock('Tracker_FormElementFactory');
        Tracker_FormElementFactory::setInstance($this->formelement_factory);
    }

    public function tearDown()
    {
        parent::tearDown();
        Tracker_FormElementFactory::clearInstance();
    }

    public function itComputesDirectValues()
    {
        expect($this->dao)->getComputedFieldValues()->once();
        stub($this->dao)->getComputedFieldValues(array(233), 'effort', 23, true)->returnsDar(
            array('id' => 750, 'artifact_link_id' => 750, 'type' => 'int', 'int_value' => 5, 'parent_id' => 233),
            array('id' => 751, 'artifact_link_id' => 751, 'type' => 'int', 'int_value' => 15, 'parent_id' => 233)
        );

        $artifact = stub('Tracker_Artifact')->getId()->returns(233);
        $this->assertEqual(20, $this->field->getComputedValue($this->user, $artifact));
    }

    public function itDisplaysEmptyWhenFieldsAreAutocomputedAndNoValuesAreSet()
    {
        stub($this->dao)->getComputedFieldValues(array(233), 'effort', 23, false)->returnsDar(
            array('id' => 766, 'artifact_link_id' => 766, 'type' => 'computed'),
            array('id' => 777, 'artifact_link_id' => 777, 'type' => 'computed')
        );

        $artifact  = stub('Tracker_Artifact')->getId()->returns(233);
        $changeset = Mockery::mock(Tracker_Artifact_Changeset::class);
        $changeset->shouldReceive('getId')->andReturn(101);
        stub($artifact)->getLastChangeset()->returns($changeset);

        stub($this->manual_dao)->getManuallySetValueForChangeset(101, 23)->returns(array('value' => null));
        expect($this->field)->getStandardCalculationMode()->once();
        $this->field->getComputedValueWithNoStopOnManualValue($artifact);
    }

    public function itDisplaysComputedValuesWhenComputedChildrenAreSet()
    {
        stub($this->dao)->getComputedFieldValues(array(233), 'effort', 23, false)->returnsDar(
            array('id' => 766, 'artifact_link_id' => 766, 'type' => 'computed', 'value' => 10),
            array('id' => 777, 'artifact_link_id' => 777, 'type' => 'computed', 'value' => 5)
        );

        $artifact  = stub('Tracker_Artifact')->getId()->returns(233);
        $changeset = Mockery::mock(Tracker_Artifact_Changeset::class);
        $changeset->shouldReceive('getId')->andReturn(101);
        stub($artifact)->getLastChangeset()->returns($changeset);

        stub($this->manual_dao)->getManuallySetValueForChangeset(101, 23)->returns(array('value' => null));
        expect($this->field)->getStandardCalculationMode()->once();
        $this->field->getComputedValueWithNoStopOnManualValue($artifact);
    }


    public function itMakesOneDbCallPerGraphDepth()
    {
        expect($this->dao)->getComputedFieldValues()->count(2);
        stub($this->dao)->getComputedFieldValues(array(233), 'effort', 23, true)->returnsDar(
            array('id' => 750, 'artifact_link_id' => 750, 'type' => 'int', 'int_value' => 5, 'parent_id' => 233),
            array('id' => 751, 'artifact_link_id' => 751, 'type' => 'int', 'int_value' => 15, 'parent_id' => 233),
            array('id' => 766, 'artifact_link_id' => 766, 'type' => 'computed', 'parent_id' => 233),
            array('id' => 777, 'artifact_link_id' => 777, 'type' => 'computed', 'parent_id' => 233)
        );
        stub($this->dao)->getComputedFieldValues(array(766, 777), 'effort', 23, true)->returnsDar(
            array('id' => 752, 'artifact_link_id' => 752, 'type' => 'int', 'int_value' => 10, 'parent_id' => 766),
            array('id' => 753, 'artifact_link_id' => 753, 'type' => 'int', 'int_value' => 10, 'parent_id' => 777)
        );

        $artifact = stub('Tracker_Artifact')->getId()->returns(233);
        $this->assertEqual(40, $this->field->getComputedValue($this->user, $artifact));
    }

    public function itDoesntMakeLoopInGraph()
    {
        expect($this->dao)->getComputedFieldValues()->count(3);
        stub($this->dao)->getComputedFieldValues(array(233), 'effort', 23, true)->returnsDar(
            array('id' => 750, 'artifact_link_id' => 750, 'type' => 'int', 'int_value' => 5, 'parent_id' => 233),
            array('id' => 751, 'artifact_link_id' => 751, 'type' => 'int', 'int_value' => 15, 'parent_id' => 233),
            array('id' => 766, 'artifact_link_id' => 766, 'type' => 'computed', 'parent_id' => 233),
            array('id' => 777, 'artifact_link_id' => 777, 'type' => 'computed', 'parent_id' => 233)
        );
        stub($this->dao)->getComputedFieldValues(array(766, 777), 'effort', 23, true)->returnsDar(
            array('id' => 752, 'artifact_link_id' => 750, 'type' => 'int', 'int_value' => 10, 'parent_id' => 766),
            array('id' => 753, 'artifact_link_id' => 751, 'type' => 'int', 'int_value' => 10, 'parent_id' => 777),
            array('id' => 766, 'artifact_link_id' => 766, 'type' => 'computed', 'parent_id' => 233),
            array('id' => 777, 'artifact_link_id' => 777, 'type' => 'computed', 'parent_id' => 233)
        );

        $artifact = stub('Tracker_Artifact')->getId()->returns(233);
        $this->assertEqual(40, $this->field->getComputedValue($this->user, $artifact));
    }

    /**
     * This use case highlights the case where a Release have 2 backlog elements
     * and 2 sprints. The backlog elements are also presents in the sprints backlog
     * each backlog element should be counted only once.
     */
    public function itDoesntCountTwiceTheFinalData()
    {
        stub($this->dao)->getComputedFieldValues(array(233), 'effort', 23, true)->returnsDar(
            array('id' => 750, 'artifact_link_id' => 750, 'type' => 'int', 'int_value' => 5, 'parent_id' => 233),
            array('id' => 751, 'artifact_link_id' => 751, 'type' => 'int', 'int_value' => 15, 'parent_id' => 233),
            array('id' => 766, 'artifact_link_id' => 766, 'type' => 'computed', 'parent_id' => 233),
            array('id' => 777, 'artifact_link_id' => 777, 'type' => 'computed', 'parent_id' => 233)
        );
        stub($this->dao)->getComputedFieldValues(array(766, 777), 'effort', 23, true)->returnsDar(
            array('id' => 750, 'artifact_link_id' => 750, 'type' => 'int', 'int_value' => 5, 'parent_id' => 766),
            array('id' => 751, 'artifact_link_id' => 751, 'type' => 'int', 'int_value' => 15, 'parent_id' => 766),
            array('id' => 766, 'artifact_link_id' => 766, 'type' => 'computed', 'parent_id' => 777)
        );

        $artifact = stub('Tracker_Artifact')->getId()->returns(233);
        $this->assertEqual(20, $this->field->getComputedValue($this->user, $artifact));
    }

    public function itStopsWhenAManualValueIsSet()
    {
        stub($this->dao)->getComputedFieldValues(array(233), 'effort', 23, true)->returnsDar(
            array('id' => 766, 'artifact_link_id' => 766, 'type' => 'computed', 'parent_id' => 233)
        );
        stub($this->dao)->getComputedFieldValues(array(766), 'effort', 23, true)->returnsDar(
            array('id' => 766, 'artifact_link_id' => 766, 'type' => 'computed', 'value' => 4, 'parent_id' => 766),
            array('id' => 750, 'artifact_link_id' => 750, 'type' => 'int', 'int_value' => 5, 'parent_id' => 766),
            array('id' => 751, 'artifact_link_id' => 751, 'type' => 'int', 'int_value' => 15, 'parent_id' => 766)
        );

        $artifact = stub('Tracker_Artifact')->getId()->returns(233);
        $this->assertEqual(4, $this->field->getComputedValue($this->user, $artifact));
    }

    public function itCanAddManuallySetValuesAndComputedValues()
    {
        stub($this->dao)->getComputedFieldValues(array(233), 'effort', 23, true)->returnsDar(
            array('id' => 766, 'artifact_link_id' => 766, 'type' => 'computed', 'parent_id' => 233, 'value' => 4.7500),
            array('id' => 777, 'artifact_link_id' => 777, 'type' => 'computed', 'parent_id' => null)
        );
        stub($this->dao)->getComputedFieldValues(array(777), 'effort', 23, true)->returnsDar(
            array('id' => 750, 'artifact_link_id' => 750, 'type' => 'float', 'float_value' => 5.2500, 'parent_id' => 777),
            array('id' => 751, 'artifact_link_id' => 751, 'type' => 'float', 'float_value' => 15, 'parent_id' => 777)
        );

        $artifact = stub('Tracker_Artifact')->getId()->returns(233);
        $this->assertEqual(25, $this->field->getComputedValue($this->user, $artifact));
    }
}

class Tracker_FormElement_Field_Computed_FieldValidationTest extends TuleapTestCase
{
    /**
     * @var Tracker_FormElement_Field_Computed
     */
    private $field;

    public function setUp()
    {
        parent::setUp();
        $this->field = partial_mock('Tracker_FormElement_Field_Computed', array('isRequired', 'userCanUpdate'));
    }

    public function itExpectsAnArray()
    {
        $this->assertFalse($this->field->validateValue('String'));
        $this->assertFalse($this->field->validateValue(1));
        $this->assertFalse($this->field->validateValue(1.1));
        $this->assertFalse($this->field->validateValue(true));
        $this->assertTrue($this->field->validateValue(array(Tracker_FormElement_Field_Computed::FIELD_VALUE_IS_AUTOCOMPUTED => true)));
    }

    public function itExpectsAtLeastAValueOrAnAutocomputedInformation()
    {
        $this->assertFalse($this->field->validateValue(array()));
        $this->assertFalse($this->field->validateValue(array('v1' => 1)));
        $this->assertFalse($this->field->validateValue(array(Tracker_FormElement_Field_Computed::FIELD_VALUE_IS_AUTOCOMPUTED)));
        $this->assertFalse($this->field->validateValue(array(Tracker_FormElement_Field_Computed::FIELD_VALUE_MANUAL)));
        $this->assertFalse($this->field->validateValue(array(
            Tracker_FormElement_Field_Computed::FIELD_VALUE_MANUAL,
            Tracker_FormElement_Field_Computed::FIELD_VALUE_IS_AUTOCOMPUTED
        )));
        $this->assertTrue($this->field->validateValue(array(Tracker_FormElement_Field_Computed::FIELD_VALUE_MANUAL => 1)));
        $this->assertTrue($this->field->validateValue(array(Tracker_FormElement_Field_Computed::FIELD_VALUE_IS_AUTOCOMPUTED => true)));
    }

    public function itExpectsAFloatOrAIntAsManualValue()
    {
        $this->assertFalse($this->field->validateValue(array(Tracker_FormElement_Field_Computed::FIELD_VALUE_MANUAL => 'String')));
        $this->assertTrue($this->field->validateValue(array(Tracker_FormElement_Field_Computed::FIELD_VALUE_MANUAL => 1.1)));
        $this->assertTrue($this->field->validateValue(array(Tracker_FormElement_Field_Computed::FIELD_VALUE_MANUAL => 0)));
    }

    public function itCanNotAcceptAManualValueWhenAutocomputedIsEnabled()
    {
        $this->assertFalse($this->field->validateValue(array(
            Tracker_FormElement_Field_Computed::FIELD_VALUE_MANUAL => 1,
            Tracker_FormElement_Field_Computed::FIELD_VALUE_IS_AUTOCOMPUTED => true
        )));
        $this->assertTrue($this->field->validateValue(array(
            Tracker_FormElement_Field_Computed::FIELD_VALUE_MANUAL => 1,
            Tracker_FormElement_Field_Computed::FIELD_VALUE_IS_AUTOCOMPUTED => false
        )));
        $this->assertFalse($this->field->validateValue(array(
            Tracker_FormElement_Field_Computed::FIELD_VALUE_MANUAL => '',
            Tracker_FormElement_Field_Computed::FIELD_VALUE_IS_AUTOCOMPUTED => false
        )));
        $this->assertTrue($this->field->validateValue(array(
            Tracker_FormElement_Field_Computed::FIELD_VALUE_MANUAL => '',
            Tracker_FormElement_Field_Computed::FIELD_VALUE_IS_AUTOCOMPUTED => true
        )));
    }

    public function itIsValidWhenTheFieldIsRequiredAndIsAutocomputed()
    {
        $artifact = stub('Tracker_Artifact')->getId()->returns(233);
        stub($this->field)->isRequired()->returns(true);
        stub($this->field)->userCanUpdate()->returns(true);
        $submitted_value = array(
            'manual_value'    => '',
            'is_autocomputed' => true
        );

        $this->assertTrue(
            $this->field->validateFieldWithPermissionsAndRequiredStatus($artifact, $submitted_value)
        );
    }

    public function itIsValidWhenTheFieldIsNotRequiredAndIsAutocomputed()
    {
        $artifact = stub('Tracker_Artifact')->getId()->returns(233);
        stub($this->field)->isRequired()->returns(false);
        stub($this->field)->userCanUpdate()->returns(true);
        $submitted_value = array(
            'manual_value'    => '',
            'is_autocomputed' => true
        );

        $this->assertTrue(
            $this->field->validateFieldWithPermissionsAndRequiredStatus($artifact, $submitted_value)
        );
    }

    public function itIsValidWhenTheFieldIsRequiredAndHasAManualValue()
    {
        $artifact = stub('Tracker_Artifact')->getId()->returns(233);
        stub($this->field)->isRequired()->returns(true);
        stub($this->field)->userCanUpdate()->returns(true);
        $submitted_value = array(
            'manual_value'    => '11'
        );

        $this->assertTrue(
            $this->field->validateFieldWithPermissionsAndRequiredStatus($artifact, $submitted_value)
        );
    }

    public function itIsNotValidWhenTheFieldIsRequiredAndDoesntHaveAManualValue()
    {
        $artifact = stub('Tracker_Artifact')->getId()->returns(233);
        stub($this->field)->isRequired()->returns(true);
        stub($this->field)->userCanUpdate()->returns(true);
        $submitted_value = array(
            'manual_value'    => ''
        );

        $this->assertFalse(
            $this->field->validateFieldWithPermissionsAndRequiredStatus($artifact, $submitted_value)
        );
    }

    public function itIsNotValidWhenTheFieldIsNotRequiredAndDoesntHaveAManualValue()
    {
        $artifact = stub('Tracker_Artifact')->getId()->returns(233);
        stub($this->field)->isRequired()->returns(false);
        stub($this->field)->userCanUpdate()->returns(true);
        $submitted_value = array(
            'manual_value'    => ''
        );

        $this->assertFalse(
            $this->field->validateFieldWithPermissionsAndRequiredStatus($artifact, $submitted_value)
        );
    }

    public function itIsValidWhenNoValuesAreSubmitted()
    {
        $artifact = stub('Tracker_Artifact')->getId()->returns(233);
        stub($this->field)->isRequired()->returns(false);
        stub($this->field)->userCanUpdate()->returns(true);
        $submitted_value = array(
        );

        $this->assertTrue(
            $this->field->validateFieldWithPermissionsAndRequiredStatus($artifact, $submitted_value)
        );
    }
}

class Tracker_FormElement_Field_Computed_RESTValueTest extends TuleapTestCase
{
    /**
     * @var Tracker_FormElement_Field_Computed
     */
    private $field;

    public function setUp()
    {
        parent::setUp();

        $this->field = partial_mock('Tracker_FormElement_Field_Computed', array());
    }

    public function itReturnsValueWhenCorrectlyFormatted()
    {
        $value = array(
            Tracker_FormElement_Field_Computed::FIELD_VALUE_IS_AUTOCOMPUTED => true
        );

        $this->assertEqual($value, $this->field->getFieldDataFromRESTValue($value));
    }

    public function itRejectsDataWhenAutocomputedIsDisabledAndNoManualValueIsProvided()
    {
        $this->expectException('Tracker_FormElement_InvalidFieldValueException');
        $value = array(
            Tracker_FormElement_Field_Computed::FIELD_VALUE_IS_AUTOCOMPUTED => false
        );
        $this->field->getFieldDataFromRESTValue($value);
    }

    public function itRejectsDataWhenAutocomputedIsDisabledAndManualValueIsNull()
    {
        $this->expectException('Tracker_FormElement_InvalidFieldValueException');
        $value = array(
            Tracker_FormElement_Field_Computed::FIELD_VALUE_IS_AUTOCOMPUTED => false,
            Tracker_FormElement_Field_Computed::FIELD_VALUE_MANUAL          => null
        );
        $this->field->getFieldDataFromRESTValue($value);
    }

    public function itRejectsDataWhenValueIsSet()
    {
        $this->expectException('Tracker_FormElement_InvalidFieldValueException');
        $value = array(
            'value' => 1
        );
        $this->field->getFieldDataFromRESTValue($value);
    }
}
