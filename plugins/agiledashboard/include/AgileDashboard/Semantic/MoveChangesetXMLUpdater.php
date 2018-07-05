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
use SimpleXMLElement;
use Tracker;
use Tracker_FormElement_Field;

class MoveChangesetXMLUpdater
{
    /**
     * @var AgileDashboard_Semantic_InitialEffortFactory
     */
    private $initial_effort_factory;

    public function __construct(AgileDashboard_Semantic_InitialEffortFactory $initial_effort_factory)
    {
        $this->initial_effort_factory = $initial_effort_factory;
    }

    /**
     * @return bool
     */
    public function parseFieldChangeNodesAtGivenIndex(
        Tracker $source_tracker,
        Tracker $target_tracker,
        SimpleXMLElement $changeset_xml,
        $index
    ) {
        $source_initial_effort_field = $this->initial_effort_factory->getByTracker($source_tracker)->getField();
        $target_initial_effort_field = $this->initial_effort_factory->getByTracker($target_tracker)->getField();

        if ($target_initial_effort_field &&
            $this->isFieldChangeCorrespondingToTitleSemanticField($changeset_xml, $source_initial_effort_field, $index)
        ) {
            $this->useTargetTrackerFieldName($changeset_xml, $target_initial_effort_field, $index);
            return true;
        }

        return false;
    }

    private function isFieldChangeCorrespondingToTitleSemanticField(
        SimpleXMLElement $changeset_xml,
        Tracker_FormElement_Field $source_field,
        $index
    ) {
        $field_change = $changeset_xml->field_change[$index];

        return (string)$field_change['field_name'] === $source_field->getName();
    }

    private function useTargetTrackerFieldName(
        SimpleXMLElement $changeset_xml,
        Tracker_FormElement_Field $target_field,
        $index
    ) {
        $changeset_xml->field_change[$index]['field_name'] = $target_field->getName();
    }
}
