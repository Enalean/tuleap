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

declare(strict_types = 1);

use Tuleap\Tracker\FormElement\TransitionListValidator;
use Tuleap\Tracker\XML\TrackerXmlImportFeedbackCollector;

final class Tracker_FormElement_Field_ListTest extends \PHPUnit\Framework\TestCase //phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotCamelCaps
{
    use \Tuleap\GlobalResponseMock, \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration, \Tuleap\GlobalLanguageMock;

    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|Tracker_Artifact_ChangesetValue_List
     */
    private $changeset_value;

    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|Tracker_FormElement_Field_List
     */
    private $list_field;

    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|Tracker_FormElement_Field_List_BindValue
     */
    private $bind_value;

    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|Tracker_FormElement_Field_List_Bind_Static
     */
    private $bind;

    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|Tracker_FormElement_Field_Value_ListDao
     */
    private $value_dao;

    protected function setUp(): void
    {
        $this->list_field             = Mockery::mock(Tracker_FormElement_Field_List::class)
            ->shouldAllowMockingProtectedMethods()->makePartial();
        $this->value_dao              = Mockery::spy(Tracker_FormElement_Field_Value_ListDao::class);
        $this->changeset_value        = Mockery::spy(Tracker_Artifact_ChangesetValue_List::class);
        $this->bind                   = Mockery::spy(Tracker_FormElement_Field_List_Bind_Static::class);
        $this->bind_value             = Mockery::spy(Tracker_FormElement_Field_List_BindValue::class);

        $this->list_field->shouldReceive('getValueDao')->andReturn($this->value_dao);
        $this->list_field->shouldReceive('getBind')->andReturn($this->bind);
    }

    public function testGetChangesetValue(): void
    {
        $this->value_dao->shouldReceive('searchById')->andReturn(
            TestHelper::arrayToDar(
                ['id' => '123', 'field_id' => '1', 'bindvalue_id' => '1000'],
                ['id' => '123', 'field_id' => '1', 'bindvalue_id' => '1001'],
                ['id' => '123', 'field_id' => '1', 'bindvalue_id' => '1002']
            )
        );

        $this->bind->shouldReceive('getBindValues')->andReturn(
            array_fill(0, 3, $this->bind_value)
        );

        $changeset_value = $this->list_field->getChangesetValue(
            Mockery::mock(Tracker_Artifact_Changeset::class),
            123,
            false
        );
        $this->assertInstanceOf(Tracker_Artifact_ChangesetValue_List::class, $changeset_value);
        $this->assertIsArray($changeset_value->getListValues());
        $this->assertCount(3, $changeset_value->getListValues());
        foreach ($changeset_value->getListValues() as $bv) {
            $this->assertInstanceOf(Tracker_FormElement_Field_List_BindValue::class, $bv);
        }
    }

    public function testGetChangesetValueDoesntExist(): void
    {
        $this->value_dao->shouldReceive('searchById')->andReturn(false);

        $this->bind->shouldReceive('getBindValues')->andReturn(
            array_fill(0, 3, $this->bind_value)
        );

        $changeset_value = $this->list_field->getChangesetValue(
            Mockery::mock(Tracker_Artifact_Changeset::class),
            123,
            false
        );
        $this->assertInstanceOf(Tracker_Artifact_ChangesetValue_List::class, $changeset_value);
        $this->assertIsArray($changeset_value->getListValues());
        $this->assertCount(0, $changeset_value->getListValues());
    }

    public function testHasChangesNoChangesReverseOrderMSB(): void
    {
        $old_value = ['107', '108'];
        $new_value = ['108', '107'];
        $this->changeset_value->shouldReceive('getValue')->andReturn($old_value);
        $this->assertFalse(
            $this->list_field->hasChanges(Mockery::mock(Tracker_Artifact::class), $this->changeset_value, $new_value)
        );
    }

    public function testHasChangesNoChangesSameOrderMSB(): void
    {
        $old_value = ['107', '108'];
        $new_value = ['107', '108'];
        $this->changeset_value->shouldReceive('getValue')->andReturn($old_value);
        $this->assertFalse(
            $this->list_field->hasChanges(Mockery::mock(Tracker_Artifact::class), $this->changeset_value, $new_value)
        );
    }

