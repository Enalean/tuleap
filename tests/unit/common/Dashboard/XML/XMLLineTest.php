<?php
/**
 * Copyright (c) Enalean, 2022 - Present. All Rights Reserved.
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

namespace Tuleap\Dashboard\XML;

use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Widget\XML\XMLWidget;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
class XMLLineTest extends TestCase
{
    public function testItExportsNothingIfNoColumns(): void
    {
        $line = XMLLine::withDefaultLayout();

        $xml = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><dashboard/>');

        $line->export($xml);

        self::assertCount(0, $xml->line);
    }

    public function testWithDefaultLayout(): void
    {
        $line = (XMLLine::withDefaultLayout())
            ->withColumn((new XMLColumn())
                ->withWidget(new XMLWidget('projectmembers'))
                ->withWidget(new XMLWidget('projectcontacts')));

        $xml = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><dashboard/>');

        $line->export($xml);

        self::assertCount(1, $xml->line);
        self::assertNull($xml->line['layout']);
        self::assertCount(1, $xml->line->column);
        self::assertCount(2, $xml->line->column->widget);
        self::assertEquals('projectmembers', (string) $xml->line->column->widget[0]['name']);
        self::assertEquals('projectcontacts', (string) $xml->line->column->widget[1]['name']);
    }

    public function testWithGivenLayout(): void
    {
        $line = (XMLLine::withLayout('two-columns-big-small'))
            ->withColumn((new XMLColumn())
                ->withWidget(new XMLWidget('projectmembers')));

        $xml = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><dashboard/>');

        $line->export($xml);

        self::assertEquals('two-columns-big-small', (string) $xml->line['layout']);
    }
}
