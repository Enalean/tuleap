<?php
/**
 * Copyright (c) Enalean, 2025-Present. All Rights Reserved.
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

namespace Tuleap\Tracker\FormElement\Field;

use Tracker_FormElementFactory;
use Tuleap\Test\PHPUnit\TestIntegrationTestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class FieldDaoTest extends TestIntegrationTestCase
{
    private const TRACKER_ID = 51;
    private const SCOPE      = 'P';
    private FieldDao $field_dao;

    #[\Override]
    protected function setUp(): void
    {
        $this->field_dao = new FieldDao();
    }

    public function testCRUD(): void
    {
        $retrieved_field_dar_before_create = $this->field_dao->searchById(1);
        self::assertCount(0, $retrieved_field_dar_before_create);

        $type        = Tracker_FormElementFactory::FIELD_INTEGER_TYPE;
        $parent_id   = 379;
        $name        = 'ensculpture_albification';
        $label       = 'Ensculpture Albification';
        $description = 'Quadrifolium xerogel';
        $is_used     = 1;

        $field_id = $this->field_dao->create(
            $type,
            self::TRACKER_ID,
            $parent_id,
            $name,
            'field_',
            $label,
            $description,
            $is_used,
            self::SCOPE,
            false,
            false,
            '2',
            null,
            false
        );

        $retrieved_field_dar_after_create = $this->field_dao->searchById($field_id);
        self::assertCount(1, $retrieved_field_dar_after_create);
        $row = $retrieved_field_dar_after_create->getRow();
        self::assertNotFalse($row);
        self::assertSame((string) $field_id, $row['id']);
        self::assertSame((string) self::TRACKER_ID, $row['tracker_id']);
        self::assertSame((string) $parent_id, $row['parent_id']);
        self::assertSame($type, $row['formElement_type']);
        self::assertSame($name, $row['name']);
        self::assertSame($label, $row['label']);
        self::assertSame($description, $row['description']);
        self::assertSame((string) $is_used, $row['use_it']);
        self::assertSame('0', $row['rank']);
        self::assertSame(self::SCOPE, $row['scope']);
        self::assertSame('0', $row['required']);
        self::assertNull($row['notifications']);
        self::assertSame('0', $row['original_field_id']);

        $field = Tracker_FormElementFactory::instance()->getInstanceFromRow($row);
        $this->field_dao->delete($field);

        $retrieved_field_dar_after_delete = $this->field_dao->searchById($field_id);
        self::assertCount(0, $retrieved_field_dar_after_delete);

        $this->createFieldWithDifferentConditions();
    }

    private function createFieldWithDifferentConditions(): void
    {
        $type      = Tracker_FormElementFactory::FIELD_SELECT_BOX_TYPE;
        $parent_id = 0;
        $name      = 'pantodon_benzopyranyl';
        $label     = 'Pantodon Benzopyranyl';
        $is_used   = 0;
        $rank      = 2;

        $field_id = $this->field_dao->create(
            $type,
            self::TRACKER_ID,
            $parent_id,
            $name,
            'field_',
            $label,
            '',
            $is_used,
            self::SCOPE,
            true,
            true,
            $rank,
            null,
            true
        );

        $dar = $this->field_dao->searchById($field_id);
        self::assertCount(1, $dar);
        $row = $dar->getRow();
        self::assertNotFalse($row);
        self::assertSame((string) $field_id, $row['id']);
        self::assertSame((string) self::TRACKER_ID, $row['tracker_id']);
        self::assertSame((string) $parent_id, $row['parent_id']);
        self::assertSame($type, $row['formElement_type']);
        self::assertSame($name, $row['name']);
        self::assertSame($label, $row['label']);
        self::assertSame('', $row['description']);
        self::assertSame((string) $is_used, $row['use_it']);
        self::assertSame((string) $rank, $row['rank']);
        self::assertSame(self::SCOPE, $row['scope']);
        self::assertSame('1', $row['required']);
        self::assertSame('1', $row['notifications']);
        self::assertSame('0', $row['original_field_id']);
    }
}
