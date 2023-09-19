<?php
/**
 * Copyright (c) Enalean, 2023-Present. All Rights Reserved.
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

namespace Tuleap\Cardwall\JiraImport;

use Cardwall_Semantic_CardFields;
use Tuleap\Tracker\Creation\JiraImporter\Import\AlwaysThereFieldsExporter;
use Tuleap\Tracker\Creation\JiraImporter\Import\Structure\FieldMappingCollection;

final class CardSemanticExporter
{
    public function exportCardSemantic(\SimpleXMLElement $semantics_node, FieldMappingCollection $field_mapping_collection): void
    {
        $assignee_field = $field_mapping_collection->getMappingFromJiraField(AlwaysThereFieldsExporter::JIRA_ASSIGNEE_NAME);
        if ($assignee_field === null) {
            return;
        }

        $semantic_node = $semantics_node->addChild("semantic");
        if ($semantic_node === null) {
            throw new \RuntimeException('This must not happen.');
        }
        $semantic_node->addAttribute("type", Cardwall_Semantic_CardFields::NAME);

        $field_node = $semantic_node->addChild("field");
        if ($field_node === null) {
            throw new \RuntimeException('This must not happen.');
        }
        $field_node->addAttribute("REF", $assignee_field->getXMLId());
    }
}
