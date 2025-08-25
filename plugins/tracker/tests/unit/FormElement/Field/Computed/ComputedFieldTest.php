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

namespace Tuleap\Tracker\FormElement\Field\Computed;

use PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles;
use PHPUnit\Framework\MockObject\MockObject;
use SimpleXMLElement;
use TestHelper;
use Tracker_Artifact_Changeset_ValueDao;
use Tracker_FormElement_InvalidFieldValueException;
use Tuleap\GlobalLanguageMock;
use Tuleap\GlobalResponseMock;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\DAO\ComputedDao;
use Tuleap\Tracker\FormElement\Field\Files\CreatedFileURLMapping;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;
use Tuleap\Tracker\Test\Builders\ChangesetTestBuilder;
use Tuleap\Tracker\Test\Builders\ChangesetValueComputedTestBuilder;
use Tuleap\Tracker\Test\Builders\Fields\ComputedFieldBuilder;
use Tuleap\User\CurrentUserWithLoggedInInformation;
use UserManager;

#[DisableReturnValueGenerationForTestDoubles]
final class ComputedFieldTest extends TestCase
{
    use GlobalResponseMock;
    use GlobalLanguageMock;

    public function testItExportsDefaultValueInXML(): void
    {
        $computed_field = ComputedFieldBuilder::aComputedField(123)->withSpecificProperty('default_value', ['value' => '12.34'])->build();

        $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><field />');
        $computed_field->exportPropertiesToXML($xml);

        self::assertSame('12.34', (string) $xml->properties['default_value']);
    }

    public function testItDoesNotExportNullDefaultValueInXML(): void
    {
        $computed_field = ComputedFieldBuilder::aComputedField(123)->build();

        $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><field />');
        $computed_field->exportPropertiesToXML($xml);

        self::assertFalse(isset($xml->properties));
    }

    public function testItReturnsValueWhenCorrectlyFormatted(): void
    {
        $field = ComputedFieldBuilder::aComputedField(123)->build();
        $value = [ComputedField::FIELD_VALUE_IS_AUTOCOMPUTED => true];

        self::assertEquals($value, $field->getFieldDataFromRESTValue($value));
    }

    public function testItRejectsDataWhenAutocomputedIsDisabledAndNoManualValueIsProvided(): void
    {
        $field = ComputedFieldBuilder::aComputedField(123)->build();
        $this->expectException(Tracker_FormElement_InvalidFieldValueException::class);
        $value = [ComputedField::FIELD_VALUE_IS_AUTOCOMPUTED => false];
        $field->getFieldDataFromRESTValue($value);
    }

    public function testItRejectsDataWhenAutocomputedIsDisabledAndManualValueIsNull(): void
    {
        $field = ComputedFieldBuilder::aComputedField(123)->build();
        $this->expectException(Tracker_FormElement_InvalidFieldValueException::class);
        $value = [
            ComputedField::FIELD_VALUE_IS_AUTOCOMPUTED => false,
            ComputedField::FIELD_VALUE_MANUAL          => null,
        ];
        $field->getFieldDataFromRESTValue($value);
    }

    public function testItRejectsDataWhenValueIsSet(): void
    {
        $field = ComputedFieldBuilder::aComputedField(123)->build();
        $this->expectException(Tracker_FormElement_InvalidFieldValueException::class);
        $value = ['value' => 1];
        $field->getFieldDataFromRESTValue($value);
    }

    public function testItExpectsAnArray(): void
    {
        $field = ComputedFieldBuilder::aComputedField(123)->build();
        self::assertFalse($field->validateValue('String'));
        self::assertFalse($field->validateValue(1));
        self::assertFalse($field->validateValue(1.1));
        self::assertFalse($field->validateValue(true));
        self::assertTrue($field->validateValue([ComputedField::FIELD_VALUE_IS_AUTOCOMPUTED => true]));
    }

