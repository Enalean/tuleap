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

declare(strict_types=1);

namespace Tuleap\Tracker\FormElement\Field;

use HTTPRequest;
use PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles;
use PHPUnit\Framework\MockObject\MockObject;
use SimpleXMLElement;
use TestHelper;
use Tracker_Artifact_ChangesetValue_List;
use Tracker_FormElement_Field_List_Bind_Static;
use Tracker_FormElement_Field_List_BindFactory;
use Tracker_FormElement_Field_List_BindValue;
use Tracker_FormElement_InvalidFieldValueException;
use Tracker_FormElement_RESTValueByField_NotImplementedException;
use Tracker_IDisplayTrackerLayout;
use Tracker_Report_Criteria;
use Tracker_Report_InvalidRESTCriterionException;
use Tracker_Report_REST;
use Tuleap\GlobalLanguageMock;
use Tuleap\GlobalResponseMock;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Test\Stubs\User\XML\Import\IFindUserFromXMLReferenceStub;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\FormElement\Field\ListFields\ListValueDao;
use Tuleap\Tracker\FormElement\FieldSpecificProperties\ListFieldSpecificPropertiesDAO;
use Tuleap\Tracker\FormElement\TransitionListValidator;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;
use Tuleap\Tracker\Test\Builders\ChangesetTestBuilder;
use Tuleap\Tracker\Test\Builders\ChangesetValueListTestBuilder;
use Tuleap\Tracker\Test\Builders\Fields\List\ListStaticValueBuilder;
use Tuleap\Tracker\Test\Builders\ReportTestBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use Tuleap\Tracker\XML\TrackerXmlImportFeedbackCollector;
use Workflow;

#[DisableReturnValueGenerationForTestDoubles]
final class ListFieldTest extends TestCase
{
    use GlobalResponseMock;
    use GlobalLanguageMock;

    private Tracker_Artifact_ChangesetValue_List&MockObject $changeset_value;
    private ListField&MockObject $list_field;
    private Tracker_FormElement_Field_List_BindValue $bind_value;
    private Tracker_FormElement_Field_List_Bind_Static&MockObject $bind;
    private ListValueDao&MockObject $value_dao;

    #[\Override]
    protected function setUp(): void
    {
        $this->list_field      = $this->createPartialMock(ListField::class, [
            'getValueDao', 'getBind', 'isNone', 'getFactoryLabel', 'getFactoryDescription', 'getFactoryIconUseIt',
            'getFactoryIconCreate', 'accept', 'isAlwaysInEditMode', 'fieldHasEnableWorkflow', 'getWorkflow',
            'getBindFactory', 'isRequired', 'getTransitionListValidator', 'getListDao', 'getId',
        ]);
        $this->value_dao       = $this->createMock(ListValueDao::class);
        $this->changeset_value = $this->createMock(Tracker_Artifact_ChangesetValue_List::class);
        $this->bind            = $this->createMock(Tracker_FormElement_Field_List_Bind_Static::class);
        $this->bind_value      = ListStaticValueBuilder::aStaticValue('value')->build();

        $this->list_field->method('getValueDao')->willReturn($this->value_dao);
        $this->list_field->method('getBind')->willReturn($this->bind);
        $this->list_field->method('getId')->willReturn(66);
    }

    #[\Override]
    protected function tearDown(): void
    {
        unset($GLOBALS['_SESSION']);
    }

    public function testGetChangesetValue(): void
    {
        $this->value_dao->method('searchById')->willReturn(TestHelper::arrayToDar(
            ['id' => '123', 'field_id' => '1', 'bindvalue_id' => '1000'],
            ['id' => '123', 'field_id' => '1', 'bindvalue_id' => '1001'],
            ['id' => '123', 'field_id' => '1', 'bindvalue_id' => '1002']
        ));

        $this->bind->method('getBindValues')->willReturn(array_fill(0, 3, $this->bind_value));

        $changeset_value = $this->list_field->getChangesetValue(
            ChangesetTestBuilder::aChangeset(58)->build(),
            123,
            false
        );
        self::assertInstanceOf(Tracker_Artifact_ChangesetValue_List::class, $changeset_value);
        self::assertIsArray($changeset_value->getListValues());
        self::assertCount(3, $changeset_value->getListValues());
        foreach ($changeset_value->getListValues() as $bv) {
            self::assertInstanceOf(Tracker_FormElement_Field_List_BindValue::class, $bv);
        }
    }

