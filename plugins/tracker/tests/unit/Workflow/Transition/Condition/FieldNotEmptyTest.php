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

use PHPUnit\Framework\MockObject\MockObject;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\FormElement\Field\List\SelectboxField;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;
use Tuleap\Tracker\Test\Builders\Fields\List\ListStaticValueBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class FieldNotEmptyTest extends \Tuleap\Test\PHPUnit\TestCase // phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
{
    use \Tuleap\GlobalResponseMock;

    private Workflow_Transition_Condition_FieldNotEmpty $condition;
    private string $empty_data     = '';
    private string $not_empty_data = 'coin';
    private SelectboxField $field;
    private SelectboxField $field_bis;
    private Workflow_Transition_Condition_FieldNotEmpty_Dao&MockObject $dao;
    private Transition $transition;
    private Artifact $artifact;
    private \Tracker_Artifact_Changeset&MockObject $changeset;
    private \Tracker_Artifact_ChangesetValue $previous_value;
    private PFUser $current_user;

    #[\Override]
    protected function setUp(): void
    {
        $factory = $this->createMock(\Tracker_FormElementFactory::class);

        $this->field     = $this->createFieldWithId($factory, 123);
        $this->field_bis = $this->createFieldWithId($factory, 234);

        Tracker_FormElementFactory::setInstance($factory);
        $this->dao        = $this->createMock(Workflow_Transition_Condition_FieldNotEmpty_Dao::class);
        $this->transition = new Transition(
            42,
            101,
            null,
            ListStaticValueBuilder::aStaticValue('Done')->build(),
        );
        $this->condition  = new Workflow_Transition_Condition_FieldNotEmpty($this->transition, $this->dao);

        $this->changeset = $this->createMock(\Tracker_Artifact_Changeset::class);

        $this->artifact = ArtifactTestBuilder::anArtifact(101)
            ->withChangesets($this->changeset)
            ->build();

        $this->previous_value = $this->createMock(\Tracker_Artifact_ChangesetValue::class);
        $this->current_user   = UserTestBuilder::buildWithDefaults();
    }

    private function createFieldWithId(Tracker_FormElementFactory&MockObject $factory, int $id): SelectboxField
    {
        $field = $this->createMock(\Tuleap\Tracker\FormElement\Field\List\SelectboxField::class);
        $field->method('getId')->willReturn($id);
        $field->method('getName')->willReturn('field');
        $field->method('getLabel')->willReturn('Field');
        $field->method('setHasErrors');
        $field->method('isEmpty')->willReturnCallback(fn (mixed $value) => match ($value) {
            $this->not_empty_data => false,
            $this->empty_data, null => true,
        });
        $factory->method('getUsedFormElementById')->with($id)->willReturn($field);

        return $field;
    }

    #[\Override]
    protected function tearDown(): void
    {
        Tracker_FormElementFactory::clearInstance();
    }

    public function testItSavesUsingTheRealFieldObject(): void
    {
        $this->condition->addField($this->field);
        $this->dao->expects($this->once())->method('create')->with(42, [123]);
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
        $this->changeset->method('getValue')->with($this->field)->willReturn($this->previous_value);
        $this->previous_value->method('getValue')->willReturn($this->not_empty_data);
        $fields_data = [];
        $is_valid    = $this->condition->validate($fields_data, $this->artifact, '', $this->current_user);
        $this->assertTrue($is_valid);
    }

    public function testItReturnsFalseWhenFieldNotPresentInRequestAndNotSetInTheLastChangeset(): void
    {
        $this->condition->addField($this->field);
        $this->changeset->method('getValue')->with($this->field)->willReturn($this->previous_value);
        $this->previous_value->method('getValue')->willReturn($this->empty_data);
        $fields_data = [];
        $is_valid    = $this->condition->validate($fields_data, $this->artifact, '', $this->current_user);
        $this->assertFalse($is_valid);
    }

    public function testItReturnsFalseWhenFieldNotPresentInRequestAndNotInTheLastChangeset(): void
    {
        $this->condition->addField($this->field);
        $this->changeset->method('getValue')->with($this->field)->willReturn(null);
        $fields_data = [];
        $is_valid    = $this->condition->validate($fields_data, $this->artifact, '', $this->current_user);
        $this->assertFalse($is_valid);
    }

    public function testItReturnsFalseWhenFieldNotPresentInRequestAndThereIsNoLastChangeset(): void
    {
        $this->condition->addField($this->field);
        $artifact_without_changeset = $this->createMock(Artifact::class);
        $artifact_without_changeset->method('getLastChangeset')->willReturn(null);
        $fields_data = [];
        $is_valid    = $this->condition->validate($fields_data, $artifact_without_changeset, '', $this->current_user);
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