    public function testItExpectsAtLeastAValueOrAnAutocomputedInformation(): void
    {
        $field = ComputedFieldBuilder::aComputedField(123)->build();
        self::assertFalse($field->validateValue([]));
        self::assertFalse($field->validateValue(['v1' => 1]));
        self::assertFalse($field->validateValue([ComputedField::FIELD_VALUE_IS_AUTOCOMPUTED]));
        self::assertFalse($field->validateValue([ComputedField::FIELD_VALUE_MANUAL]));
        self::assertFalse($field->validateValue([
            ComputedField::FIELD_VALUE_MANUAL,
            ComputedField::FIELD_VALUE_IS_AUTOCOMPUTED,
        ]));
        self::assertTrue($field->validateValue([ComputedField::FIELD_VALUE_MANUAL => 1]));
        self::assertTrue($field->validateValue([ComputedField::FIELD_VALUE_IS_AUTOCOMPUTED => true]));
    }

    public function testItExpectsAFloatOrAIntAsManualValue(): void
    {
        $field = ComputedFieldBuilder::aComputedField(123)->build();
        self::assertFalse($field->validateValue([ComputedField::FIELD_VALUE_MANUAL => 'String']));
        self::assertTrue($field->validateValue([ComputedField::FIELD_VALUE_MANUAL => 1.1]));
        self::assertTrue($field->validateValue([ComputedField::FIELD_VALUE_MANUAL => 0]));
    }

    public function testItCanNotAcceptAManualValueWhenAutocomputedIsEnabled(): void
    {
        $field = ComputedFieldBuilder::aComputedField(123)->build();
        self::assertFalse($field->validateValue([
            ComputedField::FIELD_VALUE_MANUAL          => 1,
            ComputedField::FIELD_VALUE_IS_AUTOCOMPUTED => true,
        ]));
        self::assertTrue($field->validateValue([
            ComputedField::FIELD_VALUE_MANUAL          => 1,
            ComputedField::FIELD_VALUE_IS_AUTOCOMPUTED => false,
        ]));
        self::assertFalse($field->validateValue([
            ComputedField::FIELD_VALUE_MANUAL          => '',
            ComputedField::FIELD_VALUE_IS_AUTOCOMPUTED => false,
        ]));
        self::assertTrue($field->validateValue([
            ComputedField::FIELD_VALUE_MANUAL          => '',
            ComputedField::FIELD_VALUE_IS_AUTOCOMPUTED => true,
        ]));
    }

    public function testItIsValidWhenTheFieldIsRequiredAndIsAutocomputed(): void
    {
        $user            = UserTestBuilder::buildWithDefaults();
        $field           = ComputedFieldBuilder::aComputedField(123)->thatIsRequired()->withUpdatePermission($user, true)->build();
        $artifact        = ArtifactTestBuilder::anArtifact(233)->build();
        $submitted_value = [
            'manual_value'    => '',
            'is_autocomputed' => true,
        ];
        UserManager::instance()->setCurrentUser(CurrentUserWithLoggedInInformation::fromLoggedInUser($user));

        self::assertTrue($field->validateFieldWithPermissionsAndRequiredStatus($artifact, $submitted_value, $user));
    }

    public function testItIsValidWhenTheFieldIsNotRequiredAndIsAutocomputed(): void
    {
        $user            = UserTestBuilder::buildWithDefaults();
        $field           = ComputedFieldBuilder::aComputedField(123)->withUpdatePermission($user, true)->build();
        $artifact        = ArtifactTestBuilder::anArtifact(233)->build();
        $submitted_value = [
            'manual_value'    => '',
            'is_autocomputed' => true,
        ];
        UserManager::instance()->setCurrentUser(CurrentUserWithLoggedInInformation::fromLoggedInUser($user));

        self::assertTrue($field->validateFieldWithPermissionsAndRequiredStatus($artifact, $submitted_value, $user));
    }

    public function testItIsValidWhenTheFieldIsRequiredAndHasAManualValue(): void
    {
        $user            = UserTestBuilder::buildWithDefaults();
        $field           = ComputedFieldBuilder::aComputedField(123)->thatIsRequired()->withUpdatePermission($user, true)->build();
        $artifact        = ArtifactTestBuilder::anArtifact(233)->build();
        $submitted_value = [
            'manual_value' => '11',
        ];
        UserManager::instance()->setCurrentUser(CurrentUserWithLoggedInInformation::fromLoggedInUser($user));

        self::assertTrue($field->validateFieldWithPermissionsAndRequiredStatus($artifact, $submitted_value, $user));
    }

