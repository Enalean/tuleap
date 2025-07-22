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

namespace Tuleap\TestManagement\REST;

use PHPUnit\Framework\MockObject\MockObject;
use Tracker_FormElementFactory;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;
use Tuleap\Tracker\Test\Builders\Fields\IntegerFieldBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class FormattedChangesetValueForIntFieldRetrieverTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private FormattedChangesetValueForIntFieldRetriever $formatted_changeset_value_for_int_field_retriever;
    private Tracker_FormElementFactory&MockObject $tracker_formelement_factory;

    protected function setUp(): void
    {
        $this->tracker_formelement_factory                       = $this->createMock(Tracker_FormElementFactory::class);
        $this->formatted_changeset_value_for_int_field_retriever = new FormattedChangesetValueForIntFieldRetriever(
            $this->tracker_formelement_factory
        );
    }

    public function testGetFormattedChangesetValueForIntFile(): void
    {
        $field = IntegerFieldBuilder::anIntField(112)->build();

        $this->tracker_formelement_factory->method('getUsedFieldByNameForUser')->willReturn($field);

        $result = $this->formatted_changeset_value_for_int_field_retriever
            ->getFormattedChangesetValueForFieldInt(
                'time',
                1234,
                ArtifactTestBuilder::anArtifact(42)->build(),
                UserTestBuilder::buildWithDefaults(),
            );

        $this->assertEquals(1234, $result->value);
        $this->assertEquals(112, $result->field_id);
    }

    public function testGetFormattedChangesetValueForFieldIntReturnsNullIfFieldDoesntExist(): void
    {
        $this->tracker_formelement_factory->method('getUsedFieldByNameForUser')->willReturn(null);

        $result = $this->formatted_changeset_value_for_int_field_retriever
            ->getFormattedChangesetValueForFieldInt(
                'time',
                1234,
                ArtifactTestBuilder::anArtifact(42)->build(),
                UserTestBuilder::buildWithDefaults(),
            );

        $this->assertNull($result);
    }
}
