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

namespace Tuleap\Docman\XML\Import;

use Docman_Item;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use SimpleXMLElement;

class PostFileImporterTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testPostImport(): void
    {
        $node = new SimpleXMLElement(
            <<<EOS
            <?xml version="1.0" encoding="UTF-8"?>
            <item type="file">
                <versions>
                    <version><filename>image.png</filename></version>
                    <version><filename>file.txt</filename></version>
                </versions>
            </item>
            EOS
        );

        $node_importer = Mockery::mock(NodeImporter::class);
        $item          = Mockery::mock(Docman_Item::class);

        $version_importer = Mockery::mock(VersionImporter::class);
        $logger           = Mockery::mock(LoggerInterface::class);

        $logger->shouldReceive('debug')->with("└ Importing version #1")->once();
        $version_importer
            ->shouldReceive('import')
            ->with(
                Mockery::on(
                    static function (SimpleXMLElement $node): bool {
                        return (string) $node->filename === 'image.png';
                    }
                ),
                $item,
                1
            )->once()
            ->ordered();
        $logger->shouldReceive('debug')->with("└ Importing version #2")->once();
        $version_importer
            ->shouldReceive('import')
            ->with(
                Mockery::on(
                    static function (SimpleXMLElement $node): bool {
                        return (string) $node->filename === 'file.txt';
                    }
                ),
                $item,
                2
            )->once()
            ->ordered();

        $importer = new PostFileImporter($version_importer, $logger);
        $importer->postImport($node_importer, $node, $item);
    }
}
