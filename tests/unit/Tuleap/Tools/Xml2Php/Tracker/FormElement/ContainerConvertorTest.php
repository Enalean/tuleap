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

namespace Tuleap\Tools\Xml2Php\Tracker\FormElement;

use PhpParser\PrettyPrinter;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
class ContainerConvertorTest extends TestCase
{
    public function testItBuildsABasicContainer(): void
    {
        $xml = simplexml_load_string(<<<EOS
            <?xml version="1.0" encoding="UTF-8"?>
            <tracker>
                <formElement type="column" ID="F123">
                    <name>Col1</name>
                </formElement>
            </tracker>
            EOS
        );

        $id_to_name_mapping = new IdToNameMapping();

        $node = (new ContainerConvertor($xml->formElement, $xml, 'XMLColumn'))
            ->get(new \Psr\Log\NullLogger(), $id_to_name_mapping);

        $printer = new PrettyPrinter\Standard();
        self::assertEquals(
            '\XMLColumn::fromTrackerAndName($tracker, \'Col1\')',
            $printer->prettyPrint([$node])
        );
        self::assertEquals('Col1', $id_to_name_mapping->get('F123'));
    }

    public function testItBuildsAContainerWithLabel(): void
    {
        $xml = simplexml_load_string(<<<EOS
            <?xml version="1.0" encoding="UTF-8"?>
            <tracker>
                <formElement type="column" ID="F123">
                    <name>Col1</name>
                    <label>Plop</label>
                </formElement>
            </tracker>
            EOS
        );

        $id_to_name_mapping = new IdToNameMapping();

        $node = (new ContainerConvertor($xml->formElement, $xml, 'XMLColumn'))
            ->get(new \Psr\Log\NullLogger(), $id_to_name_mapping);

        $printer = new PrettyPrinter\Standard();
        self::assertEquals(
            '\XMLColumn::fromTrackerAndName($tracker, \'Col1\')->withLabel(\'Plop\')',
            $printer->prettyPrint([$node])
        );
        self::assertEquals('Col1', $id_to_name_mapping->get('F123'));
    }

    public function testItBuildsAContainerWithRank(): void
    {
        $xml = simplexml_load_string(<<<EOS
            <?xml version="1.0" encoding="UTF-8"?>
            <tracker>
                <formElement type="column" ID="F123" rank="2">
                    <name>Col1</name>
                </formElement>
            </tracker>
            EOS
        );

        $id_to_name_mapping = new IdToNameMapping();

        $node = (new ContainerConvertor($xml->formElement, $xml, 'XMLColumn'))
            ->get(new \Psr\Log\NullLogger(), $id_to_name_mapping);

        $printer = new PrettyPrinter\Standard();
        self::assertEquals(
            '\XMLColumn::fromTrackerAndName($tracker, \'Col1\')->withRank(2)',
            $printer->prettyPrint([$node])
        );
        self::assertEquals('Col1', $id_to_name_mapping->get('F123'));
    }

    public function testItBuildsAContainerWithChidren(): void
    {
        $xml = simplexml_load_string(<<<EOS
            <?xml version="1.0" encoding="UTF-8"?>
            <tracker>
                <formElement type="fieldset" ID="F123">
                    <name>f1</name>
                    <formElements>
                        <formElement type="column" ID="F124">
                            <name>Col1</name>
                        </formElement>
                        <formElement type="column" ID="F125">
                            <name>Col2</name>
                        </formElement>
                    </formElements>
                </formElement>
            </tracker>
            EOS
        );

        $id_to_name_mapping = new IdToNameMapping();

        $node = (new ContainerConvertor($xml->formElement, $xml, 'XMLFieldset'))
            ->get(new \Psr\Log\NullLogger(), $id_to_name_mapping);

        $printer = new PrettyPrinter\Standard();
        self::assertEquals(
            '\XMLFieldset::fromTrackerAndName($tracker, \'f1\')->withFormElements(' .
            '\Tuleap\Tracker\FormElement\Container\Column\XML\XMLColumn::fromTrackerAndName($tracker, \'Col1\'), ' .
            '\Tuleap\Tracker\FormElement\Container\Column\XML\XMLColumn::fromTrackerAndName($tracker, \'Col2\'))',
            $printer->prettyPrint([$node])
        );
        self::assertEquals('f1', $id_to_name_mapping->get('F123'));
        self::assertEquals('Col1', $id_to_name_mapping->get('F124'));
        self::assertEquals('Col2', $id_to_name_mapping->get('F125'));
    }
}