    public function testHasChangesNoChangeEmptyMSB(): void
    {
        $old_value = [];
        $new_value = [];
        $this->changeset_value->shouldReceive('getValue')->andReturn($old_value);
        $this->assertFalse(
            $this->list_field->hasChanges(Mockery::mock(Tracker_Artifact::class), $this->changeset_value, $new_value)
        );
    }

    public function testHasChangesNoChangesSB(): void
    {
        $old_value = ['108'];
        $new_value = '108';
        $this->changeset_value->shouldReceive('getValue')->andReturn($old_value);
        $this->assertFalse(
            $this->list_field->hasChanges(Mockery::mock(Tracker_Artifact::class), $this->changeset_value, $new_value)
        );
    }

    public function testHasChangesChangesMSB(): void
    {
        $old_value = ['107', '108'];
        $new_value = ['107', '110'];
        $this->changeset_value->shouldReceive('getValue')->andReturn($old_value);
        $this->assertTrue(
            $this->list_field->hasChanges(Mockery::mock(Tracker_Artifact::class), $this->changeset_value, $new_value)
        );
    }

    public function testHasChangesChangesNewMSB(): void
    {
        $old_value = [];
        $new_value = ['107', '110'];
        $this->changeset_value->shouldReceive('getValue')->andReturn($old_value);
        $this->assertTrue(
            $this->list_field->hasChanges(Mockery::mock(Tracker_Artifact::class), $this->changeset_value, $new_value)
        );
    }

    public function testHasChangesChangesSB(): void
    {
        $old_value = ['107'];
        $new_value = '110';
        $this->changeset_value->shouldReceive('getValue')->andReturn($old_value);
        $this->assertTrue(
            $this->list_field->hasChanges(Mockery::mock(Tracker_Artifact::class), $this->changeset_value, $new_value)
        );
    }

    public function testHasChangesChangesNewSB(): void
    {
        $old_value = [];
        $new_value = '110';
        $this->changeset_value->shouldReceive('getValue')->andReturn($old_value);
        $this->assertTrue(
            $this->list_field->hasChanges(Mockery::mock(Tracker_Artifact::class), $this->changeset_value, $new_value)
        );
    }

    public function testTransitionIsValidWhenWorkflowIsNotEnabled(): void
    {
        $value_from = Mockery::spy(Tracker_FormElement_Field_List_BindValue::class);
        $value_to   = Mockery::spy(Tracker_FormElement_Field_List_BindValue::class);

        $this->list_field->shouldReceive('fieldHasEnableWorkflow')->andReturnFalse();

        $this->assertTrue($this->list_field->isTransitionValid($value_from, $value_to));
    }

    public function testTransitionIsValidWhenTransitionExistsInWorkflow(): void
    {
        $value_from = Mockery::spy(Tracker_FormElement_Field_List_BindValue::class);
        $value_to   = Mockery::spy(Tracker_FormElement_Field_List_BindValue::class);

        $this->list_field->shouldReceive('fieldHasEnableWorkflow')->andReturnTrue();
        $workflow = Mockery::mock(Workflow::class);
        $this->list_field->shouldReceive('getWorkflow')->andReturn($workflow);
        $workflow->shouldReceive('isTransitionExist')->withArgs([$value_from, $value_to])->andReturnTrue();

        $this->assertTrue($this->list_field->isTransitionValid($value_from, $value_to));
    }

    public function testTransitionIsValidWhenTransitionDoesNotExistsInWorkflow(): void
    {
        $value_from = Mockery::spy(Tracker_FormElement_Field_List_BindValue::class);
        $value_to   = Mockery::spy(Tracker_FormElement_Field_List_BindValue::class);

        $this->list_field->shouldReceive('fieldHasEnableWorkflow')->andReturnTrue();
        $workflow = Mockery::mock(Workflow::class);
        $this->list_field->shouldReceive('getWorkflow')->andReturn($workflow);
        $workflow->shouldReceive('isTransitionExist')->withArgs([$value_from, $value_to])->andReturnFalse();

        $this->assertFalse($this->list_field->isTransitionValid($value_from, $value_to));
    }

