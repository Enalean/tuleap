<?php
/**
 * Copyright (c) Enalean, 2018-Present. All Rights Reserved.
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

namespace Tuleap\Cardwall\Semantic;

use Cardwall_Semantic_CardFields;
use PHPUnit\Framework\MockObject\MockObject;
use SimpleXMLElement;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Test\Builders\Fields\SelectboxFieldBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use Tuleap\Tracker\Tracker;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class CardFieldXmlExporterTest extends TestCase
{
    private Tracker $tracker;
    private SimpleXMLElement $xml_tree;
    private CardFieldXmlExporter $exporter;
    private BackgroundColorDao&MockObject $color_dao;

    #[\Override]
    public function setUp(): void
    {
        $this->color_dao = $this->createMock(BackgroundColorDao::class);
        $this->exporter  = new CardFieldXmlExporter($this->color_dao);
        $this->xml_tree  = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><projects />');
        $this->tracker   = TrackerTestBuilder::aTracker()->build();
    }

    public function testItShouldExportCardFields(): void
    {
        $mapping = [
            'F102' => 13,
            'F103' => 14,
        ];

        $severity_field = SelectboxFieldBuilder::aSelectboxField(13)->build();
        $status_field   = SelectboxFieldBuilder::aSelectboxField(14)->build();

        $fields = [$severity_field, $status_field];

        $semantic = $this->createMock(Cardwall_Semantic_CardFields::class);
        $semantic->method('getFields')->willReturn($fields);
        $semantic->method('getTracker')->willReturn($this->tracker);
        $this->color_dao->method('searchBackgroundColor');

        $this->exporter->exportToXml($this->xml_tree, $mapping, $semantic);

        $semantic = $this->xml_tree->semantic->attributes();
        self::assertEquals(Cardwall_Semantic_CardFields::NAME, $semantic->type);

        $fields = $this->xml_tree->semantic->field;
        self::assertEquals('F102', $fields[0]->attributes());
        self::assertEquals('F103', $fields[1]->attributes());
    }

    public function testItShouldExportBackgroundColor(): void
    {
        $this->color_dao->method('searchBackgroundColor')->willReturn(13);

        $mapping = [
            'F102' => 13,
            'F103' => 14,
        ];

        $semantic = $this->createMock(Cardwall_Semantic_CardFields::class);
        $semantic->method('getFields')->willReturn([]);
        $semantic->method('getTracker')->willReturn($this->tracker);

        $this->exporter->exportToXml($this->xml_tree, $mapping, $semantic);

        $semantic = $this->xml_tree->semantic->attributes();
        self::assertEquals(Cardwall_Semantic_CardFields::NAME, $semantic->type);

        $background_color_field = $this->xml_tree->semantic->{'background-color'};
        self::assertEquals('F102', $background_color_field[0]->attributes());
    }
}
