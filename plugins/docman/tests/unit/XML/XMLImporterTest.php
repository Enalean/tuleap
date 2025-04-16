<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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

namespace Tuleap\Docman\XML;

use ColinODell\PsrTestLogger\TestLogger;
use Docman_Item;
use Docman_ItemFactory;
use Psr\Log\NullLogger;
use SimpleXMLElement;
use Tuleap\Docman\XML\Import\NodeImporter;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\XML\ParseExceptionWithErrors;
use XML_ParseException;
use XML_RNGValidator;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class XMLImporterTest extends TestCase
{
    public function testImport(): void
    {
        $item_factory  = $this->createMock(Docman_ItemFactory::class);
        $project       = ProjectTestBuilder::aProject()->withId(113)->build();
        $node_importer = $this->createMock(NodeImporter::class);
        $rng_validator = $this->createMock(XML_RNGValidator::class);

        $node = new SimpleXMLElement(
            <<<EOS
            <?xml version="1.0" encoding="UTF-8"?>
            <docman>
                <item type="folder">
                    <item type="wiki"/>
                    <item type="file"/>
                </item>
            </docman>
            EOS
        );

        $rng_validator->expects($this->once())->method('validate');

        $parent_item = new Docman_Item();
        $item_factory->expects($this->once())->method('getRoot')->with(113)->willReturn($parent_item);

        $node_importer->expects($this->exactly(2))->method('import')
            ->with(
                self::callback(static fn(SimpleXMLElement $node) => (string) $node['type'] === 'wiki' || (string) $node['type'] === 'file'),
                $parent_item
            );

        $importer = new XMLImporter($item_factory, $project, new NullLogger(), $node_importer, $rng_validator);
        $importer->import($node);
    }

    public function testItDoesNotImportWhenThereIsNoRoot(): void
    {
        $item_factory  = $this->createMock(Docman_ItemFactory::class);
        $project       = ProjectTestBuilder::aProject()->withId(113)->build();
        $logger        = new TestLogger();
        $node_importer = $this->createMock(NodeImporter::class);
        $rng_validator = $this->createMock(XML_RNGValidator::class);

        $node = new SimpleXMLElement(
            <<<EOS
            <?xml version="1.0" encoding="UTF-8"?>
            <docman>
                <item type="folder">
                    <item type="wiki"/>
                    <item type="file"/>
                </item>
            </docman>
            EOS
        );

        $rng_validator->expects($this->once())->method('validate');

        $item_factory->expects($this->once())->method('getRoot')->with(113)->willReturn(null);
        $node_importer->expects($this->never())->method('import');

        $importer = new XMLImporter($item_factory, $project, $logger, $node_importer, $rng_validator);
        $importer->import($node);
        self::assertTrue($logger->hasError('Unable to find a root element in project #113'));
    }

    public function testItRaisesParseExceptionWhenXMLIsInvalid(): void
    {
        $item_factory  = $this->createMock(Docman_ItemFactory::class);
        $project       = ProjectTestBuilder::aProject()->withId(113)->build();
        $node_importer = $this->createMock(NodeImporter::class);
        $rng_validator = $this->createMock(XML_RNGValidator::class);

        $node = new SimpleXMLElement(
            <<<EOS
            <?xml version="1.0" encoding="UTF-8"?>
            <docman>
                <item type="empty" />
            </docman>
            EOS
        );

        $rng_validator->expects($this->once())->method('validate')
            ->willThrowException(new ParseExceptionWithErrors('', [], []));

        self::expectException(XML_ParseException::class);
        $importer = new XMLImporter($item_factory, $project, new NullLogger(), $node_importer, $rng_validator);
        $importer->import($node);
    }
}
