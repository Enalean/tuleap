<?php
/**
 * Copyright (c) Enalean, 2026-present. All Rights Reserved.
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

use Override;
use PFUser;
use PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles;
use PHPUnit\Framework\MockObject\Stub;
use Tuleap\NeverThrow\Result;
use Tuleap\Stubs\AddHistoryStub;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\FormElement\Field\CannotRemoveFormElementFault;
use Tuleap\Tracker\FormElement\Field\FieldUsedInSemanticsFault;
use Tuleap\Tracker\FormElement\Field\Text\TextField;
use Tuleap\Tracker\Semantic\CollectionOfSemanticsUsingAParticularTrackerField;
use Tuleap\Tracker\Semantic\Title\TrackerSemanticTitle;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use Tuleap\Tracker\Test\Stub\FormElement\UnuseFormElementStub;
use Tuleap\Tracker\Tracker;

#[DisableReturnValueGenerationForTestDoubles]
final class TrackerFormElementRemoverTest extends TestCase
{
    private TrackerFormElement&Stub $field;

    private UnuseFormElementStub $unuse_dao;

    private AddHistoryStub $project_history;

    private PFUser $current_user;
    private Tracker $tracker;

    #[Override]
    protected function setUp(): void
    {
        $project       = ProjectTestBuilder::aProject()->build();
        $this->tracker = TrackerTestBuilder::aTracker()->withProject($project)->build();
        $this->field   = $this->createStub(TrackerFormElement::class);
        $this->field->method('getId')->willReturn(72);
        $this->field->method('getTracker')->willReturn($this->tracker);
        $this->field->method('getLabel')->willReturn('Some label');

        $this->unuse_dao       = UnuseFormElementStub::build();
        $this->project_history = AddHistoryStub::build();

        $this->current_user = UserTestBuilder::buildWithDefaults();
    }

    public function testItReturnsAnErrorIfTheCurrentFormElementCannotBeRemoved(): void
    {
        $this->field->method('isUsed')->willReturn(true);
        $this->field->method('canBeRemovedFromUsage')->willReturn(false);
        $this->field->method('getCannotRemoveMessage')->willReturn('Cannot be removed');

        $field_remover = new TrackerFormElementRemover($this->unuse_dao, $this->project_history);
        $value         = $field_remover->remove($this->field, $this->current_user);

        self::assertTrue(Result::isErr($value));
        self::assertInstanceOf(CannotRemoveFormElementFault::class, $value->error);
        self::assertSame(0, $this->unuse_dao->call_count);
        self::assertSame(0, $this->project_history->call_count);
    }

    public function testItReturnsAnErrorIfTheCurrentFieldIsUsedInSemantic(): void
    {
        $field = $this->createStub(TextField::class);
        $field->method('isUsed')->willReturn(true);
        $field->method('canBeRemovedFromUsage')->willReturn(true);
        $field->method('getTracker')->willReturn($this->tracker);

        $semantics = [new TrackerSemanticTitle($this->tracker, $field)];
        $field->method('getUsagesInSemantics')->willReturn(new CollectionOfSemanticsUsingAParticularTrackerField($field, $semantics));

        $field_remover = new TrackerFormElementRemover($this->unuse_dao, $this->project_history);
        $value         = $field_remover->remove($field, $this->current_user);

        self::assertTrue(Result::isErr($value));
        self::assertInstanceOf(FieldUsedInSemanticsFault::class, $value->error);
        self::assertSame(0, $this->unuse_dao->call_count);
        self::assertSame(0, $this->project_history->call_count);
    }

    public function testItReturnsOkIfTheCurrenFormElementIsAlreadyUnused(): void
    {
        $this->field->method('isUsed')->willReturn(false);

        $field_remover = new TrackerFormElementRemover($this->unuse_dao, $this->project_history);
        $value         = $field_remover->remove($this->field, $this->current_user);

        self::assertTrue(Result::isOk($value));
        self::assertSame(0, $this->unuse_dao->call_count);
        self::assertSame(0, $this->project_history->call_count);
    }

    public function testItReturnsOkIfTheCurrentFormElementCanBeRemoved(): void
    {
        $this->field->method('isUsed')->willReturn(true);
        $this->field->method('canBeRemovedFromUsage')->willReturn(true);

        $field_remover = new TrackerFormElementRemover($this->unuse_dao, $this->project_history);
        $value         = $field_remover->remove($this->field, $this->current_user);

        self::assertTrue(Result::isOk($value));
        self::assertSame(1, $this->unuse_dao->call_count);
        self::assertSame(1, $this->project_history->call_count);
        self::assertFalse($this->field->use_it);
    }
}
