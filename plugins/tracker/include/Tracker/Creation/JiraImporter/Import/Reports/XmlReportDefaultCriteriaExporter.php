<?php
/**
 * Copyright (c) Enalean, 2020 - present. All Rights Reserved.
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

namespace Tuleap\Tracker\Creation\JiraImporter\Import\Reports;

use SimpleXMLElement;
use Tuleap\Tracker\Creation\JiraImporter\Import\Structure\FieldMapping;

class XmlReportDefaultCriteriaExporter
{
    /**
     * @param FieldMapping[] $field_mappings
     */
    public function exportDefaultCriteria(
        array $field_mappings,
        SimpleXMLElement $criterias_node
    ): void {
        $next_rank_in_node = $criterias_node->count();

        foreach ($field_mappings as $field_mapping) {
            $criteria_node = $criterias_node->addChild('criteria');
            $criteria_node->addAttribute("rank", (string) $next_rank_in_node);
            $criteria_field_node = $criteria_node->addChild("field");
            $criteria_field_node->addAttribute("REF", $field_mapping->getXMLId());
            $next_rank_in_node++;
        }
    }
}
