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

use Tracker_FormElementFactory;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;
use Tuleap\Tracker\Test\Builders\Fields\StringFieldBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class FormattedChangesetValueForTextFieldRetrieverTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testGetFormattedChangesetValueForFieldText(): void
    {
        $tracker_formelement_factory = $this->createMock(Tracker_FormElementFactory::class);
        $tracker_formelement_factory
            ->method('getUsedFieldByNameForUser')
            ->willReturn(StringFieldBuilder::aStringField(112)->build());

        $formatted_changeset_value_for_text_field_retriever = new FormattedChangesetValueForTextFieldRetriever(
            $tracker_formelement_factory
        );
        $result                                             = $formatted_changeset_value_for_text_field_retriever
            ->getFormattedChangesetValueForFieldText(
                'result',
                'Result',
                ArtifactTestBuilder::anArtifact(123)->build(),
                UserTestBuilder::buildWithDefaults(),
            );

        $expected_result = ['content' => 'Result', 'format' => 'html'];

        $this->assertEquals($expected_result, $result->value);
        $this->assertEquals(112, $result->field_id);
    }

    public function testGetFormattedChangesetValueForFieldTextReturnsNullIfFieldDoesntExist(): void
    {
        $tracker_formelement_factory = $this->createMock(Tracker_FormElementFactory::class);
        $tracker_formelement_factory
            ->method('getUsedFieldByNameForUser')
            ->willReturn(null);

        $formatted_changeset_value_for_text_field_retriever = new FormattedChangesetValueForTextFieldRetriever(
            $tracker_formelement_factory
        );
        $result                                             = $formatted_changeset_value_for_text_field_retriever
            ->getFormattedChangesetValueForFieldText(
                'result',
                'Result',
                ArtifactTestBuilder::anArtifact(123)->build(),
                UserTestBuilder::buildWithDefaults(),
            );

        $this->assertNull($result);
    }
}