    public function testItHasErrorWhenValueIsNotAPossibleValue(): void
    {
        $artifact = Mockery::mock(Tracker_Artifact::class);
        $this->list_field->shouldReceive('isPossibleValue')->andReturnFalse();

        $this->assertFalse($this->list_field->isValid($artifact, "impossible"));
    }

    public function testItHasErrorWhenItIsNotValid(): void
    {
        $artifact = Mockery::mock(Tracker_Artifact::class);
        $this->list_field->shouldReceive('isPossibleValue')->andReturnTrue();
        $this->list_field->shouldReceive('validate')->andReturnFalse();

        $this->assertFalse($this->list_field->isValid($artifact, "invalid"));
    }

    public function testItIsValid(): void
    {
        $artifact = Mockery::mock(Tracker_Artifact::class);
        $this->list_field->shouldReceive('isPossibleValue')->andReturnTrue();
        $this->list_field->shouldReceive('validate')->andReturnTrue();

        $this->assertTrue($this->list_field->isValid($artifact, "valid"));
    }

    public function testExistingValueOfArrayIsAPossibleValue(): void
    {
        $this->bind->shouldReceive('isExistingValue')->andReturnTrue();
        $this->assertTrue($this->list_field->isPossibleValue(["valid"]));
    }

    public function testNonExistingValueOfArrayIsNotAPossibleValue(): void
    {
        $this->bind->shouldReceive('isExistingValue')->andReturnFalse();
        $this->assertTrue($this->list_field->isPossibleValue(["invalid"]));
    }

    public function testExistingStringIsAPossibleValue(): void
    {
        $this->bind->shouldReceive('isExistingValue')->andReturnTrue();
        $this->assertTrue($this->list_field->isPossibleValue("valid"));
    }

    public function testNonExistingStringIsNotAPossibleValue(): void
    {
        $this->bind->shouldReceive('isExistingValue')->andReturnFalse();
        $this->assertTrue($this->list_field->isPossibleValue("invalid"));
    }

    public function testValidateIsOkWhenNoWorkflowIsSet(): void
    {
        $artifact = Mockery::mock(Tracker_Artifact::class);
        $value    = 'value';
        $this->list_field->shouldReceive('fieldHasEnableWorkflow')->andReturnFalse();

        $this->assertTrue($this->list_field->validate($artifact, $value));
    }

    public function testItChecksTransitionIsValidWhenArtifactDoesNotHaveAnExistingChangeset(): void
    {
        $artifact = Mockery::mock(Tracker_Artifact::class);
        $artifact->shouldReceive('getLastChangeset')->andReturn(null);
        $this->bind->shouldReceive('getValue')->andReturn(null);
        $value = 'value';
        $this->list_field->shouldReceive('fieldHasEnableWorkflow')->andReturnTrue();
        $this->list_field->shouldReceive('isTransitionValid')->andReturnTrue();

        $validator = Mockery::mock(TransitionListValidator::class);
        $validator->shouldReceive('checkTransition')->once()->andReturnTrue();
        $this->list_field->shouldReceive('getTransitionListValidator')->andReturn($validator);

        $this->assertTrue($this->list_field->validate($artifact, $value));
    }

    public function testItChecksAllFieldsValueWhenLastChangesetHasAValue(): void
    {
        $artifact = Mockery::mock(Tracker_Artifact::class);
        $artifact->shouldReceive('getLastChangeset')->andReturn(null);
        $this->bind->shouldReceive('getValue')->andReturn([$this->bind_value]);
        $value = 'value';
        $this->list_field->shouldReceive('fieldHasEnableWorkflow')->andReturnTrue();
        $this->list_field->shouldReceive('isTransitionValid')->andReturnTrue();

        $validator = Mockery::mock(TransitionListValidator::class);
        $validator->shouldReceive('checkTransition')->once()->andReturnTrue();
        $this->list_field->shouldReceive('getTransitionListValidator')->andReturn($validator);

        $this->assertTrue($this->list_field->validate($artifact, $value));
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

        $factory            = \Mockery::mock(Tracker_FormElement_Field_List_BindFactory::class);
        $user_finder        = \Mockery::mock(User\XML\Import\IFindUserFromXMLReference::class);
        $feedback_collector = \Mockery::mock(TrackerXmlImportFeedbackCollector::class);

        $this->list_field->shouldReceive('getBindFactory')->andReturn($factory);

        $factory->shouldReceive('getInstanceFromXML')->andReturn($this->bind);

        $this->list_field->continueGetInstanceFromXML($xml, $mapping, $user_finder, $feedback_collector);
        $this->assertEquals($this->bind, $this->list_field->getBind());
    }

