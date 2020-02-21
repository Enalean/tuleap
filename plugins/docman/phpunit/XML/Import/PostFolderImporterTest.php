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
use SimpleXMLElement;

class PostFolderImporterTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testPostImport(): void
    {
        $node = new SimpleXMLElement(
            <<<EOS
            <?xml version="1.0" encoding="UTF-8"?>
            <item type="folder">
                <item type="empty" />
                <item type="wiki" />
            </item>
            EOS
        );

        $node_importer = Mockery::mock(NodeImporter::class);
        $item          = Mockery::mock(Docman_Item::class);

        $node_importer
            ->shouldReceive('import')
            ->with(
                Mockery::on(static function (SimpleXMLElement $node): bool {
                    return (string) $node['type'] === 'empty';
                }),
                $item,
            )->once()
            ->ordered();
        $node_importer
            ->shouldReceive('import')
            ->with(
                Mockery::on(static function (SimpleXMLElement $node): bool {
                    return (string) $node['type'] === 'wiki';
                }),
                $item,
            )->once()
            ->ordered();

        $importer = new PostFolderImporter();
        $importer->postImport($node_importer, $node, $item);
    }
}
