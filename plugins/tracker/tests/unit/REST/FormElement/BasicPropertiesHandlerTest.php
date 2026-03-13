<?php
/**
 * Copyright (c) Enalean, 2026 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\REST\FormElement;

use Luracast\Restler\RestException;
use Tuleap\REST\v1\TrackerFieldRepresentations\TrackerFieldPatchRepresentation;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\FormElement\Field\FieldDao;
use Tuleap\Tracker\Test\Builders\Fields\StringFieldBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class BasicPropertiesHandlerTest extends TestCase
{
    public function testItDoesNothingIfNewLabelIsNotPartOfPatch(): void
    {
        $field = StringFieldBuilder::aStringField(1)->withLabel('Summary')->build();
        $patch = new TrackerFieldPatchRepresentation(null, [], null, null);

        $dao = $this->createMock(FieldDao::class);
        $dao->expects($this->never())->method('save');

        $handler = new BasicPropertiesHandler($dao);
        $handler->handle($field, $patch, UserTestBuilder::buildWithDefaults());

        self::assertSame('Summary', $field->getLabel());
    }

    public function testItRaisesAnExceptionIfLabelIsEmpty(): void
    {
        $field = StringFieldBuilder::aStringField(1)->withLabel('Summary')->build();
        $patch = new TrackerFieldPatchRepresentation('   ', [], null, null);

        $dao = $this->createMock(FieldDao::class);
        $dao->expects($this->never())->method('save');

        $this->expectException(RestException::class);
        $this->expectExceptionCode(400);

        $handler = new BasicPropertiesHandler($dao);
        $handler->handle($field, $patch, UserTestBuilder::buildWithDefaults());
    }

    public function testItUpdatesTheLabel(): void
    {
        $field = StringFieldBuilder::aStringField(1)->withLabel('Summary')->build();
        $patch = new TrackerFieldPatchRepresentation('New label', [], null, null);

        $dao = $this->createMock(FieldDao::class);
        $dao->expects($this->once())->method('save');

        $handler = new BasicPropertiesHandler($dao);
        $handler->handle($field, $patch, UserTestBuilder::buildWithDefaults());

        self::assertSame('New label', $field->getLabel());
    }
}