    public function testGetChangesetValueDoesntExist(): void
    {
        $this->value_dao->method('searchById')->willReturn(false);

        $this->bind->method('getBindValues')->willReturn(array_fill(0, 3, $this->bind_value));

        $changeset_value = $this->list_field->getChangesetValue(
            ChangesetTestBuilder::aChangeset(58)->build(),
            123,
            false
        );
        self::assertInstanceOf(Tracker_Artifact_ChangesetValue_List::class, $changeset_value);
        self::assertIsArray($changeset_value->getListValues());
        self::assertCount(0, $changeset_value->getListValues());
    }

    public function testHasChangesNoChangesReverseOrderMSB(): void
    {
        $old_value = ['107', '108'];
        $new_value = ['108', '107'];
        $this->changeset_value->method('getValue')->willReturn($old_value);
        self::assertFalse(
            $this->list_field->hasChanges(ArtifactTestBuilder::anArtifact(456)->build(), $this->changeset_value, $new_value)
        );
    }

    public function testHasChangesNoChangesSameOrderMSB(): void
    {
        $old_value = ['107', '108'];
        $new_value = ['107', '108'];
        $this->changeset_value->method('getValue')->willReturn($old_value);
        self::assertFalse(
            $this->list_field->hasChanges(ArtifactTestBuilder::anArtifact(456)->build(), $this->changeset_value, $new_value)
        );
    }

    public function testHasChangesNoChangeEmptyMSB(): void
    {
        $old_value = [];
        $new_value = [];
        $this->changeset_value->method('getValue')->willReturn($old_value);
        self::assertFalse(
            $this->list_field->hasChanges(ArtifactTestBuilder::anArtifact(456)->build(), $this->changeset_value, $new_value)
        );
    }

    public function testHasChangesNoChangesSB(): void
    {
        $old_value = ['108'];
        $new_value = '108';
        $this->changeset_value->method('getValue')->willReturn($old_value);
        self::assertFalse(
            $this->list_field->hasChanges(ArtifactTestBuilder::anArtifact(456)->build(), $this->changeset_value, $new_value)
        );
    }

    public function testHasChangesChangesMSB(): void
    {
        $old_value = ['107', '108'];
        $new_value = ['107', '110'];
        $this->changeset_value->method('getValue')->willReturn($old_value);
        self::assertTrue(
            $this->list_field->hasChanges(ArtifactTestBuilder::anArtifact(456)->build(), $this->changeset_value, $new_value)
        );
    }

    public function testHasChangesChangesNewMSB(): void
    {
        $old_value = [];
        $new_value = ['107', '110'];
        $this->changeset_value->method('getValue')->willReturn($old_value);
        self::assertTrue(
            $this->list_field->hasChanges(ArtifactTestBuilder::anArtifact(456)->build(), $this->changeset_value, $new_value)
        );
    }

    public function testHasChangesChangesSB(): void
    {
        $old_value = ['107'];
        $new_value = '110';
        $this->changeset_value->method('getValue')->willReturn($old_value);
        self::assertTrue(
            $this->list_field->hasChanges(ArtifactTestBuilder::anArtifact(456)->build(), $this->changeset_value, $new_value)
        );
    }

    public function testHasChangesChangesNewSB(): void
    {
        $old_value = [];
        $new_value = '110';
        $this->changeset_value->method('getValue')->willReturn($old_value);
        self::assertTrue(
            $this->list_field->hasChanges(ArtifactTestBuilder::anArtifact(456)->build(), $this->changeset_value, $new_value)
        );
    }

    public function testTransitionIsValidWhenWorkflowIsNotEnabled(): void
    {
        $this->bind->method('isExistingValue')->willReturn(true);
        $this->list_field->method('fieldHasEnableWorkflow')->willReturn(false);

        self::assertTrue($this->list_field->isValid(ArtifactTestBuilder::anArtifact(65)->build(), 'to'));
    }

