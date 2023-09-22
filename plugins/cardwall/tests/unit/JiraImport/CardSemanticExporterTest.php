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

use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Creation\JiraImporter\Import\Structure\FieldMappingCollection;
use Tuleap\Tracker\Creation\JiraImporter\Import\Structure\ListFieldMapping;

final class CardSemanticExporterTest extends TestCase
{
    public function testItExportsCardSemantic(): void
    {
        $field_mapping_collection = new FieldMappingCollection();
        $field_mapping_collection->addMapping(
            new ListFieldMapping(
                'assignee',
                'Assignee',
                null,
                'Fassignee',
                'assignee',
                \Tracker_FormElementFactory::FIELD_SELECT_BOX_TYPE,
                \Tracker_FormElement_Field_List_Bind_Users::TYPE,
                [],
            )
        );

        $semantics_node = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><tracker><semantics/></tracker>');

        (new CardSemanticExporter())->exportCardSemantic($semantics_node, $field_mapping_collection);

        self::assertNotNull($semantics_node->semantic);
        self::assertCount(1, $semantics_node->semantic);

        $semantic_card_node = $semantics_node->semantic[0];
        self::assertSame("plugin_cardwall_card_fields", (string) $semantic_card_node['type']);
        self::assertSame("Fassignee", (string) $semantic_card_node->field['REF']);
    }
}
