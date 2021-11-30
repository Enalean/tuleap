<?php
/**
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\AgileDashboard\Semantic\XML;

use AgileDashBoard_Semantic_InitialEffort;
use Tuleap\Tracker\Creation\JiraImporter\Configuration\PlatformConfiguration;
use Tuleap\Tracker\Creation\JiraImporter\Import\Structure\FieldMappingCollection;

final class SemanticsExporter
{
    public function process(
        \SimpleXMLElement $xml_tracker,
        PlatformConfiguration $platform_configuration,
        FieldMappingCollection $field_mapping_collection,
    ): void {
        if (! $xml_tracker->semantics) {
            throw new \LogicException('tracker XML node does not have a `semantics` node');
        }

        $this->exportInitialEffort($platform_configuration, $field_mapping_collection, $xml_tracker->semantics);
    }

    private function exportInitialEffort(
        PlatformConfiguration $platform_configuration,
        FieldMappingCollection $field_mapping_collection,
        \SimpleXMLElement $xml_semantics,
    ): void {
        if (! $platform_configuration->hasStoryPointsField()) {
            return;
        }

        $story_points_field = $field_mapping_collection->getMappingFromJiraField(
            $platform_configuration->getStoryPointsField()
        );
        if (! $story_points_field) {
            return;
        }

        $semantic_node = $xml_semantics->addChild('semantic');
        $semantic_node->addAttribute("type", AgileDashBoard_Semantic_InitialEffort::NAME);

        $semantic_node->addChild("shortname", AgileDashBoard_Semantic_InitialEffort::NAME);
        $semantic_node->addChild("label", dgettext('tuleap-agiledashboard', 'Initial Effort'));
        $semantic_node->addChild(
            "description",
            dgettext('tuleap-agiledashboard', 'Define the initial effort of an artifact.')
        );
        $field_node = $semantic_node->addChild("field");
        $field_node->addAttribute("REF", $story_points_field->getXMLId());
    }
}
