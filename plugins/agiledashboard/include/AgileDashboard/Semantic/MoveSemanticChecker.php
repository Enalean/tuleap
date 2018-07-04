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

namespace Tuleap\AgileDashboard\Semantic;

use AgileDashboard_Semantic_InitialEffortFactory;
use Tracker;
use Tracker_FormElement_Field;
use Tracker_FormElementFactory;

class MoveSemanticChecker
{
    /**
     * @var AgileDashboard_Semantic_InitialEffortFactory
     */
    private $initial_effort_factory;

    /**
     * @var Tracker_FormElementFactory
     */
    private $form_element_factory;

    public function __construct(
        AgileDashboard_Semantic_InitialEffortFactory $initial_effort_factory,
        Tracker_FormElementFactory $form_element_factory
    ) {
        $this->initial_effort_factory = $initial_effort_factory;
        $this->form_element_factory   = $form_element_factory;
    }

    public function areSemanticsAligned(Tracker $source_tracker, Tracker $target_tracker)
    {
        $source_initial_effort = $this->initial_effort_factory->getByTracker($source_tracker);
        $target_initiel_effort = $this->initial_effort_factory->getByTracker($target_tracker);

        $source_initial_effort_field = $source_initial_effort->getField();
        $target_initiel_effort_field = $target_initiel_effort->getField();

        return ($source_initial_effort_field &&
            $target_initiel_effort_field &&
            $this->areFieldsTypeAligned($source_initial_effort_field, $target_initiel_effort_field)
        );
    }

    private function areFieldsTypeAligned(
        Tracker_FormElement_Field $source_initial_effort_field,
        Tracker_FormElement_Field $target_initiel_effort_field
    ) {
        return $this->form_element_factory->getType($source_initial_effort_field) ===
            $this->form_element_factory->getType($target_initiel_effort_field);
    }
}
