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

use Mockery;
use PHPUnit\Framework\TestCase;
use Tuleap\Tracker\REST\v1\ArtifactValuesRepresentation;

class ExecutionChangesExtractorTest extends TestCase
{
    use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|FormattedChangesetValueForFileFieldRetriever
     */
    private $formatted_changeset_value_for_file_field_retriever;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|FormattedChangesetValueForIntFieldRetriever
     */
    private $formatted_changeset_value_for_int_field_retriever;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|FormattedChangesetValueForTextFieldRetriever
     */
    private $formatted_changeset_value_for_text_field_retriever;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|FormattedChangesetValueForListFieldRetriever
     */
    private $formatted_changeset_value_for_list_field_retriever;
    /**
     * @var ExecutionChangesExtractor
     */
    private $execution_changes_extractor;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|\Tuleap\Tracker\Artifact\Artifact
     */
    private $artifact;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|\PFUser
     */
    private $user;

    protected function setUp(): void
    {
        $this->artifact = Mockery::mock(\Tuleap\Tracker\Artifact\Artifact::class);
        $this->user     = Mockery::mock(\PFUser::class);

        $this->formatted_changeset_value_for_file_field_retriever = Mockery::mock(FormattedChangesetValueForFileFieldRetriever::class);
        $this->formatted_changeset_value_for_int_field_retriever  = Mockery::mock(FormattedChangesetValueForIntFieldRetriever::class);
        $this->formatted_changeset_value_for_text_field_retriever = Mockery::mock(FormattedChangesetValueForTextFieldRetriever::class);
        $this->formatted_changeset_value_for_list_field_retriever = Mockery::mock(FormattedChangesetValueForListFieldRetriever::class);

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
            ->shouldReceive('getFormattedChangesetValueForFieldFile')
            ->once()
            ->andReturn(Mockery::mock(ArtifactValuesRepresentation::class));
        $this->formatted_changeset_value_for_int_field_retriever
            ->shouldReceive('getFormattedChangesetValueForFieldInt')
            ->once()
            ->andReturn(Mockery::mock(ArtifactValuesRepresentation::class));
        $this->formatted_changeset_value_for_text_field_retriever
            ->shouldReceive('getFormattedChangesetValueForFieldText')
            ->once()
            ->andReturn(Mockery::mock(ArtifactValuesRepresentation::class));
        $this->formatted_changeset_value_for_list_field_retriever
            ->shouldReceive('getFormattedChangesetValueForFieldList')
            ->once()
            ->andReturn(Mockery::mock(ArtifactValuesRepresentation::class));


        $result = $this->execution_changes_extractor->getChanges(
            'passed',
            [12],
            123,
            "result",
            $this->artifact,
            $this->user
        );

        $this->assertCount(4, $result);
    }

    public function testGetChangesShouldReturnVoidArrayIfTheirIsNoChange(): void
    {
        $this->formatted_changeset_value_for_file_field_retriever
            ->shouldReceive('getFormattedChangesetValueForFieldFile')
            ->never();
        $this->formatted_changeset_value_for_int_field_retriever
            ->shouldReceive('getFormattedChangesetValueForFieldInt')
            ->never();
        $this->formatted_changeset_value_for_text_field_retriever
            ->shouldReceive('getFormattedChangesetValueForFieldText')
            ->once()
            ->andReturn(null);
        $this->formatted_changeset_value_for_list_field_retriever
            ->shouldReceive('getFormattedChangesetValueForFieldList')
            ->once()
            ->andReturn(null);


        $result = $this->execution_changes_extractor->getChanges(
            'passed',
            [],
            0,
            "result",
            $this->artifact,
            $this->user
        );

        $this->assertCount(0, $result);
    }
}
