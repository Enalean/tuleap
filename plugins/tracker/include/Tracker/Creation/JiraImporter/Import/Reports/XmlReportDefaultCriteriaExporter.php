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
use Tuleap\Tracker\FormElement\XML\XMLFormElementFlattenedCollection;
use Tuleap\Tracker\FormElement\XML\XMLReferenceByID;
use Tuleap\Tracker\Report\XML\XMLReportCriterion;

class XmlReportDefaultCriteriaExporter
{
    /**
     * @param FieldMapping[] $field_mappings
     */
    public function exportDefaultCriteria(
        array $field_mappings,
        SimpleXMLElement $criterias_node,
    ): void {
        $next_rank_in_node = $criterias_node->count();

        foreach ($field_mappings as $field_mapping) {
            (new XMLReportCriterion(new XMLReferenceByID($field_mapping->getXMLId())))
                ->withRank($next_rank_in_node)
                ->export($criterias_node, new XMLFormElementFlattenedCollection([]));
            $next_rank_in_node++;
        }
    }
}