    public function testAfterSaveObject(): void
    {
        $tracker = Mockery::mock(Tracker::class);
        $factory = Mockery::mock(Tracker_FormElement_Field_List_BindFactory::class);
        $dao     = Mockery::mock(Tracker_FormElement_Field_ListDao::class);

        $this->list_field->shouldReceive('getBindFactory')->andReturn($factory);
        $this->list_field->shouldReceive('getListDao')->andReturn($dao);
        $this->list_field->shouldReceive('getId')->andReturn(66);

        $factory->shouldReceive('getType')->with($this->bind)->andReturn('users')->once();

        $this->bind->shouldReceive('saveObject')->once();

        $dao->shouldReceive('save')->withArgs([66, 'users']);

        $this->list_field->afterSaveObject($tracker, false, false);
    }

    public function testItIsValidWhenIsRequiredAndHaveAValue(): void
    {
        $artifact = Mockery::mock(Tracker_Artifact::class);
        $value    = 102;
        $this->list_field->shouldReceive('isRequired')->andReturnTrue();
        $this->list_field->shouldReceive('isNone')->andReturnFalse();
        $this->assertTrue($this->list_field->isValidRegardingRequiredProperty($artifact, $value));
    }

    public function testItIsInvalidWhenIsRequiredAndEmpty(): void
    {
        $artifact = Mockery::mock(Tracker_Artifact::class);
        $value    = 100;
        $this->list_field->shouldReceive('isRequired')->andReturnTrue();
        $this->list_field->shouldReceive('isNone')->andReturnTrue();
        $this->assertFalse($this->list_field->isValidRegardingRequiredProperty($artifact, $value));
    }

    public function testItIsValidWhenIsNotRequiredAndEmpty(): void
    {
        $artifact = Mockery::mock(Tracker_Artifact::class);
        $value    = 102;
        $this->list_field->shouldReceive('isRequired')->andReturnFalse();
        $this->list_field->shouldReceive('isNone')->andReturnFalse();
        $this->assertTrue($this->list_field->isValidRegardingRequiredProperty($artifact, $value));
    }

    public function testItDoesNothingIfTheRequestDoesNotContainTheParameter(): void
    {
        $layout = Mockery::mock(Tracker_IDisplayTrackerLayout::class);
        $user   = Mockery::mock(PFUser::class);

        $request = Mockery::mock(Codendi_Request::class);
        $request->shouldReceive('get')->andReturn('stuff');

        $this->bind->shouldReceive('fetchFormattedForJson')->never();
        $this->list_field->process($layout, $request, $user);
    }

    public function testItSendsWhateverBindReturns(): void
    {
        $layout = Mockery::mock(Tracker_IDisplayTrackerLayout::class);
        $user   = Mockery::mock(PFUser::class);

        $request = Mockery::mock(Codendi_Request::class);
        $request->shouldReceive('get')->andReturn('get-values');

        $this->bind->shouldReceive('fetchFormattedForJson')->once();
        $this->list_field->process($layout, $request, $user);
    }

    public function testItHasValuesInAdditionToCommonFormat(): void
    {
        $this->bind->shouldReceive('fetchFormattedForJson')->andReturn([])->once();

        $json = $this->list_field->fetchFormattedForJson();
        $this->assertEquals([], $json['values']);
    }

    public function testItThrowsAnExceptionIfValueIsNotUsable(): void
    {
        $this->expectException(Tracker_Report_InvalidRESTCriterionException::class);

        $criteria             = Mockery::mock(Tracker_Report_Criteria::class);
        $criteria->report     = Mockery::mock(Tracker_Report::class);
        $criteria->report->id = 1;
        $rest_criteria_value  = [
            Tracker_Report_REST::VALUE_PROPERTY_NAME    => [[1234]],
            Tracker_Report_REST::OPERATOR_PROPERTY_NAME => Tracker_Report_REST::OPERATOR_CONTAINS
        ];

        $this->list_field->setCriteriaValueFromREST($criteria, $rest_criteria_value);
    }