    public function testTransitionIsValidWhenTransitionExistsInWorkflow(): void
    {
        $value_from = ListStaticValueBuilder::aStaticValue('from')->build();
        $value_to   = ListStaticValueBuilder::aStaticValue('to')->build();

        $changeset = ChangesetTestBuilder::aChangeset(85)->build();
        $artifact  = ArtifactTestBuilder::anArtifact(65)->withChangesets($changeset)->build();
        $changeset->setFieldValue(
            $this->list_field,
            ChangesetValueListTestBuilder::aListOfValue(1, $changeset, $this->list_field)->withValues([$value_from])->build(),
        );
        $this->bind->method('isExistingValue')->willReturn(true);
        $this->bind->method('getValue')->with('to')->willReturn($value_to);
        $this->list_field->method('fieldHasEnableWorkflow')->willReturn(true);
        $workflow = $this->createMock(Workflow::class);
        $this->list_field->method('getWorkflow')->willReturn($workflow);
        $workflow->method('isTransitionExist')->with($value_from, $value_to)->willReturn(true);
        $validator = $this->createMock(TransitionListValidator::class);
        $validator->expects($this->once())->method('checkTransition')->willReturn(true);
        $this->list_field->method('getTransitionListValidator')->willReturn($validator);

        self::assertTrue($this->list_field->isValid($artifact, 'to'));
    }

    public function testTransitionIsValidWhenTransitionDoesNotExistsInWorkflow(): void
    {
        $value_from = ListStaticValueBuilder::aStaticValue('from')->build();
        $value_to   = ListStaticValueBuilder::aStaticValue('to')->build();

        $changeset = ChangesetTestBuilder::aChangeset(85)->build();
        $artifact  = ArtifactTestBuilder::anArtifact(65)->withChangesets($changeset)->build();
        $changeset->setFieldValue(
            $this->list_field,
            ChangesetValueListTestBuilder::aListOfValue(1, $changeset, $this->list_field)->withValues([$value_from])->build(),
        );
        $this->bind->method('isExistingValue')->willReturn(true);
        $this->bind->method('getValue')->with('to')->willReturn($value_to);
        $this->list_field->method('fieldHasEnableWorkflow')->willReturn(true);
        $workflow = $this->createMock(Workflow::class);
        $this->list_field->method('getWorkflow')->willReturn($workflow);
        $workflow->method('isTransitionExist')->with($value_from, $value_to)->willReturn(false);

        self::assertFalse($this->list_field->isValid($artifact, 'to'));
    }

    public function testItHasErrorWhenValueIsNotAPossibleValue(): void
    {
        $artifact = ArtifactTestBuilder::anArtifact(456)->build();
        $this->bind->method('isExistingValue')->willReturn(false);

        self::assertFalse($this->list_field->isValid($artifact, 'impossible'));
    }

    public function testItHasErrorWhenItIsNotValid(): void
    {
        $artifact = $this->createMock(Artifact::class);
        $artifact->method('getLastChangeset')->willReturn(null);
        $this->bind->method('isExistingValue')->willReturn(true);
        $this->bind->method('getValue')->willReturn(null);
        $workflow = $this->createMock(Workflow::class);
        $workflow->method('isTransitionExist')->willReturn(false);
        $this->list_field->method('getWorkflow')->willReturn($workflow);
        $this->list_field->method('fieldHasEnableWorkflow')->willReturn(true);

        self::assertFalse($this->list_field->isValid($artifact, 'invalid'));
    }

    public function testItIsValid(): void
    {
        $artifact = ArtifactTestBuilder::anArtifact(456)->build();
        $this->bind->method('isExistingValue')->willReturn(true);
        $this->list_field->method('fieldHasEnableWorkflow')->willReturn(false);

        self::assertTrue($this->list_field->isValid($artifact, 'valid'));
    }

    public function testExistingValueOfArrayIsAPossibleValue(): void
    {
        $this->bind->method('isExistingValue')->willReturn(true);
        $this->list_field->method('fieldHasEnableWorkflow')->willReturn(false);
        self::assertTrue($this->list_field->isValid(ArtifactTestBuilder::anArtifact(87)->build(), ['valid']));
    }

