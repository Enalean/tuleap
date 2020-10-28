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

use PHPUnit\Framework\TestCase;
use SimpleXMLElement;
use Tracker_FormElementFactory;
use Tuleap\Tracker\Creation\JiraImporter\Import\Structure\FieldMapping;

final class XmlReportDefaultCriteriaExporterTest extends TestCase
{
    public function testItExportsCriteriaWithNoSelectedValues(): void
    {
        $node_criterias = new SimpleXMLElement('
            <criterias>
                <criteria>
                    <field REF="Ffield01"></field>
                </criteria>
            </criterias>
        ');
        $status_field_mapping = new FieldMapping(
            'status',
            'Fstatus',
            'status',
            Tracker_FormElementFactory::FIELD_SELECT_BOX_TYPE,
            \Tracker_FormElement_Field_List_Bind_Static::TYPE
        );

        $priority_field_mapping = new FieldMapping(
            'priority',
            'Fpriority',
            'priority',
            Tracker_FormElementFactory::FIELD_SELECT_BOX_TYPE,
            \Tracker_FormElement_Field_List_Bind_Static::TYPE
        );

        $criteria_exporter = new XmlReportDefaultCriteriaExporter();
        $criteria_exporter->exportDefaultCriteria(
            [$status_field_mapping, $priority_field_mapping],
            $node_criterias
        );

        $this->assertEquals(3, $node_criterias->count());

        $this->assertSame("Fstatus", (string) $node_criterias->criteria[1]->field['REF']);
        $this->assertSame("1", (string) $node_criterias->criteria[1]['rank']);

        $this->assertSame("Fpriority", (string) $node_criterias->criteria[2]->field['REF']);
        $this->assertSame("2", (string) $node_criterias->criteria[2]['rank']);
    }
}
