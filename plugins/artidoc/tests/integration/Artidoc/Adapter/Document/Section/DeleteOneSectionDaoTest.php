<?php
/**
 * Copyright (c) Enalean, 2024 - Present. All Rights Reserved.
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

namespace Tuleap\Artidoc\Adapter\Document\Section;

use Tuleap\Artidoc\Adapter\Document\ArtidocDocument;
use Tuleap\Artidoc\Adapter\Document\Section\Freetext\Identifier\UUIDFreetextIdentifierFactory;
use Tuleap\Artidoc\Adapter\Document\Section\Identifier\UUIDSectionIdentifierFactory;
use Tuleap\Artidoc\Domain\Document\ArtidocWithContext;
use Tuleap\Artidoc\Domain\Document\Section\ContentToInsert;
use Tuleap\Test\PHPUnit\TestIntegrationTestCase;

final class DeleteOneSectionDaoTest extends TestIntegrationTestCase
{
    public function testDeleteSectionsById(): void
    {
        $save_dao = new SaveSectionDao(
            new UUIDSectionIdentifierFactory(new \Tuleap\DB\DatabaseUUIDV7Factory()),
            new UUIDFreetextIdentifierFactory(new \Tuleap\DB\DatabaseUUIDV7Factory()),
        );

        $delete_dao = new DeleteOneSectionDao();

        $artidoc = new ArtidocWithContext(new ArtidocDocument(['item_id' => 101]));

        $uuid_1 = $save_dao->saveSectionAtTheEnd(
            $artidoc,
            ContentToInsert::fromArtifactId(1001),
        );
        $uuid_2 = $save_dao->saveSectionAtTheEnd(
            $artidoc,
            ContentToInsert::fromArtifactId(1002),
        );
        $uuid_3 = $save_dao->saveSectionAtTheEnd(
            $artidoc,
            ContentToInsert::fromArtifactId(1003),
        );

        $delete_dao->deleteSectionById($uuid_2);

        SectionsAsserter::assertSectionsForDocument($artidoc, [1001, 1003]);
    }
}
