<?php
/**
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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

namespace Tuleap\Tracker\FormElement\Field\ListFields\Bind;

use PHPUnit\Framework\TestCase;
use SimpleXMLElement;

final class BindDecoratorExporterTest extends TestCase
{
    /**
     * @var BindDecoratorExporter
     */
    private $decorator_exporter;

    protected function setUp(): void
    {
        $this->decorator_exporter = new BindDecoratorExporter();
    }

    public function testItExportOldPaletteColor(): void
    {
        $root = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><project />');
        $this->decorator_exporter->exportToXml($root, 'val', true, '255', '255', '255', null);

        $attr = $root->decorator->attributes();
        $this->assertEquals("255", (string) $attr->r);
        $this->assertEquals("255", (string) $attr->g);
        $this->assertEquals("255", (string) $attr->b);
        $this->assertEquals("val", (string) $attr->REF);
    }

    public function testItExportOldPaletteColorIfAColorIsEqualToZero(): void
    {
        $root = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><project />');
        $this->decorator_exporter->exportToXml($root, 'val', true, '0', '255', '255', null);

        $attr = $root->decorator->attributes();
        $this->assertEquals("0", (string) $attr->r);
        $this->assertEquals("255", (string) $attr->g);
        $this->assertEquals("255", (string) $attr->b);
        $this->assertEquals("val", (string) $attr->REF);
    }

    public function testItDoesNotExportOldPaletteColorIfAColorIsNull(): void
    {
        $root = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><project />');
        $this->decorator_exporter->exportToXml($root, 'val', true, null, '255', '255', null);

        $this->assertCount(1, $root->decorator->attributes());
    }

    public function testitExportTlpColor(): void
    {
        $root = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><project />');
        $this->decorator_exporter->exportToXml($root, 'val', false, null, null, null, 'inca-silver');

        $attr = $root->decorator->attributes();
        $this->assertEquals('inca-silver', (string) $attr->tlp_color_name);
        $this->assertEquals("val", (string) $attr->REF);
    }

    public function testItExportNoneOldPaletteColor(): void
    {
        $root = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><project />');
        $this->decorator_exporter->exportNoneToXml($root, true, '255', '255', '255', null);

        $attr = $root->decorator->attributes();
        $this->assertEquals("255", (string) $attr->r);
        $this->assertEquals("255", (string) $attr->g);
        $this->assertEquals("255", (string) $attr->b);
        $this->assertNull($attr->REF);
    }

    public function testitExportNoneTlpColor(): void
    {
        $root = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><project />');
        $this->decorator_exporter->exportNoneToXml($root, false, null, null, null, 'inca-silver');

        $attr = $root->decorator->attributes();
        $this->assertEquals('inca-silver', (string) $attr->tlp_color_name);
        $this->assertNull($attr->REF);
    }
}
