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
use PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;
use Tracker_Workflow_Trigger_RulesManager;
use TrackerFactory;
use Tuleap\NeverThrow\Result;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\FormElement\Field\String\StringField;
use Tuleap\Tracker\Test\Builders\Fields\StringFieldBuilder;
use Tuleap\Tracker\Test\Stub\RemoveFieldStub;

#[DisableReturnValueGenerationForTestDoubles]
final class TrackerFieldRemoverTest extends TestCase
{
    private StringField $field;

    private RemoveFieldStub $form_element_factory_remove;

    private TrackerFactory&MockObject $tracker_factory;
    private Tracker_Workflow_Trigger_RulesManager&Stub $rules_manager;

    #[Override]
    protected function setUp(): void
    {
        $this->field = StringFieldBuilder::aStringField(1)->build();

        $this->form_element_factory_remove = RemoveFieldStub::build();
        $this->tracker_factory             = $this->createMock(TrackerFactory::class);
        $this->rules_manager               = $this->createStub(Tracker_Workflow_Trigger_RulesManager::class);
    }

    public function testItReturnsAnErrorIfTheCurrentFieldIsUsedInATrigger(): void
    {
        $this->rules_manager->method('isUsedInTrigger')->willReturn(true);
        $this->tracker_factory->expects($this->once())->method('getTriggerRulesManager')->willReturn(
            $this->rules_manager
        );

        $field_remover = new TrackerFieldRemover($this->form_element_factory_remove, $this->tracker_factory);
        $value         = $field_remover->remove($this->field);

        self::assertTrue(Result::isErr($value));
        self::assertSame(0, $this->form_element_factory_remove->call_count);
    }

    public function testItReturnsOkIfTheCurrentFieldCanBeRemoved(): void
    {
        $this->rules_manager->method('isUsedInTrigger')->willReturn(false);
        $this->tracker_factory->expects($this->once())->method('getTriggerRulesManager')->willReturn(
            $this->rules_manager
        );

        $field_remover = new TrackerFieldRemover($this->form_element_factory_remove, $this->tracker_factory);
        $value         = $field_remover->remove($this->field);

        self::assertTrue(Result::isOk($value));
        self::assertSame(1, $this->form_element_factory_remove->call_count);
    }
}
