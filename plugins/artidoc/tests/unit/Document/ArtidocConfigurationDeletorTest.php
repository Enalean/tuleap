<?php
/**
 * Copyright (c) Enalean, 2025 - present. All Rights Reserved.
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

namespace Tuleap\Artidoc\Document;

use Tuleap\Artidoc\Adapter\Document\ArtidocDocument;
use Tuleap\Artidoc\Stubs\Document\DeleteConfiguredTrackerStub;
use Tuleap\Artidoc\Stubs\Document\Field\DeleteDocumentConfiguredFieldsStub;
use Tuleap\Test\DB\DBTransactionExecutorPassthrough;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
class ArtidocConfigurationDeletorTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testItDeletesTheConfiguredTrackerAndFields(): void
    {
        $artidoc_id = 220;
        $document   = new ArtidocDocument(['item_id' => $artidoc_id]);

        $were_fields_deleted = false;
        $was_tracker_deleted = false;

        $delete_configured_tracker = DeleteConfiguredTrackerStub::withCallback(static function (int $item_id) use ($artidoc_id, &$was_tracker_deleted) {
            self::assertEquals($artidoc_id, $item_id);
            $was_tracker_deleted = true;
        });
        $delete_configured_fields  = DeleteDocumentConfiguredFieldsStub::withCallback(static function (int $item_id) use ($artidoc_id, &$were_fields_deleted) {
            self::assertEquals($artidoc_id, $item_id);
            $were_fields_deleted = true;
        });

        $deletor = new ArtidocConfigurationDeletor(
            new DBTransactionExecutorPassthrough(),
            $delete_configured_tracker,
            $delete_configured_fields,
        );

        $deletor->deleteArtidocConfiguration($document);

        $this->assertTrue($was_tracker_deleted);
        $this->assertTrue($were_fields_deleted);
    }
}