    public function testItThrowsAnExceptionIfValueIsNotANumber(): void
    {
        $this->expectException(Tracker_Report_InvalidRESTCriterionException::class);

        $criteria             = Mockery::mock(Tracker_Report_Criteria::class);
        $criteria->report     = Mockery::mock(Tracker_Report::class);
        $criteria->report->id = 1;
        $rest_criteria_value  = [
            Tracker_Report_REST::VALUE_PROPERTY_NAME    => 'I am a string',
            Tracker_Report_REST::OPERATOR_PROPERTY_NAME => Tracker_Report_REST::OPERATOR_CONTAINS
        ];

        $this->list_field->setCriteriaValueFromREST($criteria, $rest_criteria_value);
    }

    public function testItIgnoresInvalidFieldValues(): void
    {
        $criteria = Mockery::mock(Tracker_Report_Criteria::class);
        $report   = Mockery::mock(Tracker_Report::class);
        $criteria->shouldReceive('getReport')->andReturn($report);
        $report->shouldReceive('getId')->andReturn(1);

        $rest_criteria_value = [
            Tracker_Report_REST::VALUE_PROPERTY_NAME    => '106',
            Tracker_Report_REST::OPERATOR_PROPERTY_NAME => Tracker_Report_REST::OPERATOR_CONTAINS
        ];

        $set = $this->list_field->setCriteriaValueFromREST($criteria, $rest_criteria_value);
        $this->assertFalse($set);

        $res = $this->list_field->getCriteriaValue($criteria);
        $this->assertCount(0, $res);
    }

    public function testItAddsACriterion(): void
    {
        $this->bind->shouldReceive('getAllValues')->andReturn(array(101 => 101, 102 => 102, 103 => 103));
        $criteria   = Mockery::mock(Tracker_Report_Criteria::class);
        $report     = Mockery::mock(Tracker_Report::class);
        $criteria->shouldReceive('getReport')->andReturn($report);
        $report->shouldReceive('getId')->andReturn(1);

        $rest_criteria_value  = array(
            Tracker_Report_REST::VALUE_PROPERTY_NAME    => '101',
            Tracker_Report_REST::OPERATOR_PROPERTY_NAME => Tracker_Report_REST::OPERATOR_CONTAINS
        );

        $set = $this->list_field->setCriteriaValueFromREST($criteria, $rest_criteria_value);
        $this->assertTrue($set);

        $res = $this->list_field->getCriteriaValue($criteria);

        $this->assertCount(1, $res);
        $this->assertContains(101, $res);
    }

    public function testItAddsCriteria(): void
    {
        $this->bind->shouldReceive('getAllValues')->andReturn(array(101 => 101, 102 => 102, 103 => 103));
        $criteria   = Mockery::mock(Tracker_Report_Criteria::class);
        $report     = Mockery::mock(Tracker_Report::class);
        $criteria->shouldReceive('getReport')->andReturn($report);
        $report->shouldReceive('getId')->andReturn(1);

        $rest_criteria_value  = array(
            Tracker_Report_REST::VALUE_PROPERTY_NAME    => array('101', 103),
            Tracker_Report_REST::OPERATOR_PROPERTY_NAME => Tracker_Report_REST::OPERATOR_CONTAINS
        );

        $set = $this->list_field->setCriteriaValueFromREST($criteria, $rest_criteria_value);
        $this->assertTrue($set);

        $res = $this->list_field->getCriteriaValue($criteria);

        $this->assertCount(2, $res);
        $this->assertContains(101, $res);
        $this->assertContains(103, $res);
    }

    public function testItThrowsAnExceptionWhenReturningValueIndexedByFieldName(): void
    {
        $this->expectException(Tracker_FormElement_RESTValueByField_NotImplementedException::class);

        $value = ['some_value'];

        $this->list_field->getFieldDataFromRESTValueByField($value);
    }

    public function testItDoesNotAcceptIncorrectValues(): void
    {
        $artifact = Mockery::mock(Tracker_Artifact::class);
        $this->assertFalse($this->list_field->isValid($artifact, 9999));
        $this->assertFalse($this->list_field->isValid($artifact, array(9998, 9999)));
        $this->assertFalse($this->list_field->isValid($artifact, array(101, 9999)));
    }
}
