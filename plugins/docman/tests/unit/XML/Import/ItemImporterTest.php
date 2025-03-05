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

use DateTimeImmutable;
use Docman_Item;
use Docman_ItemFactory;
use SimpleXMLElement;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class ItemImporterTest extends TestCase
{
    public function testImport(): void
    {
        $permission_importer = $this->createMock(PermissionsImporter::class);
        $item_factory        = $this->createMock(Docman_ItemFactory::class);

        $node          = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><item/>');
        $node_importer = $this->createMock(NodeImporter::class);
        $post_importer = new PostDoNothingImporter();
        $parent_item   = new Docman_Item(['item_id' => 13]);
        $user          = UserTestBuilder::buildWithId(101);

        $create_date = (new DateTimeImmutable())->setTimestamp(1234567890);
        $update_date = (new DateTimeImmutable())->setTimestamp(1324567890);
        $properties  = ImportProperties::buildLink('My document', 'The description', 'https://example.test', $create_date, $update_date, $user);

        $created_item = new Docman_Item(['item_id' => 14]);

        $item_factory->expects(self::once())->method('createWithoutOrdering')
            ->with('My document', 'The description', 13, 100, 0, 101, 3, '', $create_date, $update_date, null, 'https://example.test')
            ->willReturn($created_item);

        $permission_importer->expects(self::once())->method('importPermissions')->with($parent_item, $created_item, $node);

        $importer = new ItemImporter($permission_importer, $item_factory);
        $importer->import($node, $node_importer, $post_importer, $parent_item, $properties);
    }
}
