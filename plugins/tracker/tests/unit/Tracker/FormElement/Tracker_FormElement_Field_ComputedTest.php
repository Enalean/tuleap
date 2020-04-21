<?php
/**
 * Copyright (c) Enalean, 2012-Present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\Tracker\FormElement;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery\LegacyMockInterface;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;
use SimpleXMLElement;
use Tracker_Artifact;
use Tracker_Artifact_Changeset;
use Tracker_Artifact_Changeset_ValueDao;
use Tracker_FormElement_Field_Computed;
use Tracker_FormElement_Field_ComputedDao;
use Tracker_FormElement_InvalidFieldValueException;
use Tuleap\GlobalLanguageMock;
use Tuleap\GlobalResponseMock;
use Tuleap\Tracker\Artifact\ChangesetValueComputed;
use Tuleap\Tracker\DAO\ComputedDao;
use Tuleap\Tracker\FormElement\Field\File\CreatedFileURLMapping;

// phpcs:ignore Squiz.Classes.ValidClassName.NotCamelCaps
final class Tracker_FormElement_Field_ComputedTest extends TestCase
{
    use MockeryPHPUnitIntegration;
    use GlobalResponseMock;
    use GlobalLanguageMock;

    /**
     * @return Tracker_FormElement_Field_Computed|LegacyMockInterface|MockInterface
     */
    private function getComputedField()
    {
        return \Mockery::mock(Tracker_FormElement_Field_Computed::class)
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();
    }

    public function testItExportsDefaultValueInXML()
    {
        $computed_field = \Mockery::mock(Tracker_FormElement_Field_Computed::class)->makePartial();
        $computed_field->shouldReceive('getProperty')->with('default_value')->andReturn('12.34');

        $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><field />');
        $computed_field->exportPropertiesToXML($xml);

        $this->assertSame("12.34", (string) $xml->properties['default_value']);
    }

    public function testItDoesNotExportNullDefaultValueInXML()
    {
        $computed_field = \Mockery::mock(Tracker_FormElement_Field_Computed::class)->makePartial();
        $computed_field->shouldReceive('getProperty')->with('default_value')->andReturnNull();

        $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><field />');
        $computed_field->exportPropertiesToXML($xml);

        $this->assertFalse(isset($xml->properties));
    }

    public function testItReturnsValueWhenCorrectlyFormatted(): void
    {
        $field = $this->getComputedField();
        $value = [
            Tracker_FormElement_Field_Computed::FIELD_VALUE_IS_AUTOCOMPUTED => true
        ];

        $this->assertEquals($value, $field->getFieldDataFromRESTValue($value));
    }

    public function testItRejectsDataWhenAutocomputedIsDisabledAndNoManualValueIsProvided(): void
    {
        $field = $this->getComputedField();
        $this->expectException(Tracker_FormElement_InvalidFieldValueException::class);
        $value = [
            Tracker_FormElement_Field_Computed::FIELD_VALUE_IS_AUTOCOMPUTED => false
        ];
        $field->getFieldDataFromRESTValue($value);
    }

    public function testItRejectsDataWhenAutocomputedIsDisabledAndManualValueIsNull(): void
    {
        $field = $this->getComputedField();
        $this->expectException(Tracker_FormElement_InvalidFieldValueException::class);
        $value = [
            Tracker_FormElement_Field_Computed::FIELD_VALUE_IS_AUTOCOMPUTED => false,
            Tracker_FormElement_Field_Computed::FIELD_VALUE_MANUAL          => null
        ];
        $field->getFieldDataFromRESTValue($value);
    }

    public function testItRejectsDataWhenValueIsSet(): void
    {
        $field = $this->getComputedField();
        $this->expectException(Tracker_FormElement_InvalidFieldValueException::class);
        $value = [
            'value' => 1
        ];
        $field->getFieldDataFromRESTValue($value);
    }

    public function testItExpectsAnArray(): void
    {
        $field = $this->getComputedField();
        $this->assertFalse($field->validateValue('String'));
        $this->assertFalse($field->validateValue(1));
        $this->assertFalse($field->validateValue(1.1));
        $this->assertFalse($field->validateValue(true));
        $this->assertTrue(
            $field->validateValue([Tracker_FormElement_Field_Computed::FIELD_VALUE_IS_AUTOCOMPUTED => true])
        );
    }

    public function testItExpectsAtLeastAValueOrAnAutocomputedInformation(): void
    {
        $field = $this->getComputedField();
        $this->assertFalse($field->validateValue([]));
        $this->assertFalse($field->validateValue(['v1' => 1]));
        $this->assertFalse($field->validateValue([Tracker_FormElement_Field_Computed::FIELD_VALUE_IS_AUTOCOMPUTED]));
        $this->assertFalse($field->validateValue([Tracker_FormElement_Field_Computed::FIELD_VALUE_MANUAL]));
        $this->assertFalse(
            $field->validateValue(
                [
                    Tracker_FormElement_Field_Computed::FIELD_VALUE_MANUAL,
                    Tracker_FormElement_Field_Computed::FIELD_VALUE_IS_AUTOCOMPUTED
                ]
            )
        );
        $this->assertTrue($field->validateValue([Tracker_FormElement_Field_Computed::FIELD_VALUE_MANUAL => 1]));
        $this->assertTrue(
            $field->validateValue([Tracker_FormElement_Field_Computed::FIELD_VALUE_IS_AUTOCOMPUTED => true])
        );
    }

    public function testItExpectsAFloatOrAIntAsManualValue(): void
    {
        $field = $this->getComputedField();
        $this->assertFalse($field->validateValue([Tracker_FormElement_Field_Computed::FIELD_VALUE_MANUAL => 'String']));
        $this->assertTrue($field->validateValue([Tracker_FormElement_Field_Computed::FIELD_VALUE_MANUAL => 1.1]));
        $this->assertTrue($field->validateValue([Tracker_FormElement_Field_Computed::FIELD_VALUE_MANUAL => 0]));
    }

    public function testItCanNotAcceptAManualValueWhenAutocomputedIsEnabled(): void
    {
        $field = $this->getComputedField();
        $this->assertFalse(
            $field->validateValue(
                [
                    Tracker_FormElement_Field_Computed::FIELD_VALUE_MANUAL          => 1,
                    Tracker_FormElement_Field_Computed::FIELD_VALUE_IS_AUTOCOMPUTED => true
                ]
            )
        );
        $this->assertTrue(
            $field->validateValue(
                [
                    Tracker_FormElement_Field_Computed::FIELD_VALUE_MANUAL          => 1,
                    Tracker_FormElement_Field_Computed::FIELD_VALUE_IS_AUTOCOMPUTED => false
                ]
            )
        );
        $this->assertFalse(
            $field->validateValue(
                [
                    Tracker_FormElement_Field_Computed::FIELD_VALUE_MANUAL          => '',
                    Tracker_FormElement_Field_Computed::FIELD_VALUE_IS_AUTOCOMPUTED => false
                ]
            )
        );
        $this->assertTrue(
            $field->validateValue(
                [
                    Tracker_FormElement_Field_Computed::FIELD_VALUE_MANUAL          => '',
                    Tracker_FormElement_Field_Computed::FIELD_VALUE_IS_AUTOCOMPUTED => true
                ]
            )
        );
    }

    public function testItIsValidWhenTheFieldIsRequiredAndIsAutocomputed(): void
    {
        $field = $this->getComputedField();
        $artifact = \Mockery::mock(\Tracker_Artifact::class);
        $artifact->shouldReceive('getId')->andReturn(233);
        $field->shouldReceive('isRequired')->andReturn(true);
        $field->shouldReceive('userCanUpdate')->andReturn(true);
        $submitted_value = [
            'manual_value'    => '',
            'is_autocomputed' => true
        ];

        $this->assertTrue(
            $field->validateFieldWithPermissionsAndRequiredStatus($artifact, $submitted_value)
        );
    }

    public function testItIsValidWhenTheFieldIsNotRequiredAndIsAutocomputed(): void
    {
        $field = $this->getComputedField();
        $artifact = \Mockery::mock(\Tracker_Artifact::class);
        $artifact->shouldReceive('getId')->andReturn(233);
        $field->shouldReceive('isRequired')->andReturn(false);
        $field->shouldReceive('userCanUpdate')->andReturn(true);
        $submitted_value = [
            'manual_value'    => '',
            'is_autocomputed' => true
        ];

        $this->assertTrue(
            $field->validateFieldWithPermissionsAndRequiredStatus($artifact, $submitted_value)
        );
    }

    public function testItIsValidWhenTheFieldIsRequiredAndHasAManualValue(): void
    {
        $field = $this->getComputedField();
        $artifact = \Mockery::mock(\Tracker_Artifact::class);
        $artifact->shouldReceive('getId')->andReturn(233);
        $field->shouldReceive('isRequired')->andReturn(true);
        $field->shouldReceive('userCanUpdate')->andReturn(true);
        $field->shouldReceive('validate')->andReturn(true);
        $submitted_value = [
            'manual_value' => '11'
        ];

        $this->assertTrue(
            $field->validateFieldWithPermissionsAndRequiredStatus($artifact, $submitted_value)
        );
    }

    public function testItIsNotValidWhenTheFieldIsRequiredAndDoesntHaveAManualValue(): void
    {
        $field = $this->getComputedField();
        $artifact = \Mockery::mock(\Tracker_Artifact::class);
        $artifact->shouldReceive('getId')->andReturn(233);
        $field->shouldReceive('isRequired')->andReturn(true);
        $field->shouldReceive('userCanUpdate')->andReturn(true);
        $submitted_value = [
            'manual_value' => ''
        ];

        $this->assertFalse(
            $field->validateFieldWithPermissionsAndRequiredStatus($artifact, $submitted_value)
        );
    }

    public function testItIsNotValidWhenTheFieldIsNotRequiredAndDoesntHaveAManualValue(): void
    {
        $field = $this->getComputedField();
        $artifact = \Mockery::mock(\Tracker_Artifact::class);
        $artifact->shouldReceive('getId')->andReturn(233);
        $field->shouldReceive('isRequired')->andReturn(false);
        $field->shouldReceive('userCanUpdate')->andReturn(true);
        $submitted_value = [
            'manual_value' => ''
        ];

        $this->assertFalse(
            $field->validateFieldWithPermissionsAndRequiredStatus($artifact, $submitted_value)
        );
    }

    public function testItIsValidWhenNoValuesAreSubmitted(): void
    {
        $field = $this->getComputedField();
        $artifact = \Mockery::mock(\Tracker_Artifact::class);
        $artifact->shouldReceive('getId')->andReturn(233);
        $field->shouldReceive('isRequired')->andReturn(false);
        $field->shouldReceive('userCanUpdate')->andReturn(true);
        $submitted_value = [
        ];

        $this->assertTrue(
            $field->validateFieldWithPermissionsAndRequiredStatus($artifact, $submitted_value)
        );
    }

    public function testItDisplaysEmptyWhenFieldsAreAutocomputedAndNoValuesAreSet(): void
    {
        $dao       = \Mockery::mock(Tracker_FormElement_Field_ComputedDao::class);
        $value_dao = \Mockery::mock(ComputedDao::class);

        $artifact = $this->getArtifactWithChangeset();
        $field    = $this->getComputedFieldForManualComputationTests($dao, $value_dao);

        $value_one = ['id' => 766, 'artifact_link_id' => 766, 'type' => 'computed'];
        $value_two = ['id' => 777, 'artifact_link_id' => 777, 'type' => 'computed'];

        $values = \TestHelper::arrayToDar($value_one, $value_two);
        $dao->shouldReceive('getComputedFieldValues')
            ->withArgs([[233], 'effort', 23, false])
            ->andReturn($values);

        $value_dao->shouldReceive('getManuallySetValueForChangeset')->withArgs([101, 23])->andReturn(['value' => null]);
        $field->shouldReceive('getStandardCalculationMode')->once();
        $field->shouldReceive('getStopAtManualSetFieldMode')->never();

        $field->getComputedValueWithNoStopOnManualValue($artifact);
    }

    public function testItDisplaysComputedValuesWhenComputedChildrenAreSet(): void
    {
        $dao       = \Mockery::mock(Tracker_FormElement_Field_ComputedDao::class);
        $value_dao = \Mockery::mock(ComputedDao::class);

        $artifact = $this->getArtifactWithChangeset();
        $field    = $this->getComputedFieldForManualComputationTests($dao, $value_dao);

        $value_one = ['id' => 766, 'artifact_link_id' => 766, 'type' => 'computed', 'value' => 10];
        $value_two = ['id' => 777, 'artifact_link_id' => 777, 'type' => 'computed', 'value' => 5];
        $values    = \TestHelper::arrayToDar($value_one, $value_two);
        $dao->shouldReceive('getComputedFieldValues')
            ->withArgs([[233], 'effort', 23, false])
            ->andReturn($values);

        $value_dao->shouldReceive('getManuallySetValueForChangeset')->withArgs([101, 23])->andReturn(['value' => null]);
        $field->shouldReceive('getStandardCalculationMode')->once();
        $field->shouldReceive('getStopAtManualSetFieldMode')->never();

        $field->getComputedValueWithNoStopOnManualValue($artifact);
    }

    public function testItCallsStandardCalculWhenFieldsAreIntAndNoValuesAreSet(): void
    {
        $dao       = \Mockery::mock(Tracker_FormElement_Field_ComputedDao::class);
        $value_dao = \Mockery::mock(ComputedDao::class);

        $artifact = $this->getArtifactWithChangeset();
        $field    = $this->getComputedFieldForManualComputationTests($dao, $value_dao);

        $value_one = ['id' => 750, 'type' => 'int'];
        $value_two = ['id' => 75, 'type' => 'int'];
        $values    = \TestHelper::arrayToDar($value_one, $value_two);
        $dao->shouldReceive('getComputedFieldValues')
            ->withArgs([[233], 'effort', 23, false])
            ->andReturn($values);

        $value_dao->shouldReceive('getManuallySetValueForChangeset')->andReturn(['value' => null]);
        $field->shouldReceive('getStandardCalculationMode')->once();
        $field->shouldReceive('getStopAtManualSetFieldMode')->never();

        $field->getComputedValueWithNoStopOnManualValue($artifact);
    }

    public function testItCallsStandardCalculWhenFieldsAreComputedAndNoValuesAreSet(): void
    {
        $dao       = \Mockery::mock(Tracker_FormElement_Field_ComputedDao::class);
        $value_dao = \Mockery::mock(ComputedDao::class);

        $artifact = $this->getArtifactWithChangeset();
        $field    = $this->getComputedFieldForManualComputationTests($dao, $value_dao);

        $value_one = ['id' => 766, 'type' => 'computed'];
        $value_two = ['id' => 777, 'type' => 'computed'];
        $values    = \TestHelper::arrayToDar($value_one, $value_two);
        $dao->shouldReceive('getComputedFieldValues')
            ->withArgs([[233], 'effort', 23, false])
            ->andReturn($values);

        $value_dao->shouldReceive('getManuallySetValueForChangeset')->andReturn(['value' => null]);
        $field->shouldReceive('getStandardCalculationMode')->once();
        $field->shouldReceive('getStopAtManualSetFieldMode')->never();

        $field->getComputedValueWithNoStopOnManualValue($artifact);
    }

    public function testItCallsStandardCalculWhenAComputedValueIsSet(): void
    {
        $dao       = \Mockery::mock(Tracker_FormElement_Field_ComputedDao::class);
        $value_dao = \Mockery::mock(ComputedDao::class);

        $artifact = $this->getArtifactWithChangeset();
        $field    = $this->getComputedFieldForManualComputationTests($dao, $value_dao);

        $value_one = ['id' => 766, 'type' => 'computed', 'value' => '6'];
        $value_two = ['id' => 777, 'type' => 'computed'];

        $values = \TestHelper::arrayToDar($value_one, $value_two);
        $dao->shouldReceive('getComputedFieldValues')
            ->withArgs([[233], 'effort', 23, false])
            ->andReturn($values);

        $value_dao->shouldReceive('getManuallySetValueForChangeset')->andReturn(['value' => null]);
        $field->shouldReceive('getStandardCalculationMode')->once();
        $field->shouldReceive('getStopAtManualSetFieldMode')->never();

        $field->getComputedValueWithNoStopOnManualValue($artifact);
    }

    public function testItCallsStandardCalculWhenIntFieldsAreSet(): void
    {
        $dao       = \Mockery::mock(Tracker_FormElement_Field_ComputedDao::class);
        $value_dao = \Mockery::mock(ComputedDao::class);

        $artifact = $this->getArtifactWithChangeset();
        $field    = $this->getComputedFieldForManualComputationTests($dao, $value_dao);

        $value_one = ['id' => 750, 'type' => 'int', 'int_value' => 10];
        $value_two = ['id' => 751, 'type' => 'int', 'int_value' => 5];
        $values    = \TestHelper::arrayToDar($value_one, $value_two);
        $dao->shouldReceive('getComputedFieldValues')
            ->withArgs([[233], 'effort', 23, false])
            ->andReturn($values);

        $value_dao->shouldReceive('getManuallySetValueForChangeset')->andReturn(['value' => null]);
        $field->shouldReceive('getStandardCalculationMode')->once();
        $field->shouldReceive('getStopAtManualSetFieldMode')->never();

        $field->getComputedValueWithNoStopOnManualValue($artifact);
    }

    public function testItReturnsNullWhenNoManualValueIsSetAndNoChildrenExists(): void
    {
        $dao       = \Mockery::mock(Tracker_FormElement_Field_ComputedDao::class);
        $value_dao = \Mockery::mock(ComputedDao::class);

        $artifact = $this->getArtifactWithChangeset();
        $field    = $this->getComputedFieldForManualComputationTests($dao, $value_dao);

        $dao->shouldReceive('getComputedFieldValues')
            ->withArgs([[233], 'effort', 23, false])
            ->andReturn(null);

        $value_dao->shouldReceive('getManuallySetValueForChangeset')->andReturn(['value' => null]);
        $field->shouldReceive('getStopAtManualSetFieldMode')->never();
        $field->shouldReceive('getStandardCalculationMode')->never();

        $result = $field->getComputedValueWithNoStopOnManualValue($artifact);

        $this->assertNull($result);
    }

    public function testItCalculatesAutocomputedAndintFieldsEvenIfAParentIsSet(): void
    {
        $dao       = \Mockery::mock(Tracker_FormElement_Field_ComputedDao::class);
        $value_dao = \Mockery::mock(ComputedDao::class);

        $artifact = $this->getArtifactWithChangeset();
        $field    = $this->getComputedFieldForManualComputationTests($dao, $value_dao);

        $value_one   = ['id' => 750, 'type' => 'int', 'int_value' => 10];
        $value_two   = ['id' => 751, 'type' => 'int', 'int_value' => 5];
        $value_three = ['id' => 766, 'type' => 'computed', 'value' => '6'];
        $value_four  = ['id' => 777, 'type' => 'computed', 'value' => '6'];
        $values      = \TestHelper::arrayToDar($value_one, $value_two, $value_three, $value_four);
        $dao->shouldReceive('getComputedFieldValues')
            ->withArgs([[233], 'effort', 23, false])
            ->andReturn($values);

        $value_dao->shouldReceive('getManuallySetValueForChangeset')->andReturn(['value' => 12]);
        $field->shouldReceive('getStopAtManualSetFieldMode')->once();
        $field->shouldReceive('getStandardCalculationMode')->once();

        $field->getComputedValueWithNoStopOnManualValue($artifact);
    }

    public function testItReturnsComputedValuesAndIntValuesWhenBothAreOneSameChildrenLevel(): void
    {
        $dao       = \Mockery::mock(Tracker_FormElement_Field_ComputedDao::class);
        $value_dao = \Mockery::mock(ComputedDao::class);

        $artifact = $this->getArtifactWithChangeset();
        $field    = $this->getComputedFieldForManualComputationTests($dao, $value_dao);

        $value_one   = ['id' => 750, 'type' => 'int', 'int_value' => 10];
        $value_two   = ['id' => 751, 'type' => 'int', 'int_value' => 5];
        $value_three = ['id' => 766, 'type' => 'computed', 'value' => '6'];
        $value_four  = ['id' => 777, 'type' => 'computed', 'value' => '6'];
        $values      = \TestHelper::arrayToDar($value_one, $value_two, $value_three, $value_four);
        $dao->shouldReceive('getComputedFieldValues')
            ->withArgs([[233], 'effort', 23, false])
            ->andReturn($values);

        $value_dao->shouldReceive('getManuallySetValueForChangeset')->andReturn(['value' => null]);
        $field->shouldReceive('getStopAtManualSetFieldMode')->never();
        $field->shouldReceive('getStandardCalculationMode')->once();

        $field->getComputedValueWithNoStopOnManualValue($artifact);
    }

    /**
     * @return LegacyMockInterface|MockInterface|\Tracker_Artifact
     */
    private function getArtifactWithChangeset()
    {
        $artifact = \Mockery::mock(\Tracker_Artifact::class);
        $artifact->shouldReceive('getId')->andReturn(233);

        $changeset = \Mockery::mock(Tracker_Artifact_Changeset::class);
        $changeset->shouldReceive('getId')->andReturn(101);
        $artifact->shouldReceive('getLastChangeset')->andReturn($changeset);

        return $artifact;
    }

    /**
     * @return LegacyMockInterface|MockInterface|Tracker_FormElement_Field_Computed
     */
    private function getComputedFieldForManualComputationTests($dao, $value_dao)
    {
        $field = $this->getComputedField();
        $field->shouldReceive('getDao')->andReturn($dao);
        $field->shouldReceive('getId')->andReturn(23);
        $field->shouldReceive('getName')->andReturn('effort');
        $field->shouldReceive('getValueDao')->andReturn($value_dao);

        return $field;
    }

    public function testItDetectsChangeWhenBackToAutocompute(): void
    {
        $field = $this->getComputedField();
        $artifact = Mockery::mock(Tracker_Artifact::class);

        $old_value = Mockery::mock(ChangesetValueComputed::class);
        $old_value->shouldReceive('getNumeric')->andReturn(1.0);
        $old_value->shouldReceive('isManualValue')->andReturn(true);

        $submitted_value = [
            'manual_value'    => '',
            'is_autocomputed' => true
        ];

        $this->assertTrue($field->hasChanges($artifact, $old_value, $submitted_value));
    }

    public function testItDetectsChangeWhenBackToManualValue(): void
    {
        $field    = $this->getComputedField();
        $artifact = Mockery::mock(Tracker_Artifact::class);

        $old_value = Mockery::mock(ChangesetValueComputed::class);
        $old_value->shouldReceive('getNumeric')->andReturn(null);
        $old_value->shouldReceive('isManualValue')->andReturn(false);
        $submitted_value = [
            'manual_value'    => '123',
            'is_autocomputed' => false
        ];

        $this->assertTrue($field->hasChanges($artifact, $old_value, $submitted_value));
    }

    public function testItDetectsChangeWhenBackToAutocomputeWhenManualValueIs0(): void
    {
        $field    = $this->getComputedField();
        $artifact = Mockery::mock(Tracker_Artifact::class);

        $old_value = Mockery::mock(ChangesetValueComputed::class);
        $old_value->shouldReceive('getNumeric')->andReturn(0.0);
        $old_value->shouldReceive('isManualValue')->andReturn(true);

        $submitted_value = [
            'manual_value'    => '',
            'is_autocomputed' => true
        ];

        $this->assertTrue($field->hasChanges($artifact, $old_value, $submitted_value));
    }

    public function testItHasChangesWhenANewManualValueIsSet(): void
    {
        $field    = $this->getComputedField();
        $artifact = Mockery::mock(Tracker_Artifact::class);

        $old_value = Mockery::mock(ChangesetValueComputed::class);
        $old_value->shouldReceive('getNumeric')->andReturn(7.0);
        $old_value->shouldReceive('isManualValue')->andReturn(false);
        $submitted_value = [
            'is_autocomputed' => '',
            'manual_value'    => 5
        ];

        $this->assertTrue($field->hasChanges($artifact, $old_value, $submitted_value));
    }

    public function testItHasNotChangesWhenANewManualValueIsEqualToOldChangesetValue(): void
    {
        $field    = $this->getComputedField();
        $artifact = Mockery::mock(Tracker_Artifact::class);

        $old_value = Mockery::mock(ChangesetValueComputed::class);
        $old_value->shouldReceive('getNumeric')->andReturn(7.0);
        $old_value->shouldReceive('isManualValue')->andReturn(false);
        $submitted_value = [
            'is_autocomputed' => '',
            'manual_value'    => 7
        ];

        $this->assertFalse($field->hasChanges($artifact, $old_value, $submitted_value));
    }

    public function testItHasNotChangesIfYouAreStillInAutocomputedMode(): void
    {
        $field    = $this->getComputedField();
        $artifact = Mockery::mock(Tracker_Artifact::class);

        $old_value = Mockery::mock(ChangesetValueComputed::class);
        $old_value->shouldReceive('getNumeric')->andReturn(null);
        $old_value->shouldReceive('isManualValue')->andReturn(false);

        $submitted_value = [
            'is_autocomputed' => '1',
            'manual_value'    => ''
        ];

        $this->assertFalse($field->hasChanges($artifact, $old_value, $submitted_value));
    }

    public function testItHasNotChangesIfYouAreStillInAutocomputedModeWithAProvidedManualValueByHTMLForm(): void
    {
        $field    = $this->getComputedField();
        $artifact = Mockery::mock(Tracker_Artifact::class);

        $old_value = Mockery::mock(ChangesetValueComputed::class);
        $old_value->shouldReceive('getNumeric')->andReturn(null);
        $old_value->shouldReceive('isManualValue')->andReturn(false);

        $submitted_value = [
            'is_autocomputed' => '1',
            'manual_value'    => '999999'
        ];

        $this->assertFalse($field->hasChanges($artifact, $old_value, $submitted_value));
    }

    public function testItHasNotChangesWhenANewManualIsAStringAndValueIsEqualToOldChangesetValue(): void
    {
        $field    = $this->getComputedField();
        $artifact = Mockery::mock(Tracker_Artifact::class);

        $old_value = Mockery::mock(ChangesetValueComputed::class);
        $old_value->shouldReceive('getNumeric')->andReturn(7.0);
        $old_value->shouldReceive('isManualValue')->andReturn(false);
        $submitted_value = [
            'is_autocomputed' => '',
            'manual_value'    => '7'
        ];

        $this->assertFalse($field->hasChanges($artifact, $old_value, $submitted_value));
    }

    public function testItCanAdd0ToManualValueFromAutocomputed(): void
    {
        $field    = $this->getComputedField();
        $artifact = Mockery::mock(Tracker_Artifact::class);

        $old_value = Mockery::mock(ChangesetValueComputed::class);
        $old_value->shouldReceive('getNumeric')->andReturn(null);
        $old_value->shouldReceive('isManualValue')->andReturn(false);
        $submitted_value = [
            'is_autocomputed' => '',
            'manual_value'    => '0'
        ];

        $this->assertTrue($field->hasChanges($artifact, $old_value, $submitted_value));
    }

    public function testItCanRetrieveManualValuesWhenArrayIsGiven(): void
    {
        $value_dao = Mockery::mock(ComputedDao::class);
        $user      = Mockery::mock(\PFUser::class);
        $changeset = Mockery::mock(Tracker_Artifact_Changeset::class);

        $field = $this->getComputedFieldForManualValueRetrievement($value_dao, $user, $changeset);

        $value_dao->shouldReceive('create')->withArgs([1234, 20]);

        $value = [
            'manual_value'    => 20,
            'is_autocomputed' => 0
        ];

        $field->saveNewChangeset(
            Mockery::mock(\Tracker_Artifact::class),
            $changeset,
            4444,
            $value,
            $user,
            false,
            false,
            \Mockery::mock(CreatedFileURLMapping::class)
        );
    }

    public function testItCanRetrieveManualValueWhenDataComesFromJson(): void
    {
        $value_dao = Mockery::mock(ComputedDao::class);
        $user      = Mockery::mock(\PFUser::class);
        $changeset = Mockery::mock(Tracker_Artifact_Changeset::class);

        $field = $this->getComputedFieldForManualValueRetrievement($value_dao, $user, $changeset);

        $value = json_encode(
            $value = [
                'manual_value'    => 20,
                'is_autocomputed' => 0
            ]
        );

        $value_dao->shouldReceive('create')->withArgs([1234, 20]);

        $field->saveNewChangeset(
            Mockery::mock(\Tracker_Artifact::class),
            $changeset,
            4444,
            $value,
            $user,
            false,
            false,
            \Mockery::mock(CreatedFileURLMapping::class)
        );
    }

    public function testItRetrieveEmptyValueWhenDataIsIncorrect(): void
    {
        $value_dao = Mockery::mock(ComputedDao::class);
        $user      = Mockery::mock(\PFUser::class);
        $changeset = Mockery::mock(Tracker_Artifact_Changeset::class);

        $field = $this->getComputedFieldForManualValueRetrievement($value_dao, $user, $changeset);

        $value = 'aaa';
        $value_dao->shouldReceive('create')->withArgs([1234, null]);

        $field->saveNewChangeset(
            Mockery::mock(\Tracker_Artifact::class),
            $changeset,
            4444,
            $value,
            $user,
            false,
            false,
            \Mockery::mock(CreatedFileURLMapping::class)
        );
    }


    private function getComputedFieldForManualValueRetrievement($value_dao, $user, $changeset)
    {
        $field = $this->getComputedField();
        $field->shouldReceive('getValueDao')->andReturn($value_dao);
        $old_changset = Mockery::mock(Tracker_Artifact_Changeset_ValueDao::class);
        $field->shouldReceive('getChangesetValueDao')->andReturn($old_changset);
        $field->shouldReceive('userCanUpdate')->andReturn(true);

        $old_changset->shouldReceive('save')->withArgs([4444, Mockery::any(), 1])->once()->andReturn(1234);

        $user->shouldReceive('isSuperUser')->andReturn(false);
        $changeset->shouldReceive('getValue')->once();

        return $field;
    }
}
