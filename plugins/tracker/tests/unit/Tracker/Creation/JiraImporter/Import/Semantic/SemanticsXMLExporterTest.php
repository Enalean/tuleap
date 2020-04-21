<?php
/**
 * Copyright (c) Enalean, 2020 - present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
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

namespace Tuleap\unit\Tracker\Creation\JiraImporter\Import\Semantic;

use PHPUnit\Framework\TestCase;
use SimpleXMLElement;
use Tuleap\GlobalLanguageMock;
use Tuleap\Tracker\Creation\JiraImporter\Import\Semantic\SemanticsXMLExporter;
use Tuleap\Tracker\Creation\JiraImporter\Import\Structure\FieldMapping;
use Tuleap\Tracker\Creation\JiraImporter\Import\Structure\FieldMappingCollection;

class SemanticsXMLExporterTest extends TestCase
{
    use GlobalLanguageMock;

    public function testExportsTheSemanticTitle(): void
    {
        $tracker_node = new SimpleXMLElement('<tracker/>');
        $mapping = new FieldMappingCollection();
        $mapping->addMapping(
            new FieldMapping(
                'summary',
                'Fsummary',
                'summary'
            )
        );

        $exporter = new SemanticsXMLExporter();
        $exporter->exportSemantics(
            $tracker_node,
            $mapping
        );

        $this->assertNotNull($tracker_node->semantics);
        $this->assertNotNull($tracker_node->semantics->semantic);

        $semantic_node = $tracker_node->semantics->semantic;
        $this->assertSame("title", (string) $semantic_node['type']);
        $this->assertSame("Fsummary", (string) $semantic_node->field['REF']);
    }

    public function testItDoesNotExportSemanticTitleIfSummaryFieldNotfoundInMapping(): void
    {
        $tracker_node = new SimpleXMLElement('<tracker/>');
        $mapping = new FieldMappingCollection();

        $exporter = new SemanticsXMLExporter();
        $exporter->exportSemantics(
            $tracker_node,
            $mapping
        );

        $this->assertNotNull($tracker_node->semantics);
        $this->assertNotNull($tracker_node->semantics->semantic);
    }
}
