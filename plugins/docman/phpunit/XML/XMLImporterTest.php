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

use Docman_Item;
use Docman_ItemFactory;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Project;
use Psr\Log\LoggerInterface;
use SimpleXMLElement;
use Tuleap\Docman\XML\Import\NodeImporter;
use XML_RNGValidator;

class XMLImporterTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testImport(): void
    {
        $item_factory  = Mockery::mock(Docman_ItemFactory::class);
        $project       = Mockery::mock(Project::class)->shouldReceive(['getGroupId' => 113])->getMock();
        $logger        = Mockery::mock(LoggerInterface::class);
        $node_importer = Mockery::mock(NodeImporter::class);
        $rng_validator = Mockery::mock(XML_RNGValidator::class);

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

        $rng_validator
            ->shouldReceive('validate')
            ->once();

        $parent_item = Mockery::mock(Docman_Item::class);
        $item_factory->shouldReceive('getRoot')->with(113)->once()->andReturn($parent_item);

        $node_importer
            ->shouldReceive('import')
            ->with(
                Mockery::on(
                    static function (SimpleXMLElement $node): bool {
                        return (string) $node['type'] === 'wiki';
                    }
                ),
                $parent_item
            )->once();
        $node_importer
            ->shouldReceive('import')
            ->with(
                Mockery::on(
                    static function (SimpleXMLElement $node): bool {
                        return (string) $node['type'] === 'file';
                    }
                ),
                $parent_item
            )->once();

        $importer = new XMLImporter($item_factory, $project, $logger, $node_importer, $rng_validator);
        $importer->import($node);
    }

    public function testItDoesNotImportWhenThereIsNoRoot(): void
    {
        $item_factory  = Mockery::mock(Docman_ItemFactory::class);
        $project       = Mockery::mock(Project::class)->shouldReceive(['getGroupId' => 113])->getMock();
        $logger        = Mockery::mock(LoggerInterface::class);
        $node_importer = Mockery::mock(NodeImporter::class);
        $rng_validator = Mockery::mock(XML_RNGValidator::class);

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

        $rng_validator
            ->shouldReceive('validate')
            ->once();

        $item_factory->shouldReceive('getRoot')->with(113)->once()->andReturnNull();
        $node_importer->shouldReceive('import')->never();
        $logger->shouldReceive('error')->with('Unable to find a root element in project #113')->once();

        $importer = new XMLImporter($item_factory, $project, $logger, $node_importer, $rng_validator);
        $importer->import($node);
    }

    public function testItRaisesParseExceptionWhenXMLIsInvalid(): void
    {
        $item_factory  = Mockery::mock(Docman_ItemFactory::class);
        $project       = Mockery::mock(Project::class)->shouldReceive(['getGroupId' => 113])->getMock();
        $logger        = Mockery::mock(LoggerInterface::class);
        $node_importer = Mockery::mock(NodeImporter::class);
        $rng_validator = Mockery::mock(XML_RNGValidator::class);

        $node = new SimpleXMLElement(
            <<<EOS
            <?xml version="1.0" encoding="UTF-8"?>
            <docman>
                <item type="empty" />
            </docman>
            EOS
        );

        $rng_validator
            ->shouldReceive('validate')
            ->once()
            ->andThrow(Mockery::mock(\XML_ParseException::class));

        $this->expectException(\XML_ParseException::class);
        $importer = new XMLImporter($item_factory, $project, $logger, $node_importer, $rng_validator);
        $importer->import($node);
    }
}