    public function testNonExistingValueOfArrayIsNotAPossibleValue(): void
    {
        $this->bind->method('isExistingValue')->willReturn(false);
        self::assertFalse($this->list_field->isValid(ArtifactTestBuilder::anArtifact(87)->build(), ['invalid']));
    }

    public function testExistingStringIsAPossibleValue(): void
    {
        $this->bind->method('isExistingValue')->willReturn(true);
        $this->list_field->method('fieldHasEnableWorkflow')->willReturn(false);
        self::assertTrue($this->list_field->isValid(ArtifactTestBuilder::anArtifact(87)->build(), 'valid'));
    }

    public function testNonExistingStringIsNotAPossibleValue(): void
    {
        $this->bind->method('isExistingValue')->willReturn(false);
        self::assertFalse($this->list_field->isValid(ArtifactTestBuilder::anArtifact(87)->build(), 'invalid'));
    }

    public function testNullIsAPossibleValue(): void
    {
        $this->bind->method('isExistingValue')->willReturn(false);
        $this->list_field->method('fieldHasEnableWorkflow')->willReturn(false);
        self::assertTrue($this->list_field->isValid(ArtifactTestBuilder::anArtifact(87)->build(), null));
    }

    public function testNoneIsAPossibleValue(): void
    {
        $this->bind->method('isExistingValue')->willReturn(false);
        $this->list_field->method('fieldHasEnableWorkflow')->willReturn(false);
        self::assertTrue($this->list_field->isValid(ArtifactTestBuilder::anArtifact(87)->build(), '100'));
    }

    public function testValidateIsOkWhenNoWorkflowIsSet(): void
    {
        $artifact = ArtifactTestBuilder::anArtifact(456)->build();
        $value    = 'value';
        $this->bind->method('isExistingValue')->willReturn(true);
        $this->list_field->method('fieldHasEnableWorkflow')->willReturn(false);

        self::assertTrue($this->list_field->isValid($artifact, $value));
    }

    public function testItChecksTransitionIsValidWhenArtifactDoesNotHaveAnExistingChangeset(): void
    {
        $artifact = $this->createMock(Artifact::class);
        $artifact->method('getLastChangeset')->willReturn(null);
        $this->bind->method('getValue')->willReturn(null);
        $this->bind->method('isExistingValue')->willReturn(true);
        $value = 'value';
        $this->list_field->method('fieldHasEnableWorkflow')->willReturn(true);
        $workflow = $this->createMock(Workflow::class);
        $this->list_field->method('getWorkflow')->willReturn($workflow);
        $workflow->method('isTransitionExist')->willReturn(true);

        $validator = $this->createMock(TransitionListValidator::class);
        $validator->expects($this->once())->method('checkTransition')->willReturn(true);
        $this->list_field->method('getTransitionListValidator')->willReturn($validator);

        self::assertTrue($this->list_field->isValid($artifact, $value));
    }

    public function testItChecksAllFieldsValueWhenLastChangesetHasAValue(): void
    {
        $artifact = $this->createMock(Artifact::class);
        $artifact->method('getLastChangeset')->willReturn(null);
        $this->bind->method('getValue')->willReturn([$this->bind_value]);
        $this->bind->method('isExistingValue')->willReturn(true);
        $value = 'value';
        $this->list_field->method('fieldHasEnableWorkflow')->willReturn(true);
        $worflow = $this->createMock(Workflow::class);
        $this->list_field->method('getWorkflow')->willReturn($worflow);
        $worflow->method('isTransitionExist')->willReturn(true);

        $validator = $this->createMock(TransitionListValidator::class);
        $validator->expects($this->once())->method('checkTransition')->willReturn(true);
        $this->list_field->method('getTransitionListValidator')->willReturn($validator);

        self::assertTrue($this->list_field->isValid($artifact, $value));
    }

