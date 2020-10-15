<?php
/**
 * Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
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


namespace Tuleap\Taskboard\REST\v1;

use PFUser;
use Tuleap\AgileDashboard\RemainingEffortValueRetriever;
use Tuleap\Tracker\Artifact\Artifact;

class RemainingEffortRepresentationBuilder
{
    /**
     * @var RemainingEffortValueRetriever
     */
    private $remaining_effort_value_retriever;
    /**
     * @var \Tracker_FormElementFactory
     */
    private $form_element_factory;

    public function __construct(
        RemainingEffortValueRetriever $remaining_effort_value_retriever,
        \Tracker_FormElementFactory $form_element_factory
    ) {
        $this->remaining_effort_value_retriever = $remaining_effort_value_retriever;
        $this->form_element_factory             = $form_element_factory;
    }

    public function getRemainingEffort(PFUser $current_user, Artifact $artifact): ?RemainingEffortRepresentation
    {
        $remaining_effort_field = $this->form_element_factory->getNumericFieldByNameForUser(
            $artifact->getTracker(),
            $current_user,
            \Tracker::REMAINING_EFFORT_FIELD_NAME
        );
        if (! $remaining_effort_field) {
            return null;
        }

        return new RemainingEffortRepresentation(
            $this->getValue($current_user, $artifact),
            $remaining_effort_field->userCanUpdate($current_user)
        );
    }

    private function getValue(PFUser $current_user, Artifact $artifact): ?float
    {
        $value = $this->remaining_effort_value_retriever->getRemainingEffortValue($current_user, $artifact);

        if (! is_numeric($value)) {
            return null;
        }

        return (float) $value;
    }
}