    public function testItIsNotValidWhenTheFieldIsRequiredAndDoesntHaveAManualValue(): void
    {
        $user            = UserTestBuilder::buildWithDefaults();
        $field           = ComputedFieldBuilder::aComputedField(123)->thatIsRequired()->withUpdatePermission($user, true)->build();
        $artifact        = ArtifactTestBuilder::anArtifact(233)->build();
        $submitted_value = [
            'manual_value' => '',
        ];
        UserManager::instance()->setCurrentUser(CurrentUserWithLoggedInInformation::fromLoggedInUser($user));

        self::assertFalse($field->validateFieldWithPermissionsAndRequiredStatus($artifact, $submitted_value, $user));
    }

    public function testItIsNotValidWhenTheFieldIsNotRequiredAndDoesntHaveAManualValue(): void
    {
        $user            = UserTestBuilder::buildWithDefaults();
        $field           = ComputedFieldBuilder::aComputedField(123)->withUpdatePermission($user, true)->build();
        $artifact        = ArtifactTestBuilder::anArtifact(233)->build();
        $submitted_value = [
            'manual_value' => '',
        ];
        UserManager::instance()->setCurrentUser(CurrentUserWithLoggedInInformation::fromLoggedInUser($user));

        self::assertFalse($field->validateFieldWithPermissionsAndRequiredStatus($artifact, $submitted_value, $user));
    }

    public function testItIsValidWhenNoValuesAreSubmitted(): void
    {
        $user            = UserTestBuilder::buildWithDefaults();
        $field           = ComputedFieldBuilder::aComputedField(123)->withUpdatePermission($user, true)->build();
        $artifact        = ArtifactTestBuilder::anArtifact(233)->build();
        $submitted_value = [];
        UserManager::instance()->setCurrentUser(CurrentUserWithLoggedInInformation::fromLoggedInUser($user));

        self::assertTrue($field->validateFieldWithPermissionsAndRequiredStatus($artifact, $submitted_value, $user));
    }

    public function testItDisplaysEmptyWhenFieldsAreAutocomputedAndNoValuesAreSet(): void
    {
        $dao       = $this->createMock(ComputedFieldDao::class);
        $value_dao = $this->createMock(ComputedDao::class);

        $artifact = $this->getArtifactWithChangeset();
        $field    = $this->getComputedFieldForManualComputationTests($dao, $value_dao);

        $value_one = ['id' => 766, 'artifact_link_id' => 766, 'type' => 'computed'];
        $value_two = ['id' => 777, 'artifact_link_id' => 777, 'type' => 'computed'];

        $values = TestHelper::arrayToDar($value_one, $value_two);
        $dao->method('getComputedFieldValues')->with([233], 'effort', 23, false)->willReturn($values);

        $value_dao->method('getManuallySetValueForChangeset')->with(101, 23)->willReturn(['value' => null]);
        $field->expects($this->once())->method('getStandardCalculationMode');
        $field->expects($this->never())->method('getStopAtManualSetFieldMode');

        $field->getComputedValueWithNoStopOnManualValue($artifact);
    }

    public function testItDisplaysComputedValuesWhenComputedChildrenAreSet(): void
    {
        $dao       = $this->createMock(ComputedFieldDao::class);
        $value_dao = $this->createMock(ComputedDao::class);

        $artifact = $this->getArtifactWithChangeset();
        $field    = $this->getComputedFieldForManualComputationTests($dao, $value_dao);

        $value_one = ['id' => 766, 'artifact_link_id' => 766, 'type' => 'computed', 'value' => 10];
        $value_two = ['id' => 777, 'artifact_link_id' => 777, 'type' => 'computed', 'value' => 5];
        $values    = TestHelper::arrayToDar($value_one, $value_two);
        $dao->method('getComputedFieldValues')->with([233], 'effort', 23, false)->willReturn($values);

        $value_dao->method('getManuallySetValueForChangeset')->with(101, 23)->willReturn(['value' => null]);
        $field->expects($this->once())->method('getStandardCalculationMode');
        $field->expects($this->never())->method('getStopAtManualSetFieldMode');

        $field->getComputedValueWithNoStopOnManualValue($artifact);
    }

