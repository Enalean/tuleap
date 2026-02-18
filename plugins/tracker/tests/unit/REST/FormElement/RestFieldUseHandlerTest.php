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

use Luracast\Restler\RestException;
use Override;
use PFUser;
use PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;
use Tracker_Workflow_Trigger_RulesManager;
use TrackerFactory;
use Tuleap\REST\v1\TrackerFieldRepresentations\TrackerFieldPatchRepresentation;
use Tuleap\Stubs\AddHistoryStub;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\FormElement\Field\String\StringField;
use Tuleap\Tracker\FormElement\TrackerFieldAdder;
use Tuleap\Tracker\FormElement\TrackerFieldRemover;
use Tuleap\Tracker\Test\Builders\Fields\StringFieldBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use Tuleap\Tracker\Test\Stub\AddFieldStub;
use Tuleap\Tracker\Test\Stub\RemoveFieldStub;

#[DisableReturnValueGenerationForTestDoubles]
final class RestFieldUseHandlerTest extends TestCase
{
    private StringField $field;

    private RemoveFieldStub $form_element_factory_remove;
    private AddFieldStub $form_element_factory_add;
    private TrackerFactory&MockObject $tracker_factory;
    private Tracker_Workflow_Trigger_RulesManager&Stub $rules_manager;
    private PFUser $current_user;
    private AddHistoryStub $history_dao;

    #[Override]
    protected function setUp(): void
    {
        $project     = ProjectTestBuilder::aProject()->build();
        $tracker     = TrackerTestBuilder::aTracker()->withProject($project)->build();
        $this->field =  StringFieldBuilder::aStringField(1)->inTracker($tracker)->build();

        $this->current_user = UserTestBuilder::buildWithDefaults();

        $this->form_element_factory_remove = RemoveFieldStub::build();
        $this->form_element_factory_add    = AddFieldStub::build();

        $this->history_dao = AddHistoryStub::build();

        $this->tracker_factory = $this->createMock(TrackerFactory::class);
        $this->rules_manager   = $this->createStub(Tracker_Workflow_Trigger_RulesManager::class);
    }

    public function testItDoesNothingIfWeDoNotWantToUpdateTheUsageOfTheField(): void
    {
        $patch = new TrackerFieldPatchRepresentation(null, [], null, null);

        $this->tracker_factory->expects($this->never())->method('getTriggerRulesManager');

        $field_remove_handler = new RestFieldUseHandler(new TrackerFieldRemover($this->form_element_factory_remove, $this->tracker_factory, $this->history_dao), new TrackerFieldAdder($this->form_element_factory_add));
        $field_remove_handler->handle($this->field, $patch, $this->current_user);

        self::assertSame(0, $this->form_element_factory_remove->call_count);
        self::assertSame(0, $this->form_element_factory_add->call_count);
    }

    public function testItEnablesTheUsageOfTheField(): void
    {
        $patch = new TrackerFieldPatchRepresentation(null, [], true, null);

        $this->tracker_factory->expects($this->never())->method('getTriggerRulesManager');
        $this->field = StringFieldBuilder::aStringField(1)->unused()->build();

        $field_remove_handler = new RestFieldUseHandler(new TrackerFieldRemover($this->form_element_factory_remove, $this->tracker_factory, $this->history_dao), new TrackerFieldAdder($this->form_element_factory_add));
        $field_remove_handler->handle($this->field, $patch, $this->current_user);

        self::assertSame(0, $this->form_element_factory_remove->call_count);
        self::assertSame(1, $this->form_element_factory_add->call_count);
    }

    public function testItThrowsExceptionIfTheGivenFieldIsUsedInTrigger(): void
    {
        $patch = new TrackerFieldPatchRepresentation(null, [], false, null);

        $this->rules_manager->method('isUsedInTrigger')->willReturn(true);
        $this->tracker_factory->expects($this->once())->method('getTriggerRulesManager')->willReturn($this->rules_manager);

        $this->expectException(RestException::class);

        $field_remove_handler = new RestFieldUseHandler(new TrackerFieldRemover($this->form_element_factory_remove, $this->tracker_factory, $this->history_dao), new TrackerFieldAdder($this->form_element_factory_add));
        $field_remove_handler->handle($this->field, $patch, $this->current_user);


        self::assertSame(1, $this->form_element_factory_remove->call_count);
        self::assertSame(0, $this->form_element_factory_add->call_count);
    }

    public function testItDisablesTheUsageOfTheField(): void
    {
        $patch = new TrackerFieldPatchRepresentation(null, [], false, null);

        $this->tracker_factory->expects($this->once())->method('getTriggerRulesManager')->willReturn($this->rules_manager);
        $this->rules_manager->method('isUsedInTrigger')->willReturn(false);

        $field_remove_handler = new RestFieldUseHandler(new TrackerFieldRemover($this->form_element_factory_remove, $this->tracker_factory, $this->history_dao), new TrackerFieldAdder($this->form_element_factory_add));
        $field_remove_handler->handle($this->field, $patch, $this->current_user);

        self::assertSame(1, $this->form_element_factory_remove->call_count);
        self::assertSame(0, $this->form_element_factory_add->call_count);
    }
}
