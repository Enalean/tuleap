<?php
/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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

namespace Tuleap\Tools\Xml2Php\Tracker\Report;

use PhpParser\PrettyPrinter;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tools\Xml2Php\Tracker\FormElement\IdToNameMapping;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
class ReportConvertorTest extends TestCase
{
    public function testItBuildsABasicReport(): void
    {
        $xml = simplexml_load_string(<<<EOS
            <?xml version="1.0" encoding="UTF-8"?>
            <report>
                <name>My issues</name>
                <criterias />
                <renderers />
            </report>
            EOS
        );

        $id_to_name_mapping = new IdToNameMapping();

        $convertor = new ReportConvertor();
        $node      = $convertor->buildFromXml($xml, new \Psr\Log\NullLogger(), $id_to_name_mapping);

        $printer = new PrettyPrinter\Standard();
        self::assertEquals(
            'new \Tuleap\Tracker\Report\XML\XMLReport(\'My issues\')',
            $printer->prettyPrint([$node])
        );
    }

    public function testItBuildsADefaultReport(): void
    {
        $xml = simplexml_load_string(<<<EOS
            <?xml version="1.0" encoding="UTF-8"?>
            <report is_default="1">
                <name>My issues</name>
                <criterias />
                <renderers />
            </report>
            EOS
        );

        $id_to_name_mapping = new IdToNameMapping();

        $convertor = new ReportConvertor();
        $node      = $convertor->buildFromXml($xml, new \Psr\Log\NullLogger(), $id_to_name_mapping);

        $printer = new PrettyPrinter\Standard();
        self::assertEquals(
            '(new \Tuleap\Tracker\Report\XML\XMLReport(\'My issues\'))->withIsDefault(true)',
            $printer->prettyPrint([$node])
        );
    }

    public function testItBuildsAReportWithDescription(): void
    {
        $xml = simplexml_load_string(<<<EOS
            <?xml version="1.0" encoding="UTF-8"?>
            <report>
                <name>My issues</name>
                <description>The description</description>
                <criterias />
                <renderers />
            </report>
            EOS
        );

        $id_to_name_mapping = new IdToNameMapping();

        $convertor = new ReportConvertor();
        $node      = $convertor->buildFromXml($xml, new \Psr\Log\NullLogger(), $id_to_name_mapping);

        $printer = new PrettyPrinter\Standard();
        self::assertEquals(
            '(new \Tuleap\Tracker\Report\XML\XMLReport(\'My issues\'))->withDescription(\'The description\')',
            $printer->prettyPrint([$node])
        );
    }

    public function testItBuildsAReportInExpertMode(): void
    {
        $xml = simplexml_load_string(<<<EOS
            <?xml version="1.0" encoding="UTF-8"?>
            <report is_in_expert_mode="1" expert_query="assigned_to = MYSELF()">
                <name>My issues</name>
                <criterias />
                <renderers />
            </report>
            EOS
        );

        $id_to_name_mapping = new IdToNameMapping();

        $convertor = new ReportConvertor();
        $node      = $convertor->buildFromXml($xml, new \Psr\Log\NullLogger(), $id_to_name_mapping);

        $printer = new PrettyPrinter\Standard();
        self::assertEquals(
            '(new \Tuleap\Tracker\Report\XML\XMLReport(\'My issues\'))->withExpertMode()->withExpertQuery(\'assigned_to = MYSELF()\')',
            $printer->prettyPrint([$node])
        );
    }
}
