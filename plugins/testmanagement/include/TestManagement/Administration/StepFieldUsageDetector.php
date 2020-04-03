<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

namespace Tuleap\TestManagement\Administration;

use Tracker_FormElementFactory;
use TrackerFactory;
use Tuleap\TestManagement\Step\Definition\Field\StepDefinition;
use Tuleap\TestManagement\Step\Execution\Field\StepExecution;

class StepFieldUsageDetector
{
    /** @var TrackerFactory */
    private $tracker_factory;

    /** @var Tracker_FormElementFactory */
    private $form_element_factory;

    public function __construct(TrackerFactory $tracker_factory, Tracker_FormElementFactory $form_element_factory)
    {
        $this->tracker_factory      = $tracker_factory;
        $this->form_element_factory = $form_element_factory;
    }

    public function isStepDefinitionFieldUsed(int $tracker_id): bool
    {
        return $this->isFieldUsed($tracker_id, StepDefinition::TYPE);
    }

    public function isStepExecutionFieldUsed(int $tracker_id): bool
    {
        return $this->isFieldUsed($tracker_id, StepExecution::TYPE);
    }

    /**
     * @param int    $tracker_id
     * @param string $type
     *
     * @return bool
     */
    private function isFieldUsed($tracker_id, $type)
    {
        $tracker = $this->tracker_factory->getTrackerById($tracker_id);
        if ($tracker === null) {
            return false;
        }

        $field = $this->form_element_factory->getUsedFormElementsByType($tracker, $type);

        return ! empty($field);
    }
}
