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

use Tuleap\Tracker\FormElement\Admin\LabelDecorator;
use Tuleap\Tracker\FormElement\Field\TrackerField;
use Tuleap\Tracker\Tracker;
use Tuleap\Tracker\Workflow\FieldDependencies\ProvideFieldDependenciesUsageByField;
use Tuleap\Tracker\Workflow\PostAction\ProvideWorkflowActionUsageByField;
use Tuleap\Tracker\Workflow\Transition\Condition\ProvideWorkflowConditionUsageByField;
use Tuleap\Tracker\Workflow\Transition\ProvideWorkflowTransitionUsageByField;
use Tuleap\Tracker\Workflow\Trigger\ProvideParentsTriggersUsageByField;
use Tuleap\Tracker\Workflow\Trigger\ProvideTriggersUsageByField;

readonly class WorkflowFieldUsageDecoratorsProvider
{
    public function __construct(
        private ProvideGlobalRulesUsageByField $global_rules_usage,
        private ProvideFieldDependenciesUsageByField $field_dependencies_usage,
        private ProvideTriggersUsageByField $triggers_usage,
        private ProvideParentsTriggersUsageByField $parents_triggers_usage,
        private ProvideWorkflowConditionUsageByField $condition_usage,
        private ProvideWorkflowActionUsageByField $action_usage,
        private ProvideWorkflowTransitionUsageByField $transition_usage,
    ) {
    }

    private function getGlobalRulesLabelDecorator(TrackerField $field): LabelDecorator
    {
        return LabelDecorator::buildWithUrl(
            dgettext('tuleap-tracker', 'Global rules'),
            dgettext('tuleap-tracker', 'This field is used by global rules'),
            WorkflowUrlBuilder::buildGlobalRulesUrl($field->getTracker()),
        );
    }

    private function getFieldDependenciesLabelDecorator(TrackerField $field): LabelDecorator
    {
        return LabelDecorator::buildWithUrl(
            dgettext('tuleap-tracker', 'Field dependencies'),
            dgettext('tuleap-tracker', 'This field is used by field dependencies'),
            WorkflowUrlBuilder::buildFieldDependenciesUrl($field->getTracker()),
        );
    }

    private function getTriggersLabelDecorator(TrackerField $field): LabelDecorator
    {
        return LabelDecorator::buildWithUrl(
            dgettext('tuleap-tracker', 'Triggers'),
            dgettext('tuleap-tracker', 'This field is used by triggers'),
            WorkflowUrlBuilder::buildTriggersUrl($field->getTracker()),
        );
    }

    private function getParentTriggersLabelDecorator(Tracker $parent_tracker): LabelDecorator
    {
        return LabelDecorator::buildWithUrl(
            dgettext('tuleap-tracker', 'Parent triggers'),
            dgettext('tuleap-tracker', 'This field is used by parent triggers'),
            WorkflowUrlBuilder::buildTriggersUrl($parent_tracker),
        );
    }

    private function getWorkflowConditionsLabelDecorator(TrackerField $field): LabelDecorator
    {
        return LabelDecorator::buildWithUrl(
            dgettext('tuleap-tracker', 'Workflow condition'),
            dgettext('tuleap-tracker', 'This field is used by workflow conditions'),
            WorkflowUrlBuilder::buildTransitionsUrl($field->getTracker()),
        );
    }

    private function getWorkflowActionsLabelDecorator(TrackerField $field): LabelDecorator
    {
        return LabelDecorator::buildWithUrl(
            dgettext('tuleap-tracker', 'Workflow action'),
            dgettext('tuleap-tracker', 'This field is used by workflow actions'),
            WorkflowUrlBuilder::buildTransitionsUrl($field->getTracker()),
        );
    }

    private function getWorkflowTransitionsLabelDecorator(TrackerField $field): LabelDecorator
    {
        return LabelDecorator::buildWithUrl(
            dgettext('tuleap-tracker', 'Workflow transition'),
            dgettext('tuleap-tracker', 'This field is used by workflow transitions'),
            WorkflowUrlBuilder::buildTransitionsUrl($field->getTracker()),
        );
    }

    /**
     * @return LabelDecorator[]
     */
    public function getLabelDecorators(TrackerField $field): array
    {
        $decorators = [];

        if ($this->global_rules_usage->isFieldUsedInGlobalRules($field)) {
            $decorators[] = $this->getGlobalRulesLabelDecorator($field);
        }

        if ($this->field_dependencies_usage->isFieldUsedInFieldDependencies($field)) {
            $decorators[] = $this->getFieldDependenciesLabelDecorator($field);
        }

        if ($this->triggers_usage->isFieldUsedInCurrentTrackerTriggers($field)) {
            $decorators[] = $this->getTriggersLabelDecorator($field);
        }

        if ($this->parents_triggers_usage->isFieldUsedInParentTrackerTriggers($field)) {
            $this->parents_triggers_usage->getParentTracker($field)->apply(
                function (Tracker $tracker) use (&$decorators): void {
                    $decorators[] = $this->getParentTriggersLabelDecorator($tracker);
                }
            );
        }

        if ($this->condition_usage->isFieldUsedInWorkflowConditions($field)) {
            $decorators[] = $this->getWorkflowConditionsLabelDecorator($field);
        }

        if ($this->action_usage->isFieldUsedInWorkflowActions($field)) {
            $decorators[] = $this->getWorkflowActionsLabelDecorator($field);
        }

        if ($this->transition_usage->isFieldUsedInWorkflowTransitions($field)) {
            $decorators[] = $this->getWorkflowTransitionsLabelDecorator($field);
        }

        return $decorators;
    }
}
