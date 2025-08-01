<?php
/**
 * Copyright (c) Enalean, 2025 - Present. All Rights Reserved.
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

namespace Tuleap\CrossTracker\Query\Advanced\ResultBuilder\Metadata\Semantic\Status;

use LogicException;
use PFUser;
use PHPUnit\Framework\MockObject\MockObject;
use Tuleap\CrossTracker\Query\Advanced\ResultBuilder\Representations\StaticListRepresentation;
use Tuleap\CrossTracker\Query\Advanced\ResultBuilder\Representations\StaticListValueRepresentation;
use Tuleap\CrossTracker\Query\Advanced\ResultBuilder\SelectedValue;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Artifact\RetrieveArtifact;
use Tuleap\Tracker\Semantic\Status\TrackerSemanticStatus;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;
use Tuleap\Tracker\Test\Builders\Fields\ListFieldBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use Tuleap\Tracker\Test\Stub\RetrieveArtifactStub;
use Tuleap\Tracker\Test\Stub\Semantic\Status\RetrieveSemanticStatusStub;
use Tuleap\Tracker\Tracker;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class StatusResultBuilderTest extends TestCase
{
    private Artifact $artifact;
    private RetrieveArtifact $retrieve_artifact;
    private RetrieveSemanticStatusStub $semantic_status_retriever;
    private PFUser $user;
    private TrackerSemanticStatus&MockObject $status_semantic;
    private Tracker $tracker;

    #[\Override]
    protected function setUp(): void
    {
        $this->user              = UserTestBuilder::aRandomActiveUser()->build();
        $this->tracker           = TrackerTestBuilder::aTracker()->build();
        $this->artifact          = ArtifactTestBuilder::anArtifact(13)->inTracker($this->tracker)->build();
        $this->retrieve_artifact = RetrieveArtifactStub::withArtifacts($this->artifact);

        $this->status_semantic = $this->createMock(TrackerSemanticStatus::class);
        $this->status_semantic->method('getTracker')->willReturn($this->artifact->getTracker());
        $field = ListFieldBuilder::aListField(456)->withReadPermission($this->user, true)->build();
        $this->status_semantic->method('getField')->willReturn($field);
        $this->semantic_status_retriever = RetrieveSemanticStatusStub::build();
    }

    public function testItDoesNothingWhenLabelIsNull(): void
    {
        $select_results = [['id' => $this->artifact->getId(), '@status' => null, '@status_color' => 'neon-green']];

        $this->semantic_status_retriever->withSemanticStatus($this->status_semantic);
        $builder    = new StatusResultBuilder($this->retrieve_artifact, $this->semantic_status_retriever);
        $collection = $builder->getResult($select_results, $this->user);
        self::assertEqualsCanonicalizing([
            $this->artifact->getId() => new SelectedValue('@status', new StaticListRepresentation([])),
        ], $collection->values);
    }

    public function testItThrowsAnExceptionWhenArtifactIsNotFound(): void
    {
        $select_results = [['id' => $this->artifact->getId(), '@status' => 'Open', '@status_color' => 'neon-green']];

        $this->semantic_status_retriever->withSemanticStatus($this->status_semantic);
        $builder = new StatusResultBuilder(RetrieveArtifactStub::withNoArtifact(), $this->semantic_status_retriever);

        $this->expectException(LogicException::class);

        $builder->getResult($select_results, $this->user);
    }

    public function testItDoesNothingWhenUserCanNotReadField(): void
    {
        $select_results = [['id' => $this->artifact->getId(), '@status' => 'Open', '@status_color' => 'neon-green']];

        $field = ListFieldBuilder::aListField(457)
            ->withReadPermission($this->user, false)
            ->inTracker($this->artifact->getTracker())
            ->build();

        $status_semantic = $this->createMock(TrackerSemanticStatus::class);
        $status_semantic->method('getTracker')->willReturn($this->artifact->getTracker());
        $status_semantic->method('getField')->willReturn($field);
        $this->semantic_status_retriever->withSemanticStatus($status_semantic);

        $builder    = new StatusResultBuilder($this->retrieve_artifact, $this->semantic_status_retriever);
        $collection = $builder->getResult($select_results, $this->user);
        self::assertEqualsCanonicalizing([
            $this->artifact->getId() => new SelectedValue('@status', new StaticListRepresentation([])),
        ], $collection->values);
    }

    public function testItBuildsTheStatusValue(): void
    {
        $second_artifact = ArtifactTestBuilder::anArtifact(42)->inTracker($this->tracker)->build();

        $select_results = [
            ['id' => $this->artifact->getId(), '@status' => 'Open', '@status_color' => 'neon-green'],
            ['id' => $second_artifact->getId(), '@status' => 'Close', '@status_color' => 'fiesta-red'],
        ];

        $this->retrieve_artifact = RetrieveArtifactStub::withArtifacts(
            $this->artifact,
            $second_artifact
        );
        $this->semantic_status_retriever->withSemanticStatus($this->status_semantic);
        $builder    = new StatusResultBuilder($this->retrieve_artifact, $this->semantic_status_retriever);
        $collection = $builder->getResult($select_results, $this->user);
        self::assertEqualsCanonicalizing([
            $this->artifact->getId() => new SelectedValue('@status', new StaticListRepresentation([
                new StaticListValueRepresentation('Open', 'neon-green'),
            ])),
            $second_artifact->getId() => new SelectedValue('@status', new StaticListRepresentation([
                new StaticListValueRepresentation('Close', 'fiesta-red'),
            ])),
        ], $collection->values);
    }

    public function testItBuildMultipleStatusValue(): void
    {
        $select_results = [['id' => $this->artifact->getId(), '@status' => ['Closed', 'Also open'], '@status_color' => ['fiesta-red']]];

        $this->semantic_status_retriever->withSemanticStatus($this->status_semantic);
        $builder    = new StatusResultBuilder($this->retrieve_artifact, $this->semantic_status_retriever);
        $collection = $builder->getResult($select_results, $this->user);
        self::assertEqualsCanonicalizing([
            $this->artifact->getId() => new SelectedValue('@status', new StaticListRepresentation([
                new StaticListValueRepresentation('Closed', 'fiesta-red'),
                new StaticListValueRepresentation('Also open', null),
            ])),
        ], $collection->values);
    }
}
