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

use BackendLogger;
use EventManager;
use ForgeConfig;
use Override;
use Tracker_ArtifactFactory;
use Tracker_FormElement_Field_List_BindFactory;
use Tracker_FormElementFactory;
use Tracker_Rule_Date_Dao;
use Tracker_Rule_Date_Factory;
use Tracker_Rule_List_Dao;
use Tracker_Rule_List_Factory;
use Tracker_Workflow_Trigger_RulesBuilderFactory;
use Tracker_Workflow_Trigger_RulesDao;
use Tracker_Workflow_Trigger_RulesManager;
use Tracker_Workflow_Trigger_RulesProcessor;
use Tracker_Workflow_WorkflowUser;
use TrackerFactory;
use Transition_PostActionFactory;
use Tuleap\DB\DatabaseUUIDV7Factory;
use Tuleap\Tracker\FormElement\Field\TrackerField;
use Tuleap\Tracker\FormElement\TrackerFormElement;
use Tuleap\Tracker\Hierarchy\HierarchyDAO;
use Tuleap\Tracker\Hierarchy\ParentInHierarchyRetriever;
use Tuleap\Tracker\Workflow\FieldDependencies\FieldDependenciesUsageByFieldProvider;
use Tuleap\Tracker\Workflow\GlobalRulesUsageByFieldProvider;
use Tuleap\Tracker\Workflow\PostAction\WorkflowActionUsageByFieldProvider;
use Tuleap\Tracker\Workflow\Transition\Condition\WorkflowConditionUsageByFieldProvider;
use Tuleap\Tracker\Workflow\Transition\WorkflowTransitionUsageByFieldProvider;
use Tuleap\Tracker\Workflow\Trigger\ParentsTriggersUsageByFieldProvider;
use Tuleap\Tracker\Workflow\Trigger\Siblings\SiblingsDao;
use Tuleap\Tracker\Workflow\Trigger\Siblings\SiblingsRetriever;
use Tuleap\Tracker\Workflow\Trigger\TriggersDao;
use Tuleap\Tracker\Workflow\Trigger\TriggersUsageByFieldProvider;
use Tuleap\Tracker\Workflow\WorkflowBackendLogger;
use Tuleap\Tracker\Workflow\WorkflowFieldUsageDecoratorsProvider;
use Tuleap\Tracker\Workflow\WorkflowRulesManagerLoopSafeGuard;
use Workflow_Transition_ConditionFactory;
use WorkflowFactory;

final readonly class ListOfLabelDecoratorsForFieldBuilder implements BuildListOfLabelDecoratorsForField
{
    public function __construct(public WorkflowFieldUsageDecoratorsProvider $workflow_field_usage_decorators)
    {
    }

    public static function build(): self
    {
        $logger               = new WorkflowBackendLogger(BackendLogger::getDefaultLogger(), ForgeConfig::get('sys_logger_level'));
        $form_element_factory = Tracker_FormElementFactory::instance();
        $triggers_dao         = new TriggersDao();
        $trigger_rule_manager = new Tracker_Workflow_Trigger_RulesManager(
            new Tracker_Workflow_Trigger_RulesDao(),
            $form_element_factory,
            new Tracker_Workflow_Trigger_RulesProcessor(
                new Tracker_Workflow_WorkflowUser(),
                new SiblingsRetriever(
                    new SiblingsDao(),
                    Tracker_ArtifactFactory::instance()
                ),
                $logger,
            ),
            $logger,
            new Tracker_Workflow_Trigger_RulesBuilderFactory($form_element_factory),
            new WorkflowRulesManagerLoopSafeGuard($logger)
        );

        return new self(new WorkflowFieldUsageDecoratorsProvider(
            new GlobalRulesUsageByFieldProvider(
                new Tracker_Rule_Date_Factory(new Tracker_Rule_Date_Dao(), $form_element_factory)
            ),
            new FieldDependenciesUsageByFieldProvider(
                new Tracker_Rule_List_Factory(new Tracker_Rule_List_Dao(), new Tracker_FormElement_Field_List_BindFactory(new DatabaseUUIDV7Factory())),
            ),
            new TriggersUsageByFieldProvider($trigger_rule_manager, $triggers_dao),
            new ParentsTriggersUsageByFieldProvider($trigger_rule_manager, $triggers_dao, new ParentInHierarchyRetriever(new HierarchyDAO(), TrackerFactory::instance())),
            new WorkflowConditionUsageByFieldProvider(Workflow_Transition_ConditionFactory::build()),
            new WorkflowActionUsageByFieldProvider(new Transition_PostActionFactory(
                EventManager::instance(),
                BackendLogger::getDefaultLogger(),
            )),
            new WorkflowTransitionUsageByFieldProvider(
                WorkflowFactory::instance()
            ),
        ));
    }

    #[Override]
    public function getLabelDecorators(TrackerFormElement $form_element): array
    {
        $decorators = [];
        if ($form_element instanceof TrackerField) {
            $decorators = array_merge(
                $form_element->getUsagesInSemantics()->getLabelDecorators(),
                $this->workflow_field_usage_decorators->getLabelDecorators($form_element)
            );
        }

        if ($form_element->hasNotifications()) {
            $decorators[] = LabelDecorator::buildWithIcon(
                dgettext('tuleap-tracker', 'Notifications'),
                dgettext('tuleap-tracker', 'People selected in this field may receive notifications.'),
                'fa-solid fa-bell',
            );
        }

        return $decorators;
    }
}