    public function testItCallsStandardCalculWhenFieldsAreIntAndNoValuesAreSet(): void
    {
        $dao       = $this->createMock(ComputedFieldDao::class);
        $value_dao = $this->createMock(ComputedDao::class);

        $artifact = $this->getArtifactWithChangeset();
        $field    = $this->getComputedFieldForManualComputationTests($dao, $value_dao);

        $value_one = ['id' => 750, 'type' => 'int'];
        $value_two = ['id' => 75, 'type' => 'int'];
        $values    = TestHelper::arrayToDar($value_one, $value_two);
        $dao->method('getComputedFieldValues')->with([233], 'effort', 23, false)->willReturn($values);

        $value_dao->method('getManuallySetValueForChangeset')->willReturn(['value' => null]);
        $field->expects($this->once())->method('getStandardCalculationMode');
        $field->expects($this->never())->method('getStopAtManualSetFieldMode');

        $field->getComputedValueWithNoStopOnManualValue($artifact);
    }

    public function testItCallsStandardCalculWhenFieldsAreComputedAndNoValuesAreSet(): void
    {
        $dao       = $this->createMock(ComputedFieldDao::class);
        $value_dao = $this->createMock(ComputedDao::class);

        $artifact = $this->getArtifactWithChangeset();
        $field    = $this->getComputedFieldForManualComputationTests($dao, $value_dao);

        $value_one = ['id' => 766, 'type' => 'computed'];
        $value_two = ['id' => 777, 'type' => 'computed'];
        $values    = TestHelper::arrayToDar($value_one, $value_two);
        $dao->method('getComputedFieldValues')->with([233], 'effort', 23, false)->willReturn($values);

        $value_dao->method('getManuallySetValueForChangeset')->willReturn(['value' => null]);
        $field->expects($this->once())->method('getStandardCalculationMode');
        $field->expects($this->never())->method('getStopAtManualSetFieldMode');

        $field->getComputedValueWithNoStopOnManualValue($artifact);
    }

    public function testItCallsStandardCalculWhenAComputedValueIsSet(): void
    {
        $dao       = $this->createMock(ComputedFieldDao::class);
        $value_dao = $this->createMock(ComputedDao::class);

        $artifact = $this->getArtifactWithChangeset();
        $field    = $this->getComputedFieldForManualComputationTests($dao, $value_dao);

        $value_one = ['id' => 766, 'type' => 'computed', 'value' => '6'];
        $value_two = ['id' => 777, 'type' => 'computed'];

        $values = TestHelper::arrayToDar($value_one, $value_two);
        $dao->method('getComputedFieldValues')->with([233], 'effort', 23, false)->willReturn($values);

        $value_dao->method('getManuallySetValueForChangeset')->willReturn(['value' => null]);
        $field->expects($this->once())->method('getStandardCalculationMode');
        $field->expects($this->never())->method('getStopAtManualSetFieldMode');

        $field->getComputedValueWithNoStopOnManualValue($artifact);
    }

    public function testItCallsStandardCalculWhenIntFieldsAreSet(): void
    {
        $dao       = $this->createMock(ComputedFieldDao::class);
        $value_dao = $this->createMock(ComputedDao::class);

        $artifact = $this->getArtifactWithChangeset();
        $field    = $this->getComputedFieldForManualComputationTests($dao, $value_dao);

        $value_one = ['id' => 750, 'type' => 'int', 'int_value' => 10];
        $value_two = ['id' => 751, 'type' => 'int', 'int_value' => 5];
        $values    = TestHelper::arrayToDar($value_one, $value_two);
        $dao->method('getComputedFieldValues')->with([233], 'effort', 23, false)->willReturn($values);

        $value_dao->method('getManuallySetValueForChangeset')->willReturn(['value' => null]);
        $field->expects($this->once())->method('getStandardCalculationMode');
        $field->expects($this->never())->method('getStopAtManualSetFieldMode');

        $field->getComputedValueWithNoStopOnManualValue($artifact);
    }

