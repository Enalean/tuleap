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

namespace Tuleap\Tracker\Artifact\Changeset\TextDiff;

use PHPUnit\Framework\MockObject\MockObject;
use Tracker_Artifact_ChangesetFactory;
use Tracker_FormElementFactory;
use Tuleap\Request\ForbiddenException;
use Tuleap\Request\NotFoundException;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;
use Tuleap\Tracker\Test\Builders\ChangesetTestBuilder;
use Tuleap\Tracker\Test\Builders\Fields\IntegerFieldBuilder;
use Tuleap\Tracker\Test\Builders\Fields\TextFieldBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class ChangesetsForDiffRetrieverTest extends TestCase
{
    private Tracker_FormElementFactory&MockObject $field_factory;
    private Tracker_Artifact_ChangesetFactory&MockObject $changeset_factory;
    private ChangesetsForDiffRetriever $changeset_for_diff_retriever;

    #[\Override]
    protected function setUp(): void
    {
        $this->changeset_factory = $this->createMock(Tracker_Artifact_ChangesetFactory::class);
        $this->field_factory     = $this->createMock(Tracker_FormElementFactory::class);

        $this->changeset_for_diff_retriever = new ChangesetsForDiffRetriever(
            $this->changeset_factory,
            $this->field_factory
        );
    }

    public function testItThrowsAnErrorWhenChangesetIsNotFound(): void
    {
        $artifact = ArtifactTestBuilder::anArtifact(1)->build();
        $this->changeset_factory->expects($this->once())->method('getChangeset')->willReturn(null);

        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage('Changeset is not found.');

        $this->changeset_for_diff_retriever->retrieveChangesets($artifact, 123, 789);
    }

    public function testItThrowsAnExceptionWhenFieldIsNotFound(): void
    {
        $artifact      = ArtifactTestBuilder::anArtifact(1)->build();
        $next_changset = ChangesetTestBuilder::aChangeset(789)->build();
        $this->changeset_factory->expects($this->once())->method('getChangeset')->willReturn($next_changset);

        $this->field_factory->method('getFieldById')->with(123)->willReturn(null);

        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage('Field not found.');

        $this->changeset_for_diff_retriever->retrieveChangesets($artifact, 123, 789);
    }

    public function testItThrowsAnExceptionWhenFieldIsNotATextField(): void
    {
        $artifact      = ArtifactTestBuilder::anArtifact(1)->build();
        $next_changset = ChangesetTestBuilder::aChangeset(789)->build();
        $this->changeset_factory->expects($this->once())->method('getChangeset')->willReturn($next_changset);

        $field = IntegerFieldBuilder::anIntField(123)->build();

        $this->field_factory->method('getFieldById')->with(123)->willReturn($field);

        $this->expectException(ForbiddenException::class);
        $this->expectExceptionMessage('Only text fields are supported for diff.');

        $this->changeset_for_diff_retriever->retrieveChangesets($artifact, 123, 789);
    }

    public function testItReturnsAChangesetsForDiff(): void
    {
        $next_changset = ChangesetTestBuilder::aChangeset(12)->build();
        $artifact      = ArtifactTestBuilder::anArtifact(1)->withChangesets($next_changset)->build();
        $this->changeset_factory->expects($this->once())->method('getChangeset')->willReturn($next_changset);

        $field = TextFieldBuilder::aTextField(123)->build();

        $this->field_factory->method('getFieldById')->with(123)->willReturn($field);

        $expected_changeset = new ChangesetsForDiff($next_changset, $field, null);

        $changesets_for_diff = $this->changeset_for_diff_retriever->retrieveChangesets(
            $artifact,
            123,
            789
        );

        self::assertEquals($expected_changeset, $changesets_for_diff);
    }
}
