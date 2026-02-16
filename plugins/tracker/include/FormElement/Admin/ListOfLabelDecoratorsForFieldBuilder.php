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

use Override;
use Tracker_FormElement_Field_List_BindFactory;
use Tracker_FormElementFactory;
use Tracker_Rule_Date_Dao;
use Tracker_Rule_Date_Factory;
use Tracker_Rule_List_Dao;
use Tracker_Rule_List_Factory;
use Tuleap\DB\DatabaseUUIDV7Factory;
use Tuleap\Tracker\FormElement\Field\TrackerField;
use Tuleap\Tracker\FormElement\TrackerFormElement;
use Tuleap\Tracker\Workflow\FieldDependencies\FieldDependenciesUsageByFieldProvider;
use Tuleap\Tracker\Workflow\GlobalRulesUsageByFieldProvider;
use Tuleap\Tracker\Workflow\WorkflowFieldUsageDecoratorsProvider;

final readonly class ListOfLabelDecoratorsForFieldBuilder implements BuildListOfLabelDecoratorsForField
{
    public function __construct(public WorkflowFieldUsageDecoratorsProvider $workflow_field_usage_decorators)
    {
    }

    public static function build(): self
    {
        return new self(new WorkflowFieldUsageDecoratorsProvider(
            new GlobalRulesUsageByFieldProvider(
                new Tracker_Rule_Date_Factory(new Tracker_Rule_Date_Dao(), Tracker_FormElementFactory::instance())
            ),
            new FieldDependenciesUsageByFieldProvider(
                new Tracker_Rule_List_Factory(new Tracker_Rule_List_Dao(), new Tracker_FormElement_Field_List_BindFactory(new DatabaseUUIDV7Factory())),
            )
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