    public function testItReturnsNullWhenNoManualValueIsSetAndNoChildrenExists(): void
    {
        $dao       = $this->createMock(ComputedFieldDao::class);
        $value_dao = $this->createMock(ComputedDao::class);

        $artifact = $this->getArtifactWithChangeset();
        $field    = $this->getComputedFieldForManualComputationTests($dao, $value_dao);

        $dao->method('getComputedFieldValues')->with([233], 'effort', 23, false)->willReturn(null);

        $value_dao->method('getManuallySetValueForChangeset')->willReturn(['value' => null]);
        $field->expects($this->never())->method('getStopAtManualSetFieldMode');
        $field->expects($this->never())->method('getStandardCalculationMode');

        $result = $field->getComputedValueWithNoStopOnManualValue($artifact);

        self::assertNull($result);
    }

    public function testItCalculatesAutocomputedAndintFieldsEvenIfAParentIsSet(): void
    {
        $dao       = $this->createMock(ComputedFieldDao::class);
        $value_dao = $this->createMock(ComputedDao::class);

        $artifact = $this->getArtifactWithChangeset();
        $field    = $this->getComputedFieldForManualComputationTests($dao, $value_dao);

        $value_one   = ['id' => 750, 'type' => 'int', 'int_value' => 10];
        $value_two   = ['id' => 751, 'type' => 'int', 'int_value' => 5];
        $value_three = ['id' => 766, 'type' => 'computed', 'value' => '6'];
        $value_four  = ['id' => 777, 'type' => 'computed', 'value' => '6'];
        $values      = TestHelper::arrayToDar($value_one, $value_two, $value_three, $value_four);
        $dao->method('getComputedFieldValues')->with([233], 'effort', 23, false)->willReturn($values);

        $value_dao->method('getManuallySetValueForChangeset')->willReturn(['value' => 12]);
        $field->expects($this->once())->method('getStopAtManualSetFieldMode');
        $field->expects($this->once())->method('getStandardCalculationMode');

        $field->getComputedValueWithNoStopOnManualValue($artifact);
    }

    public function testItReturnsComputedValuesAndIntValuesWhenBothAreOneSameChildrenLevel(): void
    {
        $dao       = $this->createMock(ComputedFieldDao::class);
        $value_dao = $this->createMock(ComputedDao::class);

        $artifact = $this->getArtifactWithChangeset();
        $field    = $this->getComputedFieldForManualComputationTests($dao, $value_dao);

        $value_one   = ['id' => 750, 'type' => 'int', 'int_value' => 10];
        $value_two   = ['id' => 751, 'type' => 'int', 'int_value' => 5];
        $value_three = ['id' => 766, 'type' => 'computed', 'value' => '6'];
        $value_four  = ['id' => 777, 'type' => 'computed', 'value' => '6'];
        $values      = TestHelper::arrayToDar($value_one, $value_two, $value_three, $value_four);
        $dao->method('getComputedFieldValues')->with([233], 'effort', 23, false)->willReturn($values);

        $value_dao->method('getManuallySetValueForChangeset')->willReturn(['value' => null]);
        $field->expects($this->never())->method('getStopAtManualSetFieldMode');
        $field->expects($this->once())->method('getStandardCalculationMode');

        $field->getComputedValueWithNoStopOnManualValue($artifact);
    }

    private function getArtifactWithChangeset(): Artifact
    {
        return ArtifactTestBuilder::anArtifact(233)
            ->withChangesets(ChangesetTestBuilder::aChangeset(101)->build())
            ->build();
    }

    private function getComputedFieldForManualComputationTests(ComputedFieldDao $dao, ComputedDao $value_dao): ComputedField&MockObject
    {
        $field = $this->createPartialMock(ComputedField::class, [
            'getDao', 'getId', 'getName', 'getValueDao', 'getStandardCalculationMode', 'getStopAtManualSetFieldMode',
        ]);
        $field->method('getDao')->willReturn($dao);
        $field->method('getId')->willReturn(23);
        $field->method('getName')->willReturn('effort');
        $field->method('getValueDao')->willReturn($value_dao);

        return $field;
    }

    public function testItDetectsChangeWhenBackToAutocompute(): void
    {
        $field    = ComputedFieldBuilder::aComputedField(123)->build();
        $artifact = ArtifactTestBuilder::anArtifact(654)->build();

        $old_value = ChangesetValueComputedTestBuilder::aValue(1, ChangesetTestBuilder::aChangeset(456)->build(), $field)
            ->withValue(1.0)
            ->withIsManualValue(true)
            ->build();

        $submitted_value = [
            'manual_value'    => '',
            'is_autocomputed' => true,
        ];

        self::assertTrue($field->hasChanges($artifact, $old_value, $submitted_value));
    }