    //testing field import
    public function testImportFormElement(): void
    {
        $xml = new SimpleXMLElement(
            '<?xml version="1.0" standalone="yes"?>
         <formElement type="mon_type" ID="F0" rank="20" required="1">
             <name>field_name</name>
             <label>field_label</label>
             <description>field_description</description>
             <bind>
             </bind>
         </formElement>'
        );

        $mapping = [];

        $factory            = $this->createMock(Tracker_FormElement_Field_List_BindFactory::class);
        $user_finder        = IFindUserFromXMLReferenceStub::buildWithUser(UserTestBuilder::buildWithDefaults());
        $feedback_collector = new TrackerXmlImportFeedbackCollector();

        $this->list_field->method('getBindFactory')->willReturn($factory);

        $factory->method('getInstanceFromXML')->willReturn($this->bind);

        $this->list_field->continueGetInstanceFromXML($xml, $mapping, $user_finder, $feedback_collector);
        self::assertEquals($this->bind, $this->list_field->getBind());
    }

    public function testAfterSaveObject(): void
    {
        $tracker = TrackerTestBuilder::aTracker()->build();
        $factory = $this->createMock(Tracker_FormElement_Field_List_BindFactory::class);
        $dao     = $this->createMock(ListFieldSpecificPropertiesDAO::class);

        $this->list_field->method('getBindFactory')->willReturn($factory);
        $this->list_field->method('getListDao')->willReturn($dao);

        $factory->expects($this->once())->method('getType')->with($this->bind)->willReturn('users');

        $this->bind->expects($this->once())->method('saveObject');

        $dao->expects($this->once())->method('saveBindForFieldId')->with(66, 'users');

        $this->list_field->afterSaveObject($tracker, false, false);
    }

    public function testItIsValidWhenIsRequiredAndHaveAValue(): void
    {
        $artifact = ArtifactTestBuilder::anArtifact(456)->build();
        $value    = 102;
        $this->list_field->method('isRequired')->willReturn(true);
        $this->list_field->method('isNone')->willReturn(false);
        self::assertTrue($this->list_field->isValidRegardingRequiredProperty($artifact, $value));
    }

    public function testItIsInvalidWhenIsRequiredAndEmpty(): void
    {
        $artifact = ArtifactTestBuilder::anArtifact(456)->build();
        $value    = 100;
        $this->list_field->method('isRequired')->willReturn(true);
        $this->list_field->method('isNone')->willReturn(true);
        self::assertFalse($this->list_field->isValidRegardingRequiredProperty($artifact, $value));
    }

    public function testItIsValidWhenIsNotRequiredAndEmpty(): void
    {
        $artifact = ArtifactTestBuilder::anArtifact(456)->build();
        $value    = 102;
        $this->list_field->method('isRequired')->willReturn(false);
        $this->list_field->method('isNone')->willReturn(false);
        self::assertTrue($this->list_field->isValidRegardingRequiredProperty($artifact, $value));
    }

    public function testItDoesNothingIfTheRequestDoesNotContainTheParameter(): void
    {
        $layout = $this->createMock(Tracker_IDisplayTrackerLayout::class);
        $user   = UserTestBuilder::buildWithDefaults();

        $request = $this->createStub(HTTPRequest::class);
        $request->method('get')->willReturn('stuff');
        $request->method('isPost')->willReturn(false);

        $this->bind->expects($this->never())->method('fetchFormattedForJson');
        $this->list_field->process($layout, $request, $user);
    }

    public function testItSendsWhateverBindReturns(): void
    {
        $layout = $this->createMock(Tracker_IDisplayTrackerLayout::class);
        $user   = UserTestBuilder::buildWithDefaults();

        $request = $this->createStub(HTTPRequest::class);
        $request->method('get')->willReturn('get-values');
        $request->method('isPost')->willReturn(false);

        $this->bind->expects($this->once())->method('fetchFormattedForJson');
        $this->list_field->process($layout, $request, $user);
    }

    public function testItHasValuesInAdditionToCommonFormat(): void
    {
        $this->bind->expects($this->once())->method('fetchFormattedForJson')->willReturn([]);

        $json = $this->list_field->fetchFormattedForJson();
        self::assertEquals([], $json['values']);
    }

