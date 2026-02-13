<?php
/**
 * Copyright (c) Enalean, 2026 - present. All Rights Reserved.
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

namespace Tuleap\Tracker\REST\v1\MoveTrackerFormElement;

use PHPUnit\Framework\MockObject\MockObject;
use Tuleap\NeverThrow\Err;
use Tuleap\NeverThrow\Fault;
use Tuleap\NeverThrow\Ok;
use Tuleap\NeverThrow\Result;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\FormElement\Container\Column\ColumnContainer;
use Tuleap\Tracker\FormElement\Container\Fieldset\FieldsetContainer;
use Tuleap\Tracker\FormElement\Field\FieldDao;
use Tuleap\Tracker\FormElement\Field\Integer\IntegerField;
use Tuleap\Tracker\FormElement\Field\String\StringField;
use Tuleap\Tracker\FormElement\TrackerFormElement;
use Tuleap\Tracker\REST\v1\TrackerFieldRepresentations\MoveTrackerFieldsPATCHRepresentation;
use Tuleap\Tracker\Test\Builders\Fields\ColumnContainerBuilder;
use Tuleap\Tracker\Test\Builders\Fields\FieldsetContainerBuilder;
use Tuleap\Tracker\Test\Builders\Fields\IntegerFieldBuilder;
use Tuleap\Tracker\Test\Builders\Fields\StringFieldBuilder;
use Tuleap\Tracker\Test\Stub\FormElement\Field\RetrieveAnyTypeOfUsedFormElementByIdStub;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class PATCHMoveTrackerFieldHandlerTest extends TestCase
{
    private FieldDao&MockObject $save_field;

    private ColumnContainer $column_field;
    private FieldsetContainer $fieldset_field;
    private FieldsetContainer $another_fieldset_field;
    private StringField $string_field;
    private IntegerField $integer_field;
    private FieldsetContainer $unused_fieldset;
    private StringField $unused_string_field;

    #[\Override]
    protected function setUp(): void
    {
        $this->column_field           = ColumnContainerBuilder::aColumn(1000)->build();
        $this->fieldset_field         = FieldsetContainerBuilder::aFieldset(1001)->build();
        $this->another_fieldset_field = FieldsetContainerBuilder::aFieldset(1002)->build();
        $this->string_field           = StringFieldBuilder::aStringField(1003)->build();
        $this->integer_field          = IntegerFieldBuilder::anIntField(1004)->build();
        $this->unused_fieldset        = FieldsetContainerBuilder::aFieldset(2000)->unused()->build();
        $this->unused_string_field    = StringFieldBuilder::aStringField(2001)->unused()->build();

        $this->save_field = $this->createMock(FieldDao::class);
    }

    /**
     * @return Ok<null>|Err<Fault>
     */
    private function handle(TrackerFormElement $field, MoveTrackerFieldsPATCHRepresentation $move_payload): Ok|Err
    {
        return new PATCHMoveTrackerFieldHandler(
            RetrieveAnyTypeOfUsedFormElementByIdStub::withFormElements(
                $this->column_field,
                $this->fieldset_field,
                $this->another_fieldset_field,
                $this->string_field,
                $this->integer_field,
                $this->unused_fieldset,
                $this->unused_string_field,
            ),
            $this->save_field,
        )->handle($field, $move_payload);
    }

    private function createMovePayload(?TrackerFormElement $parent_field, ?TrackerFormElement $next_sibling): MoveTrackerFieldsPATCHRepresentation
    {
        return new MoveTrackerFieldsPATCHRepresentation(
            $parent_field?->id,
            $next_sibling?->id,
        );
    }

    public function testFieldCanBeMovedAtTheEndOfAColumn(): void
    {
        $this->save_field->expects($this->once())->method('save')->willReturn(true);

        $result = $this->handle($this->string_field, $this->createMovePayload($this->column_field, null));

        self::assertTrue(Result::isOk($result));
        self::assertSame('end', $this->string_field->rank);
        self::assertSame($this->column_field->id, $this->string_field->parent_id);
    }

    public function testFieldCanBeMovedBeforeASiblingInAColumnContainer(): void
    {
        $this->integer_field->parent_id = $this->column_field->id;
        $this->integer_field->rank      = 2;

        $this->save_field->expects($this->once())->method('save')->willReturn(true);

        $result = $this->handle($this->string_field, $this->createMovePayload($this->column_field, $this->integer_field));

        self::assertTrue(Result::isOk($result));
        self::assertSame($this->integer_field->rank, $this->string_field->rank);
        self::assertSame($this->column_field->id, $this->string_field->parent_id);
    }

    public function testFieldsetsCanBeMovedAtTheEndOfTrackerRoot(): void
    {
        $this->save_field->expects($this->once())->method('save')->willReturn(true);

        $result = $this->handle($this->fieldset_field, $this->createMovePayload(null, null));

        self::assertTrue(Result::isOk($result));
        self::assertSame('end', $this->fieldset_field->rank);
        self::assertSame(0, $this->fieldset_field->parent_id);
    }

    public function testFieldsetsCanBeMovedBeforeASiblingIntoTrackerRoot(): void
    {
        $this->another_fieldset_field->parent_id = 0;
        $this->another_fieldset_field->rank      = 4;

        $this->save_field->expects($this->once())->method('save')->willReturn(true);

        $result = $this->handle($this->fieldset_field, $this->createMovePayload(null, $this->another_fieldset_field));

        self::assertTrue(Result::isOk($result));
        self::assertSame($this->another_fieldset_field->rank, $this->fieldset_field->rank);
        self::assertSame(0, $this->fieldset_field->parent_id);
    }

    public function testFieldCannotBeMovedBeforeASiblingInAFieldsetContainer(): void
    {
        $this->integer_field->parent_id = $this->fieldset_field->id;
        $this->integer_field->rank      = 2;

        $this->save_field->expects($this->never())->method('save')->willReturn(true);

        $result = $this->handle($this->string_field, $this->createMovePayload($this->fieldset_field, $this->integer_field));

        self::assertTrue(Result::isErr($result));
        self::assertEquals(FieldCannotBeMovedFault::buildFieldsCanOnlyBeMovedIntoColumns(), $result->error);
    }

    public function testFieldCannotBeMovedAtTheEndOfAFieldset(): void
    {
        $this->save_field->expects($this->never())->method('save')->willReturn(true);

        $result = $this->handle($this->string_field, $this->createMovePayload($this->fieldset_field, null));

        self::assertTrue(Result::isErr($result));
        self::assertEquals(FieldCannotBeMovedFault::buildFieldsCanOnlyBeMovedIntoColumns(), $result->error);
    }

    public function testFieldCannotBeMovedIntoTrackerRoot(): void
    {
        $this->save_field->expects($this->never())->method('save');

        $result = $this->handle($this->string_field, new MoveTrackerFieldsPATCHRepresentation());

        self::assertTrue(Result::isErr($result));
        self::assertEquals(FieldCannotBeMovedFault::buildFieldsCanOnlyBeMovedIntoColumns(), $result->error);
    }

    public function testFieldCannotBeMovedIntoAnotherRegularField(): void
    {
        $this->save_field->expects($this->never())->method('save');

        $result = $this->handle($this->string_field, $this->createMovePayload($this->integer_field, null));

        self::assertTrue(Result::isErr($result));
        self::assertEquals(FieldCannotBeMovedFault::buildFieldsCanOnlyBeMovedIntoColumns(), $result->error);
    }

    public function testFieldsetsCannotBeMovedIntoAColumn(): void
    {
        $this->save_field->expects($this->never())->method('save');

        $result = $this->handle($this->fieldset_field, $this->createMovePayload($this->column_field, null));

        self::assertTrue(Result::isErr($result));
        self::assertEquals(FieldCannotBeMovedFault::buildFieldsetNotIntoTrackerRoot(), $result->error);
    }

    public function testFieldsetsCannotBeMovedIntoAnotherFieldset(): void
    {
        $this->save_field->expects($this->never())->method('save');

        $result = $this->handle($this->fieldset_field, $this->createMovePayload($this->another_fieldset_field, null));

        self::assertTrue(Result::isErr($result));
        self::assertEquals(FieldCannotBeMovedFault::buildFieldsetNotIntoTrackerRoot(), $result->error);
    }

    public function testFieldsetsCannotBeMovedIntoThemselves(): void
    {
        $this->save_field->expects($this->never())->method('save');

        $result = $this->handle($this->fieldset_field, $this->createMovePayload($this->fieldset_field, null));

        self::assertTrue(Result::isErr($result));
        self::assertEquals(FieldCannotBeMovedFault::buildFieldsetNotIntoTrackerRoot(), $result->error);
    }

    public function testColumnsCannotBeMoved(): void
    {
        $this->save_field->expects($this->never())->method('save');

        $result = $this->handle($this->column_field, $this->createMovePayload($this->fieldset_field, null));

        self::assertTrue(Result::isErr($result));
        self::assertEquals(FieldCannotBeMovedFault::buildColumnsCannotBeMoved(), $result->error);
    }

    public function testItReturnsAnErrorWhenTheParentIsNotFound(): void
    {
        $this->save_field->expects($this->never())->method('save');

        $result = $this->handle($this->string_field, new MoveTrackerFieldsPATCHRepresentation(88888, null));

        self::assertTrue(Result::isErr($result));
        self::assertEquals(FieldCannotBeMovedFault::buildFieldUnusedOrNotFound(88888), $result->error);
    }

    public function testItReturnsAnErrorWhenTheParentIsUnusedInItsTracker(): void
    {
        $this->save_field->expects($this->never())->method('save');

        $result = $this->handle($this->string_field, $this->createMovePayload($this->unused_fieldset, null));

        self::assertTrue(Result::isErr($result));
        self::assertEquals(FieldCannotBeMovedFault::buildFieldUnusedOrNotFound($this->unused_fieldset->id), $result->error);
    }

    public function testItReturnsAnErrorWhenTheSiblingIsNotFound(): void
    {
        $this->save_field->expects($this->never())->method('save');

        $result = $this->handle($this->string_field, new MoveTrackerFieldsPATCHRepresentation($this->column_field->id, 9999999));

        self::assertTrue(Result::isErr($result));
        self::assertEquals(FieldCannotBeMovedFault::buildFieldUnusedOrNotFound(9999999), $result->error);
    }

    public function testItReturnsAnErrorWhenTheSiblingIsUnusedInItsTracker(): void
    {
        $this->save_field->expects($this->never())->method('save');

        $result = $this->handle($this->string_field, $this->createMovePayload($this->column_field, $this->unused_string_field));

        self::assertTrue(Result::isErr($result));
        self::assertEquals(FieldCannotBeMovedFault::buildFieldUnusedOrNotFound($this->unused_string_field->id), $result->error);
    }

    public function testItReturnsAnErrorWhenTheSiblingIsNotChildOfParent(): void
    {
        $this->save_field->expects($this->never())->method('save');

        $result = $this->handle($this->string_field, $this->createMovePayload($this->column_field, $this->integer_field));

        self::assertTrue(Result::isErr($result));
        self::assertEquals(FieldCannotBeMovedFault::buildSiblingIsNotChildOfParent($this->integer_field), $result->error);
    }

    public function testItReturnsAnErrorWhenTheSiblingIsNotChildOfTrackerRoot(): void
    {
        $this->another_fieldset_field->parent_id = 44444;
        $this->save_field->expects($this->never())->method('save');

        $result = $this->handle($this->fieldset_field, $this->createMovePayload(null, $this->another_fieldset_field));

        self::assertTrue(Result::isErr($result));
        self::assertEquals(FieldCannotBeMovedFault::buildSiblingIsNotChildOfParent($this->another_fieldset_field), $result->error);
    }

    public function testItReturnsAnErrorWhenTheFieldOrderFailedToBeSaved(): void
    {
        $this->save_field->expects($this->once())->method('save')->willReturn(false);

        $result = $this->handle($this->fieldset_field, $this->createMovePayload(null, null));

        self::assertTrue(Result::isErr($result));
        self::assertEquals(FieldNotSavedFault::build($this->fieldset_field), $result->error);
    }
}
