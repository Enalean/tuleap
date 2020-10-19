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

use Mockery;
use PHPUnit\Framework\TestCase;
use Tuleap\Request\ForbiddenException;
use Tuleap\Request\NotFoundException;

final class ChangesetsForDiffRetrieverTest extends TestCase
{
    use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|\Tracker_FormElementFactory
     */
    private $field_factory;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|\Tracker_Artifact_ChangesetFactory
     */
    private $changeset_factory;
    /**
     * @var ChangesetsForDiffRetriever
     */
    private $changeset_for_diff_retriever;

    protected function setUp(): void
    {
        $this->changeset_factory = Mockery::mock(\Tracker_Artifact_ChangesetFactory::class);
        $this->field_factory     = Mockery::mock(\Tracker_FormElementFactory::class);

        $this->changeset_for_diff_retriever = new ChangesetsForDiffRetriever(
            $this->changeset_factory,
            $this->field_factory
        );
    }

    public function testItThrowsAnErrorWhenChangesetIsNotFound(): void
    {
        $artifact = \Mockery::mock(\Tuleap\Tracker\Artifact\Artifact::class);
        $this->changeset_factory->shouldReceive("getChangeset")->once()->andReturnNull();

        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage('Changeset is not found.');

        $this->changeset_for_diff_retriever->retrieveChangesets($artifact, 123, 789);
    }

    public function testItThrowsAnExceptionWhenFieldIsNotFound(): void
    {
        $artifact      = \Mockery::mock(\Tuleap\Tracker\Artifact\Artifact::class);
        $next_changset = Mockery::mock(\Tracker_Artifact_Changeset::class);
        $this->changeset_factory->shouldReceive("getChangeset")->once()->andReturn($next_changset);

        $this->field_factory->shouldReceive('getFieldById')->with(123)->andReturnNull();

        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage('Field not found.');

        $this->changeset_for_diff_retriever->retrieveChangesets($artifact, 123, 789);
    }

    public function testItThrowsAnExceptionWhenFieldIsNotATextField(): void
    {
        $artifact      = \Mockery::mock(\Tuleap\Tracker\Artifact\Artifact::class);
        $next_changset = Mockery::mock(\Tracker_Artifact_Changeset::class);
        $this->changeset_factory->shouldReceive("getChangeset")->once()->andReturn($next_changset);

        $field = Mockery::mock(\Tracker_FormElement_Field_Integer::class);

        $this->field_factory->shouldReceive('getFieldById')->with(123)->andReturn($field);

        $this->expectException(ForbiddenException::class);
        $this->expectExceptionMessage('Only text fields are supported for diff.');

        $this->changeset_for_diff_retriever->retrieveChangesets($artifact, 123, 789);
    }

    public function testItReturnsAChangesetsForDiff(): void
    {
        $artifact      = \Mockery::mock(\Tuleap\Tracker\Artifact\Artifact::class);
        $next_changset = Mockery::mock(\Tracker_Artifact_Changeset::class);
        $next_changset->shouldReceive('getId')->andReturn(12);
        $this->changeset_factory->shouldReceive("getChangeset")->once()->andReturn($next_changset);

        $field = Mockery::mock(\Tracker_FormElement_Field_Text::class);

        $this->field_factory->shouldReceive('getFieldById')->with(123)->andReturn($field);

        $artifact->shouldReceive('getPreviousChangeset')->with(12)->andReturnNull();

        $expected_changeset = new ChangesetsForDiff($next_changset, $field, null);

        $changesets_for_diff = $this->changeset_for_diff_retriever->retrieveChangesets(
            $artifact,
            123,
            789
        );

        $this->assertEquals($expected_changeset, $changesets_for_diff);
    }
}