    public function testItDetectsChangeWhenBackToManualValue(): void
    {
        $field    = ComputedFieldBuilder::aComputedField(123)->build();
        $artifact = ArtifactTestBuilder::anArtifact(654)->build();

        $old_value       = ChangesetValueComputedTestBuilder::aValue(1, ChangesetTestBuilder::aChangeset(456)->build(), $field)
            ->withValue(null)
            ->withIsManualValue(false)
            ->build();
        $submitted_value = [
            'manual_value'    => '123',
            'is_autocomputed' => false,
        ];

        self::assertTrue($field->hasChanges($artifact, $old_value, $submitted_value));
    }

    public function testItDetectsChangeWhenBackToAutocomputeWhenManualValueIs0(): void
    {
        $field    = ComputedFieldBuilder::aComputedField(123)->build();
        $artifact = ArtifactTestBuilder::anArtifact(654)->build();

        $old_value = ChangesetValueComputedTestBuilder::aValue(1, ChangesetTestBuilder::aChangeset(456)->build(), $field)
            ->withValue(0.0)
            ->withIsManualValue(true)
            ->build();

        $submitted_value = [
            'manual_value'    => '',
            'is_autocomputed' => true,
        ];

        self::assertTrue($field->hasChanges($artifact, $old_value, $submitted_value));
    }

    public function testItHasChangesWhenANewManualValueIsSet(): void
    {
        $field    = ComputedFieldBuilder::aComputedField(123)->build();
        $artifact = ArtifactTestBuilder::anArtifact(654)->build();

        $old_value       = ChangesetValueComputedTestBuilder::aValue(1, ChangesetTestBuilder::aChangeset(456)->build(), $field)
            ->withValue(7.0)
            ->withIsManualValue(false)
            ->build();
        $submitted_value = [
            'is_autocomputed' => '',
            'manual_value'    => 5,
        ];

        self::assertTrue($field->hasChanges($artifact, $old_value, $submitted_value));
    }

    public function testItHasNotChangesWhenANewManualValueIsEqualToOldChangesetValue(): void
    {
        $field    = ComputedFieldBuilder::aComputedField(123)->build();
        $artifact = ArtifactTestBuilder::anArtifact(654)->build();

        $old_value       = ChangesetValueComputedTestBuilder::aValue(1, ChangesetTestBuilder::aChangeset(456)->build(), $field)
            ->withValue(7.0)
            ->withIsManualValue(false)
            ->build();
        $submitted_value = [
            'is_autocomputed' => '',
            'manual_value'    => 7,
        ];

        self::assertFalse($field->hasChanges($artifact, $old_value, $submitted_value));
    }

    public function testItHasNotChangesIfYouAreStillInAutocomputedMode(): void
    {
        $field    = ComputedFieldBuilder::aComputedField(123)->build();
        $artifact = ArtifactTestBuilder::anArtifact(654)->build();

        $old_value = ChangesetValueComputedTestBuilder::aValue(1, ChangesetTestBuilder::aChangeset(456)->build(), $field)
            ->withValue(null)
            ->withIsManualValue(false)
            ->build();

        $submitted_value = [
            'is_autocomputed' => '1',
            'manual_value'    => '',
        ];

        self::assertFalse($field->hasChanges($artifact, $old_value, $submitted_value));
    }

    public function testItHasNotChangesIfYouAreStillInAutocomputedModeWithAProvidedManualValueByHTMLForm(): void
    {
        $field    = ComputedFieldBuilder::aComputedField(123)->build();
        $artifact = ArtifactTestBuilder::anArtifact(654)->build();

        $old_value = ChangesetValueComputedTestBuilder::aValue(1, ChangesetTestBuilder::aChangeset(456)->build(), $field)
            ->withValue(null)
            ->withIsManualValue(false)
            ->build();

        $submitted_value = [
            'is_autocomputed' => '1',
            'manual_value'    => '999999',
        ];

        self::assertFalse($field->hasChanges($artifact, $old_value, $submitted_value));
    }

