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

declare(strict_types=1);

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class FieldNotEmptyTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
    use \Tuleap\GlobalResponseMock;

    private $condition;
    private $empty_data     = '';
    private $not_empty_data = 'coin';
    private $field;
    private $field_bis;
    private $dao;
    private $transition;
    private $artifact;
    private $changeset;
    private $previous_value;
    private $current_user;

    protected function setUp(): void
    {
        $factory = \Mockery::spy(\Tracker_FormElementFactory::class);

        $this->field     = $this->createFieldWithId($factory, 123);
        $this->field_bis = $this->createFieldWithId($factory, 234);

        Tracker_FormElementFactory::setInstance($factory);
        $this->dao        = \Mockery::spy(\Workflow_Transition_Condition_FieldNotEmpty_Dao::class);
        $this->transition = \Mockery::spy(\Transition::class)->shouldReceive('getId')->andReturns(42)->getMock();
        $this->condition  = new Workflow_Transition_Condition_FieldNotEmpty($this->transition, $this->dao);
        $this->artifact   = \Mockery::spy(\Tuleap\Tracker\Artifact\Artifact::class);

        $this->changeset = \Mockery::spy(\Tracker_Artifact_Changeset::class);
        $this->artifact->shouldReceive('getLastChangeset')->andReturns($this->changeset);

        $this->previous_value = \Mockery::spy(\Tracker_Artifact_ChangesetValue::class);
        $this->current_user   = \Mockery::spy(\PFUser::class);
    }

    private function createFieldWithId(Tracker_FormElementFactory $factory, $id): Tracker_FormElement_Field_Selectbox
    {
        $field = \Mockery::spy(\Tracker_FormElement_Field_Selectbox::class);
        $field->shouldReceive('getId')->andReturns($id);
        $field->shouldReceive('isEmpty')->with($this->not_empty_data, \Mockery::any())->andReturns(false);
        $field->shouldReceive('isEmpty')->with($this->empty_data, \Mockery::any())->andReturns(true);
        $field->shouldReceive('isEmpty')->with(null, \Mockery::any())->andReturns(true);
        $factory->shouldReceive('getUsedFormElementById')->with($id)->andReturns($field);

        return $field;
    }

    protected function tearDown(): void
    {
        Tracker_FormElementFactory::clearInstance();
    }

    public function testItSavesUsingTheRealFieldObject(): void
    {
        $this->condition->addField($this->field);
        $this->dao->shouldReceive('create')->with(42, [123])->once();
        $this->condition->saveObject();
    }

    public function testItReturnsTrueWhenNoField(): void
    {
        $fields_data = [];
        $is_valid    = $this->condition->validate($fields_data, $this->artifact, '', $this->current_user);
        $this->assertTrue($is_valid);
    }

    public function testItReturnsTrueWhenNoFieldId(): void
    {
        $fields_data = [1 => $this->not_empty_data];
        $is_valid    = $this->condition->validate($fields_data, $this->artifact, '', $this->current_user);
        $this->assertTrue($is_valid);
    }

    public function testItReturnsTrueWhenFieldNotEmpty(): void
    {
        $this->condition->addField($this->field);
        $fields_data = [123 => $this->not_empty_data];
        $is_valid    = $this->condition->validate($fields_data, $this->artifact, '', $this->current_user);
        $this->assertTrue($is_valid);
    }

    public function testItReturnsTrueWhenFieldNotPresentInRequestButAlreadySetInTheLastChangeset(): void
    {
        $this->condition->addField($this->field);
        $this->changeset->shouldReceive('getValue')->with($this->field)->andReturns($this->previous_value);
        $this->previous_value->shouldReceive('getValue')->andReturns($this->not_empty_data);
        $fields_data = [];
        $is_valid    = $this->condition->validate($fields_data, $this->artifact, '', $this->current_user);
        $this->assertTrue($is_valid);
    }

    public function testItReturnsFalseWhenFieldNotPresentInRequestAndNotSetInTheLastChangeset(): void
    {
        $this->condition->addField($this->field);
        $this->changeset->shouldReceive('getValue')->with($this->field)->andReturns($this->previous_value);
        $this->previous_value->shouldReceive('getValue')->andReturns($this->empty_data);
        $fields_data = [];
        $is_valid    = $this->condition->validate($fields_data, $this->artifact, '', $this->current_user);
        $this->assertFalse($is_valid);
    }

    public function testItReturnsFalseWhenFieldNotPresentInRequestAndNotInTheLastChangeset(): void
    {
        $this->condition->addField($this->field);
        $this->changeset->shouldReceive('getValue')->with($this->field)->andReturns(null);
        $fields_data = [];
        $is_valid    = $this->condition->validate($fields_data, $this->artifact, '', $this->current_user);
        $this->assertFalse($is_valid);
    }

    public function testItReturnsFalseWhenFieldNotPresentInRequestAndThereIsNoLastChangeset(): void
    {
        $this->condition->addField($this->field);
        $artifact_without_changeset = \Mockery::spy(\Tuleap\Tracker\Artifact\Artifact::class);
        $fields_data                = [];
        $is_valid                   = $this->condition->validate($fields_data, $artifact_without_changeset, '', $this->current_user);
        $this->assertFalse($is_valid);
    }

    public function testItReturnsFalseWhenTheFieldIsEmpty(): void
    {
        $this->condition->addField($this->field);
        $fields_data = [123 => $this->empty_data];
        $is_valid    = $this->condition->validate($fields_data, $this->artifact, '', $this->current_user);
        $this->assertFalse($is_valid);
    }

    public function testItReturnsTrueWhenAllFieldsAreFilled(): void
    {
        $this->condition->addField($this->field);
        $this->condition->addField($this->field_bis);
        $fields_data = [
            123 => $this->not_empty_data,
            234 => $this->not_empty_data,
        ];
        $is_valid    = $this->condition->validate($fields_data, $this->artifact, '', $this->current_user);
        $this->assertTrue($is_valid);
    }

    public function testItReturnsFalseWhenOneFieldIsNotFilled(): void
    {
        $this->condition->addField($this->field);
        $this->condition->addField($this->field_bis);
        $fields_data = [
            123 => $this->not_empty_data,
            234 => $this->empty_data,
        ];
        $is_valid    = $this->condition->validate($fields_data, $this->artifact, '', $this->current_user);
        $this->assertFalse($is_valid);
    }
}
