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

namespace Tuleap\Tracker\Workflow;

use PHPUnit\Framework\Attributes\DataProvider;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\FormElement\Admin\LabelDecorator;
use Tuleap\Tracker\FormElement\Field\TrackerField;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use Tuleap\Tracker\Test\Stub\Workflow\FieldDependencies\ProvideFieldDependenciesUsageByFieldStub;
use Tuleap\Tracker\Test\Stub\Workflow\PostAction\ProvideWorkflowActionUsageByFieldStub;
use Tuleap\Tracker\Test\Stub\Workflow\ProvideGlobalRulesUsageByFieldStub;
use Tuleap\Tracker\Test\Stub\Workflow\Transition\Condition\ProvideWorkflowConditionUsageByFieldStub;
use Tuleap\Tracker\Test\Stub\Workflow\Transition\ProvideWorkflowTransitionUsageByFieldStub;
use Tuleap\Tracker\Test\Stub\Workflow\Trigger\ProvideParentsTriggersUsageByFieldStub;
use Tuleap\Tracker\Test\Stub\Workflow\Trigger\ProvideTriggersUsageByFieldStub;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
class WorkflowFieldUsageDecoratorsProviderTest extends TestCase
{
    private static function getExpectedGlobalRulesLabelDecorator(): LabelDecorator
    {
        return LabelDecorator::buildWithUrl(
            dgettext('tuleap-tracker', 'Global rules'),
            dgettext('tuleap-tracker', 'This field is used by global rules'),
            WorkflowUrlBuilder::buildGlobalRulesUrl(TrackerTestBuilder::aTracker()->build())
        );
    }

    private static function getExpectedFieldDependenciesLabelDecorator(): LabelDecorator
    {
        return LabelDecorator::buildWithUrl(
            dgettext('tuleap-tracker', 'Field dependencies'),
            dgettext('tuleap-tracker', 'This field is used by field dependencies'),
            WorkflowUrlBuilder::buildFieldDependenciesUrl(TrackerTestBuilder::aTracker()->build())
        );
    }

    private static function getExpectedTriggersLabelDecorator(): LabelDecorator
    {
        return LabelDecorator::buildWithUrl(
            dgettext('tuleap-tracker', 'Triggers'),
            dgettext('tuleap-tracker', 'This field is used by triggers'),
            WorkflowUrlBuilder::buildTriggersUrl(TrackerTestBuilder::aTracker()->build())
        );
    }

    private static function getExpectedParentTriggersLabelDecorator(): LabelDecorator
    {
        return LabelDecorator::buildWithUrl(
            dgettext('tuleap-tracker', 'Parent triggers'),
            dgettext('tuleap-tracker', 'This field is used by parent triggers'),
            WorkflowUrlBuilder::buildTriggersUrl(TrackerTestBuilder::aTracker()->build())
        );
    }

    private static function getExpectedWorkflowConditionsLabelDecorator(): LabelDecorator
    {
        return LabelDecorator::buildWithUrl(
            dgettext('tuleap-tracker', 'Workflow condition'),
            dgettext('tuleap-tracker', 'This field is used by workflow conditions'),
            WorkflowUrlBuilder::buildTransitionsUrl(TrackerTestBuilder::aTracker()->build())
        );
    }

    private static function getExpectedWorkflowActionsLabelDecorator(): LabelDecorator
    {
        return LabelDecorator::buildWithUrl(
            dgettext('tuleap-tracker', 'Workflow action'),
            dgettext('tuleap-tracker', 'This field is used by workflow actions'),
            WorkflowUrlBuilder::buildTransitionsUrl(TrackerTestBuilder::aTracker()->build())
        );
    }

    private static function getExpectedWorkflowTransitionsLabelDecorator(): LabelDecorator
    {
        return LabelDecorator::buildWithUrl(
            dgettext('tuleap-tracker', 'Workflow transition'),
            dgettext('tuleap-tracker', 'This field is used by workflow transitions'),
            WorkflowUrlBuilder::buildTransitionsUrl(TrackerTestBuilder::aTracker()->build())
        );
    }

