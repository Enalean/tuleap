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
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\REST\v1\ArtifactValuesRepresentation;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class ExecutionChangesExtractorTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private FormattedChangesetValueForFileFieldRetriever&MockObject $formatted_changeset_value_for_file_field_retriever;
    private FormattedChangesetValueForIntFieldRetriever&MockObject $formatted_changeset_value_for_int_field_retriever;
    private FormattedChangesetValueForTextFieldRetriever&MockObject $formatted_changeset_value_for_text_field_retriever;
    private FormattedChangesetValueForListFieldRetriever&MockObject $formatted_changeset_value_for_list_field_retriever;
    private ExecutionChangesExtractor $execution_changes_extractor;
    private Artifact $artifact;
    private \PFUser $user;

    #[\Override]
    protected function setUp(): void
    {
        $this->artifact = ArtifactTestBuilder::anArtifact(1)->build();
        $this->user     = UserTestBuilder::buildWithDefaults();

        $this->formatted_changeset_value_for_file_field_retriever = $this->createMock(FormattedChangesetValueForFileFieldRetriever::class);
        $this->formatted_changeset_value_for_int_field_retriever  = $this->createMock(FormattedChangesetValueForIntFieldRetriever::class);
        $this->formatted_changeset_value_for_text_field_retriever = $this->createMock(FormattedChangesetValueForTextFieldRetriever::class);
        $this->formatted_changeset_value_for_list_field_retriever = $this->createMock(FormattedChangesetValueForListFieldRetriever::class);

        $this->execution_changes_extractor = new ExecutionChangesExtractor(
            $this->formatted_changeset_value_for_file_field_retriever,
            $this->formatted_changeset_value_for_int_field_retriever,
            $this->formatted_changeset_value_for_text_field_retriever,
            $this->formatted_changeset_value_for_list_field_retriever
        );
    }

    public function testGetChanges(): void
    {
        $this->formatted_changeset_value_for_file_field_retriever
            ->expects($this->once())
            ->method('getFormattedChangesetValueForFieldFile')
            ->willReturn($this->createMock(ArtifactValuesRepresentation::class));
        $this->formatted_changeset_value_for_int_field_retriever
            ->expects($this->once())
            ->method('getFormattedChangesetValueForFieldInt')
            ->willReturn($this->createMock(ArtifactValuesRepresentation::class));
        $this->formatted_changeset_value_for_text_field_retriever
            ->expects($this->once())
            ->method('getFormattedChangesetValueForFieldText')
            ->willReturn($this->createMock(ArtifactValuesRepresentation::class));
        $this->formatted_changeset_value_for_list_field_retriever
            ->expects($this->once())
            ->method('getFormattedChangesetValueForFieldList')
            ->willReturn($this->createMock(ArtifactValuesRepresentation::class));


        $result = $this->execution_changes_extractor->getChanges(
            'passed',
            [12],
            [],
            123,
            'result',
            $this->artifact,
            $this->user
        );

        $this->assertCount(4, $result);
    }

    public function testGetChangesShouldReturnVoidArrayIfTheirIsNoChange(): void
    {
        $this->formatted_changeset_value_for_file_field_retriever
            ->expects($this->never())
            ->method('getFormattedChangesetValueForFieldFile');
        $this->formatted_changeset_value_for_int_field_retriever
            ->expects($this->never())
            ->method('getFormattedChangesetValueForFieldInt');
        $this->formatted_changeset_value_for_text_field_retriever
            ->expects($this->once())
            ->method('getFormattedChangesetValueForFieldText')
            ->willReturn(null);
        $this->formatted_changeset_value_for_list_field_retriever
            ->expects($this->once())
            ->method('getFormattedChangesetValueForFieldList')
            ->willReturn(null);


        $result = $this->execution_changes_extractor->getChanges(
            'passed',
            [],
            [],
            0,
            'result',
            $this->artifact,
            $this->user
        );

        $this->assertCount(0, $result);
    }
}