    public function testItThrowsAnExceptionIfValueIsNotUsable(): void
    {
        $this->expectException(Tracker_Report_InvalidRESTCriterionException::class);

        $criteria            = new Tracker_Report_Criteria(65, ReportTestBuilder::aPublicReport()->withId(1)->build(), $this->list_field, 1, false);
        $rest_criteria_value = [
            Tracker_Report_REST::VALUE_PROPERTY_NAME    => [[1234]],
            Tracker_Report_REST::OPERATOR_PROPERTY_NAME => Tracker_Report_REST::OPERATOR_CONTAINS,
        ];

        $this->list_field->setCriteriaValueFromREST($criteria, $rest_criteria_value);
    }

    public function testItThrowsAnExceptionIfValueIsNotANumber(): void
    {
        $this->expectException(Tracker_Report_InvalidRESTCriterionException::class);

        $criteria            = new Tracker_Report_Criteria(65, ReportTestBuilder::aPublicReport()->withId(1)->build(), $this->list_field, 1, false);
        $rest_criteria_value = [
            Tracker_Report_REST::VALUE_PROPERTY_NAME    => 'I am a string',
            Tracker_Report_REST::OPERATOR_PROPERTY_NAME => Tracker_Report_REST::OPERATOR_CONTAINS,
        ];

        $this->list_field->setCriteriaValueFromREST($criteria, $rest_criteria_value);
    }

    public function testItAddsACriterion(): void
    {
        $this->bind->method('getAllValues')->willReturn([101 => 101, 102 => 102, 103 => 103]);
        $criteria = new Tracker_Report_Criteria(65, ReportTestBuilder::aPublicReport()->withId(1)->build(), $this->list_field, 1, false);

        $rest_criteria_value = [
            Tracker_Report_REST::VALUE_PROPERTY_NAME    => '101',
            Tracker_Report_REST::OPERATOR_PROPERTY_NAME => Tracker_Report_REST::OPERATOR_CONTAINS,
        ];

        $set = $this->list_field->setCriteriaValueFromREST($criteria, $rest_criteria_value);
        self::assertTrue($set);

        $res = $this->list_field->getCriteriaValue($criteria);

        self::assertCount(1, $res);
        self::assertContains(101, $res);
    }

    public function testItAddsCriteria(): void
    {
        $this->bind->method('getAllValues')->willReturn([101 => 101, 102 => 102, 103 => 103]);
        $criteria = new Tracker_Report_Criteria(65, ReportTestBuilder::aPublicReport()->withId(1)->build(), $this->list_field, 1, false);

        $rest_criteria_value = [
            Tracker_Report_REST::VALUE_PROPERTY_NAME    => ['101', 103],
            Tracker_Report_REST::OPERATOR_PROPERTY_NAME => Tracker_Report_REST::OPERATOR_CONTAINS,
        ];

        $set = $this->list_field->setCriteriaValueFromREST($criteria, $rest_criteria_value);
        self::assertTrue($set);

        $res = $this->list_field->getCriteriaValue($criteria);

        self::assertCount(2, $res);
        self::assertContains('101', $res);
        self::assertContains(103, $res);
    }

    public function testItThrowsAnExceptionWhenReturningValueIndexedByFieldName(): void
    {
        $this->expectException(Tracker_FormElement_RESTValueByField_NotImplementedException::class);

        $value = ['some_value'];

        $this->list_field->getFieldDataFromRESTValueByField($value);
    }

    public function testItDoesNotAcceptIncorrectValues(): void
    {
        $artifact = ArtifactTestBuilder::anArtifact(456)->build();
        $this->bind->method('isExistingValue')->willReturn(false);
        self::assertFalse($this->list_field->isValid($artifact, 9999));
        self::assertFalse($this->list_field->isValid($artifact, [9998, 9999]));
        self::assertFalse($this->list_field->isValid($artifact, [101, 9999]));
    }

    public function testDoestExportCriteriaInvalidValueToXML(): void
    {
        $xml_element = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><root/>');
        $report      = ReportTestBuilder::aPublicReport()->withId(12)->build();
        $criteria    = new Tracker_Report_Criteria(1, $report, $this->list_field, 1, false);

        $this->bind->method('getValue')->willThrowException(new Tracker_FormElement_InvalidFieldValueException());

        $this->list_field->setCriteriaValue(['404'], 12);

        $this->expectNotToPerformAssertions();
        $this->list_field->exportCriteriaValueToXML($criteria, $xml_element, []);
    }
}