    public function testItHasNotChangesWhenANewManualIsAStringAndValueIsEqualToOldChangesetValue(): void
    {
        $field    = ComputedFieldBuilder::aComputedField(123)->build();
        $artifact = ArtifactTestBuilder::anArtifact(654)->build();

        $old_value       = ChangesetValueComputedTestBuilder::aValue(1, ChangesetTestBuilder::aChangeset(456)->build(), $field)
            ->withValue(7.0)
            ->withIsManualValue(false)
            ->build();
        $submitted_value = [
            'is_autocomputed' => '',
            'manual_value'    => '7',
        ];

        self::assertFalse($field->hasChanges($artifact, $old_value, $submitted_value));
    }

    public function testItCanAdd0ToManualValueFromAutocomputed(): void
    {
        $field    = ComputedFieldBuilder::aComputedField(123)->build();
        $artifact = ArtifactTestBuilder::anArtifact(654)->build();

        $old_value       = ChangesetValueComputedTestBuilder::aValue(1, ChangesetTestBuilder::aChangeset(456)->build(), $field)
            ->withValue(null)
            ->withIsManualValue(false)
            ->build();
        $submitted_value = [
            'is_autocomputed' => '',
            'manual_value'    => '0',
        ];

        self::assertTrue($field->hasChanges($artifact, $old_value, $submitted_value));
    }

    public function testItCanRetrieveManualValuesWhenArrayIsGiven(): void
    {
        $value_dao = $this->createMock(ComputedDao::class);
        $user      = UserTestBuilder::buildWithDefaults();
        $changeset = ChangesetTestBuilder::aChangeset(101)->build();

        $field = $this->getComputedFieldForManualValueRetrievement($value_dao);
        $changeset->setFieldValue($field, null);

        $value_dao->method('create')->with(1234, 20);

        $value = [
            'manual_value'    => 20,
            'is_autocomputed' => 0,
        ];

        $field->saveNewChangeset(
            ArtifactTestBuilder::anArtifact(654)->build(),
            $changeset,
            4444,
            $value,
            $user,
            false,
            false,
            new CreatedFileURLMapping(),
        );
    }

    public function testItCanRetrieveManualValueWhenDataComesFromJson(): void
    {
        $value_dao = $this->createMock(ComputedDao::class);
        $user      = UserTestBuilder::buildWithDefaults();
        $changeset = ChangesetTestBuilder::aChangeset(68771)->build();

        $field = $this->getComputedFieldForManualValueRetrievement($value_dao);
        $changeset->setFieldValue($field, null);

        $value = json_encode([
            'manual_value'    => 20,
            'is_autocomputed' => 0,
        ]);

        $value_dao->method('create')->with(1234, 20);

        $field->saveNewChangeset(
            ArtifactTestBuilder::anArtifact(654)->build(),
            $changeset,
            4444,
            $value,
            $user,
            false,
            false,
            new CreatedFileURLMapping(),
        );
    }

    public function testItRetrieveEmptyValueWhenDataIsIncorrect(): void
    {
        $value_dao = $this->createMock(ComputedDao::class);
        $user      = UserTestBuilder::buildWithDefaults();
        $changeset = ChangesetTestBuilder::aChangeset(9697854)->build();

        $field = $this->getComputedFieldForManualValueRetrievement($value_dao);
        $changeset->setFieldValue($field, null);

        $value = 'aaa';
        $value_dao->method('create')->with(1234, null);

        $field->saveNewChangeset(
            ArtifactTestBuilder::anArtifact(654)->build(),
            $changeset,
            4444,
            $value,
            $user,
            false,
            false,
            new CreatedFileURLMapping(),
        );
    }

    private function getComputedFieldForManualValueRetrievement(ComputedDao $value_dao): ComputedField
    {
        $field = $this->createPartialMock(ComputedField::class, [
            'getValueDao', 'getChangesetValueDao', 'userCanUpdate',
        ]);
        $field->method('getValueDao')->willReturn($value_dao);
        $old_changset = $this->createMock(Tracker_Artifact_Changeset_ValueDao::class);
        $field->method('getChangesetValueDao')->willReturn($old_changset);
        $field->method('userCanUpdate')->willReturn(true);

        $old_changset->expects($this->once())->method('save')->with(4444, self::anything(), 1)->willReturn(1234);

        return $field;
    }
}
