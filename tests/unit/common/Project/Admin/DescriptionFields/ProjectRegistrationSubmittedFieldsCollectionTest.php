<?php
/**
 * Copyright (c) Enalean, 2021 - present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
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

declare(strict_types=1);

namespace Tuleap\Project\Admin\DescriptionFields;

use Tuleap\Project\REST\v1\FieldsPostRepresentation;
use Tuleap\Project\REST\v1\ProjectPostRepresentation;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class ProjectRegistrationSubmittedFieldsCollectionTest extends TestCase
{
    public function testItBuildsACollectionFromProjectPOSTRepresentation(): void
    {
        $representation         = ProjectPostRepresentation::build(101);
        $representation->fields = [
            FieldsPostRepresentation::build(
                1,
                'field 01'
            ),
            FieldsPostRepresentation::build(
                2,
                'field 02'
            ),
        ];

        $collection = ProjectRegistrationSubmittedFieldsCollection::buildFromRESTProjectCreation($representation);

        self::assertNotEmpty($collection->getSubmittedFields());
        self::assertCount(2, $collection->getSubmittedFields());

        self::assertSame(1, $collection->getSubmittedFields()[0]->getFieldId());
        self::assertSame('field 01', $collection->getSubmittedFields()[0]->getFieldValue());

        self::assertSame(2, $collection->getSubmittedFields()[1]->getFieldId());
        self::assertSame('field 02', $collection->getSubmittedFields()[1]->getFieldValue());
    }

    public function testItBuildsACollectionFromArray(): void
    {
        $collection = ProjectRegistrationSubmittedFieldsCollection::buildFromArray([
            1 => 'field 01',
            2 => 'field 02',
        ]);

        self::assertNotEmpty($collection->getSubmittedFields());
        self::assertCount(2, $collection->getSubmittedFields());

        self::assertSame(1, $collection->getSubmittedFields()[0]->getFieldId());
        self::assertSame('field 01', $collection->getSubmittedFields()[0]->getFieldValue());

        self::assertSame(2, $collection->getSubmittedFields()[1]->getFieldId());
        self::assertSame('field 02', $collection->getSubmittedFields()[1]->getFieldValue());
    }
}
