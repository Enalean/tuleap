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

namespace Tuleap\Tracker\REST\FormElement;

use Override;
use PFUser;
use PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles;
use Tuleap\Stubs\AddHistoryStub;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\FormElement\Field\String\StringField;
use Tuleap\Tracker\FormElement\TrackerFieldAdder;
use Tuleap\Tracker\FormElement\TrackerFormElement;
use Tuleap\Tracker\FormElement\TrackerFormElementRemover;
use Tuleap\Tracker\Test\Builders\Fields\StringFieldBuilder;
use Tuleap\Tracker\Test\Builders\TrackerFieldPatchRepresentationTestBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use Tuleap\Tracker\Test\Stub\FormElement\UnuseFormElementStub;
use Tuleap\Tracker\Test\Stub\FormElement\UseFormElementStub;
use Tuleap\Tracker\Tracker;

#[DisableReturnValueGenerationForTestDoubles]
final class RestFieldUseHandlerTest extends TestCase
{
    private StringField $field;

    private UnuseFormElementStub $unuse_dao;
    private UseFormElementStub $form_element_factory_add;
    private PFUser $current_user;
    private AddHistoryStub $history_dao;
    private Tracker $tracker;

    #[Override]
    protected function setUp(): void
    {
        $project       = ProjectTestBuilder::aProject()->build();
        $this->tracker = TrackerTestBuilder::aTracker()->withProject($project)->build();
        $this->field   =  StringFieldBuilder::aStringField(1)->inTracker($this->tracker)->build();

        $this->current_user = UserTestBuilder::buildWithDefaults();

        $this->unuse_dao                = UnuseFormElementStub::build();
        $this->form_element_factory_add = UseFormElementStub::build();

        $this->history_dao = AddHistoryStub::build();
    }

    public function testItDoesNothingIfWeDoNotWantToUpdateTheUsageOfTheField(): void
    {
        $patch = TrackerFieldPatchRepresentationTestBuilder::aPatch()->build();

        $field_remove_handler = new RestFieldUseHandler(new TrackerFormElementRemover($this->unuse_dao, $this->history_dao), new TrackerFieldAdder($this->form_element_factory_add));
        $field_remove_handler->handle($this->field, $patch, $this->current_user);

        self::assertSame(0, $this->unuse_dao->call_count);
        self::assertSame(0, $this->form_element_factory_add->call_count);
    }

    public function testItEnablesTheUsageOfTheField(): void
    {
        $patch = TrackerFieldPatchRepresentationTestBuilder::aPatch()->withUseIt(true)->build();

        $this->field = StringFieldBuilder::aStringField(1)->unused()->build();

        $field_remove_handler = new RestFieldUseHandler(new TrackerFormElementRemover($this->unuse_dao, $this->history_dao), new TrackerFieldAdder($this->form_element_factory_add));
        $field_remove_handler->handle($this->field, $patch, $this->current_user);

        self::assertSame(0, $this->unuse_dao->call_count);
        self::assertSame(1, $this->form_element_factory_add->call_count);
    }

    public function testItDisablesTheUsageOfTheField(): void
    {
        $patch = TrackerFieldPatchRepresentationTestBuilder::aPatch()->withUseIt(false)->build();

        $field = $this->createStub(TrackerFormElement::class);
        $field->method('getId')->willReturn(52);
        $field->method('getLabel')->willReturn('Label');
        $field->method('getTracker')->willReturn($this->tracker);
        $field->method('isUsed')->willReturn(true);
        $field->method('canBeRemovedFromUsage')->willReturn(true);

        $field_remove_handler = new RestFieldUseHandler(new TrackerFormElementRemover($this->unuse_dao, $this->history_dao), new TrackerFieldAdder($this->form_element_factory_add));
        $field_remove_handler->handle($field, $patch, $this->current_user);

        self::assertSame(1, $this->unuse_dao->call_count);
        self::assertSame(0, $this->form_element_factory_add->call_count);
    }
}