    /**
     * @param LabelDecorator[] $expected_label_decorators
     */
    #[DataProvider('getFields')]
    public function testWorkflowDecorators(
        bool $has_global_rules,
        bool $has_field_dependencies,
        bool $has_triggers,
        bool $has_parent_triggers,
        bool $has_workflow_condition,
        bool $has_workflow_action,
        bool $has_workflow_transition,
        array $expected_label_decorators,
    ): void {
        $tracker = TrackerTestBuilder::aTracker()->build();

        $field = $this->createMock(TrackerField::class);
        $field->method('getTracker')->willReturn($tracker);

        $global_rules_usage_provider = $has_global_rules
            ? ProvideGlobalRulesUsageByFieldStub::withGlobalRules()
            : ProvideGlobalRulesUsageByFieldStub::withoutGlobalRules();

        $field_dependencies_usage_provider = $has_field_dependencies
            ? ProvideFieldDependenciesUsageByFieldStub::withFieldDependencies()
            : ProvideFieldDependenciesUsageByFieldStub::withoutFieldDependencies();

        $triggers_usage_provider = $has_triggers
            ? ProvideTriggersUsageByFieldStub::withTriggers()
            : ProvideTriggersUsageByFieldStub::withoutTriggers();

        $parent_triggers_usage_provider = $has_parent_triggers
            ? ProvideParentsTriggersUsageByFieldStub::withParentTriggers()
            : ProvideParentsTriggersUsageByFieldStub::withoutParentTriggers();

        $workflow_condition_usage_provider = $has_workflow_condition
            ? ProvideWorkflowConditionUsageByFieldStub::withWorkflowCondition()
            : ProvideWorkflowConditionUsageByFieldStub::withoutWorkflowCondition();

        $workflow_action_usage_provider = $has_workflow_action
            ? ProvideWorkflowActionUsageByFieldStub::withWorkflowAction()
            : ProvideWorkflowActionUsageByFieldStub::withoutWorkflowAction();

        $workflow_transition_usage_provider = $has_workflow_transition
            ? ProvideWorkflowTransitionUsageByFieldStub::withWorkflowTransition()
            : ProvideWorkflowTransitionUsageByFieldStub::withoutWorkflowTransition();

        $decorators_provider = new WorkflowFieldUsageDecoratorsProvider(
            $global_rules_usage_provider,
            $field_dependencies_usage_provider,
            $triggers_usage_provider,
            $parent_triggers_usage_provider,
            $workflow_condition_usage_provider,
            $workflow_action_usage_provider,
            $workflow_transition_usage_provider
        );

        self::assertEquals($expected_label_decorators, $decorators_provider->getLabelDecorators($field));
    }

    public static function getFields(): iterable
    {
        yield 'no workflow' => [
            false,
            false,
            false,
            false,
            false,
            false,
            false,
            [],
        ];

        yield 'global rules only' => [
            true,
            false,
            false,
            false,
            false,
            false,
            false,
            [self::getExpectedGlobalRulesLabelDecorator()],
        ];

        yield 'field dependencies only' => [
            false,
            true,
            false,
            false,
            false,
            false,
            false,
            [self::getExpectedFieldDependenciesLabelDecorator()],
        ];

        yield 'triggers only' => [
            false,
            false,
            true,
            false,
            false,
            false,
            false,
            [self::getExpectedTriggersLabelDecorator()],
        ];

        yield 'parent triggers only' => [
            false,
            false,
            false,
            true,
            false,
            false,
            false,
            [self::getExpectedParentTriggersLabelDecorator()],
        ];

        yield 'workflow conditions only' => [
            false,
            false,
            false,
            false,
            true,
            false,
            false,
            [self::getExpectedWorkflowConditionsLabelDecorator()],
        ];

        yield 'workflow actions only' => [
            false,
            false,
            false,
            false,
            false,
            true,
            false,
            [self::getExpectedWorkflowActionsLabelDecorator()],
        ];

        yield 'workflow transition only' => [
            false,
            false,
            false,
            false,
            false,
            false,
            true,
            [self::getExpectedWorkflowTransitionsLabelDecorator()],
        ];

        yield 'global rules and field dependencies' => [
            true,
            true,
            false,
            false,
            false,
            false,
            false,
            [self::getExpectedGlobalRulesLabelDecorator(), self::getExpectedFieldDependenciesLabelDecorator()],
        ];

        yield 'global rules and triggers' => [
            true,
            false,
            true,
            false,
            false,
            false,
            false,
            [self::getExpectedGlobalRulesLabelDecorator(), self::getExpectedTriggersLabelDecorator()],
        ];

        yield 'global rules and parent triggers' => [
            true,
            false,
            false,
            true,
            false,
            false,
            false,
            [self::getExpectedGlobalRulesLabelDecorator(), self::getExpectedParentTriggersLabelDecorator(),],
        ];

        yield 'field dependencies, triggers' => [
            false,
            true,
            true,
            false,
            false,
            false,
            false,
            [self::getExpectedFieldDependenciesLabelDecorator(), self::getExpectedTriggersLabelDecorator()],
        ];

        yield 'triggers, parent triggers' => [
            false,
            false,
            true,
            true,
            false,
            false,
            false,
            [self::getExpectedTriggersLabelDecorator(), self::getExpectedParentTriggersLabelDecorator()],
        ];

        yield 'global rules, field dependencies, triggers, parent triggers, workflow conditions' => [
            true,
            true,
            true,
            true,
            true,
            true,
            true,
            [
                self::getExpectedGlobalRulesLabelDecorator(),
                self::getExpectedFieldDependenciesLabelDecorator(),
                self::getExpectedTriggersLabelDecorator(),
                self::getExpectedParentTriggersLabelDecorator(),
                self::getExpectedWorkflowConditionsLabelDecorator(),
                self::getExpectedWorkflowActionsLabelDecorator(),
                self::getExpectedWorkflowTransitionsLabelDecorator(),
            ],
        ];
    }
}
