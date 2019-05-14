<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Workflow\SimpleMode;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use SimpleXMLElement;
use Workflow;

class SimpleWorkflowXMLExporterTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testItExportsTheSimpleWorkflowInXML()
    {
        $xml      = new SimpleXMLElement('<simple_workflow/>');
        $exporter = new SimpleWorkflowXMLExporter();
        $workflow = Mockery::mock(Workflow::class);

        $workflow->shouldReceive('getFieldId')->once()->andReturn(114);
        $workflow->shouldReceive('isUsed')->once()->andReturn(true);

        $mapping = [
            'F114' => 114
        ];

        $exporter->exportToXML($workflow, $xml, $mapping);

        $this->assertEquals((string) $xml->field_id['REF'], 'F114');
        $this->assertEquals((string) $xml->is_used, '1');
    }
}
