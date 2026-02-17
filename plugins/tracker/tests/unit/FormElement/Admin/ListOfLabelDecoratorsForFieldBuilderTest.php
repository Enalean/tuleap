<?php
/**
 * Copyright (c) Enalean, 2026 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\FormElement\Admin;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\FormElement\Field\Text\TextField;
use Tuleap\Tracker\FormElement\Field\TrackerField;
use Tuleap\Tracker\Semantic\CollectionOfSemanticsUsingAParticularTrackerField;
use Tuleap\Tracker\Semantic\Title\TrackerSemanticTitle;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use Tuleap\Tracker\Test\Stub\Workflow\FieldDependencies\ProvideFieldDependenciesUsageByFieldStub;
use Tuleap\Tracker\Test\Stub\Workflow\PostAction\ProvideWorkflowActionUsageByFieldStub;
use Tuleap\Tracker\Test\Stub\Workflow\ProvideGlobalRulesUsageByFieldStub;
use Tuleap\Tracker\Test\Stub\Workflow\Transition\Condition\ProvideWorkflowConditionUsageByFieldStub;
use Tuleap\Tracker\Test\Stub\Workflow\Trigger\ProvideParentsTriggersUsageByFieldStub;
use Tuleap\Tracker\Test\Stub\Workflow\Trigger\ProvideTriggersUsageByFieldStub;
use Tuleap\Tracker\Workflow\WorkflowFieldUsageDecoratorsProvider;

#[DisableReturnValueGenerationForTestDoubles]
final class ListOfLabelDecoratorsForFieldBuilderTest extends TestCase
{
    #[DataProvider('getFields')]
    public function testDecorators(bool $has_semantic, bool $has_workflow, bool $has_notifications, int $expected_count): void
    {
        $builder = new ListOfLabelDecoratorsForFieldBuilder(new WorkflowFieldUsageDecoratorsProvider(
            $has_workflow
                ? ProvideGlobalRulesUsageByFieldStub::withGlobalRules()
                : ProvideGlobalRulesUsageByFieldStub::withoutGlobalRules(),
            ProvideFieldDependenciesUsageByFieldStub::withoutFieldDependencies(),
            ProvideTriggersUsageByFieldStub::withoutTriggers(),
            ProvideParentsTriggersUsageByFieldStub::withoutParentTriggers(),
            ProvideWorkflowConditionUsageByFieldStub::withoutWorkflowCondition(),
            ProvideWorkflowActionUsageByFieldStub::withoutWorkflowAction()
        ));

        $field = $this->getFormElement($has_semantic, $has_notifications);

        self::assertCount($expected_count, $builder->getLabelDecorators($field));
    }

    public static function getFields(): iterable
    {
        yield 'no semantic, no workflow, no notification' => [false, false, false, 0];

        yield 'only semantic' => [true, false, false, 1];
        yield 'only workflow' => [false, true, false, 1];
        yield 'only notification' => [false, false, true, 1];

        yield 'semantic and workflow' => [true, true, false, 2];
        yield 'semantic and notification' => [true, false, true, 2];
        yield 'workflow and notification' => [false, true, true, 2];

        yield 'semantic and workflow and notification' => [true, true, true, 3];
    }

    private function getFormElement(bool $has_semantic, bool $has_notifications): TrackerField
    {
        $tracker = TrackerTestBuilder::aTracker()->build();

        $form_element = $this->createStub(TextField::class);
        $form_element->method('getId')->willReturn(123);
        $form_element->method('getTracker')->willReturn($tracker);

        $semantics = $has_semantic ? [new TrackerSemanticTitle($tracker, $form_element)] : [];
        $form_element->method('getUsagesInSemantics')->willReturn(
            new CollectionOfSemanticsUsingAParticularTrackerField($form_element, $semantics),
        );
        $form_element->method('hasNotifications')->willReturn($has_notifications);

        return $form_element;
    }
}
