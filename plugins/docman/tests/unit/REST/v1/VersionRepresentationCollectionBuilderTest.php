<?php
/**
 * Copyright (c) Enalean 2022 -  Present. All Rights Reserved.
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
 *
 */

namespace Tuleap\Docman\REST\v1;

use Tuleap\Docman\Item\PaginatedFileVersionRepresentationCollection;
use Tuleap\Docman\REST\v1\Files\FileVersionRepresentation;
use Tuleap\Docman\Version\VersionDao;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;

final class VersionRepresentationCollectionBuilderTest extends TestCase
{
    private VersionDao|\PHPUnit\Framework\MockObject\MockObject $docman_version_dao;
    private VersionRepresentationCollectionBuilder $builder;

    protected function setUp(): void
    {
        $this->docman_version_dao = $this->createMock(VersionDao::class);

        $this->builder = new VersionRepresentationCollectionBuilder($this->docman_version_dao);
    }

    public function testItBuildAVersionsRepresentation(): void
    {
        $user = UserTestBuilder::buildWithDefaults();

        $dar_item = [
            'item_id' => 4,
            'title' => 'item',
            'user_id' => 101,
            'update_date' => 1542099693,
            'item_type' => PLUGIN_DOCMAN_ITEM_TYPE_FILE,
            'parent_id' => 100,
            'group_id' => 10,
        ];
        $item     = new \Docman_File($dar_item);

        $dar = [
            'item_id' => 4,
            'number' => 1,
            'label' => "my version label",
            'filename' => "a_file.txt",
        ];
        $this->docman_version_dao->method('searchByItemId')->willReturn([$dar]);
        $this->docman_version_dao->method('countByItemId')->willReturn(1);

        $representation = $this->builder->buildVersionsCollection($item, 50, 0);

        $expected_representation = new PaginatedFileVersionRepresentationCollection(
            [FileVersionRepresentation::build(1, "my version label", "a_file.txt", 10, 4)],
            1
        );

        $this->assertEquals($expected_representation, $representation);
    }
}
