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

readonly class WorkflowFieldUsageDecoratorsProvider
{
    public function __construct(
        private ProvideGlobalRulesUsageByField $workflow_usage_provider,
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

    /**
     * @return LabelDecorator[]
     */
    public function getLabelDecorators(TrackerField $field): array
    {
        $decorators = [];

        if ($this->workflow_usage_provider->isFieldUsedInGlobalRules($field)) {
            $decorators[] = $this->getGlobalRulesLabelDecorator($field);
        }

        return $decorators;
    }
}
