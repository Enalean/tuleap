<?php
/**
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
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

namespace Tuleap\ProgramManagement\Domain\Team\MirroredTimebox;

use Tuleap\ProgramManagement\Adapter\Program\Backlog\ProgramIncrement\Source\Changeset\Values\ArtifactLinkTypeProxy;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Changeset\Values\ArtifactLinkValue;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Fields\NoArtifactLinkFieldException;
use Tuleap\ProgramManagement\Tests\Builder\MirroredProgramIncrementIdentifierBuilder;
use Tuleap\ProgramManagement\Tests\Stub\ArtifactIdentifierStub;
use Tuleap\ProgramManagement\Tests\Stub\ArtifactLinkFieldReferenceStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveArtifactLinkFieldStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveTrackerOfArtifactStub;
use Tuleap\ProgramManagement\Tests\Stub\UserIdentifierStub;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class ArtifactLinkChangesetTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private const MIRRORED_PROGRAM_INCREMENT_ID = 80;
    private const USER_ID                       = 120;
    private const ARTIFACT_LINK_FIELD_ID        = 737;
    private const MIRRORED_ITERATION_ID         = 74;
    private RetrieveTrackerOfArtifactStub $tracker_retriever;
    private MirroredProgramIncrementIdentifier $mirrored_program_increment;
    private RetrieveArtifactLinkFieldStub $field_retriever;
    private UserIdentifierStub $user;
    private ArtifactLinkValue $artifact_link_value;

    #[\Override]
    protected function setUp(): void
    {
        $this->tracker_retriever          = RetrieveTrackerOfArtifactStub::withIds(1);
        $this->field_retriever            = RetrieveArtifactLinkFieldStub::withFields(
            ArtifactLinkFieldReferenceStub::withId(self::ARTIFACT_LINK_FIELD_ID)
        );
        $this->mirrored_program_increment = MirroredProgramIncrementIdentifierBuilder::buildWithId(
            self::MIRRORED_PROGRAM_INCREMENT_ID
        );
        $this->user                       = UserIdentifierStub::withId(self::USER_ID);

        $this->artifact_link_value = ArtifactLinkValue::fromArtifactAndType(
            ArtifactIdentifierStub::withId(self::MIRRORED_ITERATION_ID),
            ArtifactLinkTypeProxy::fromIsChildType()
        );
    }

    public function testItBuildsFromMirroredProgramIncrement(): void
    {
        $changeset = ArtifactLinkChangeset::fromMirroredProgramIncrement(
            $this->tracker_retriever,
            $this->field_retriever,
            $this->mirrored_program_increment,
            $this->user,
            $this->artifact_link_value
        );
        self::assertSame(self::MIRRORED_PROGRAM_INCREMENT_ID, $changeset->mirrored_program_increment->getId());
        self::assertSame(self::USER_ID, $changeset->user->getId());
        self::assertSame(self::ARTIFACT_LINK_FIELD_ID, $changeset->artifact_link_field->getId());
        self::assertSame(self::MIRRORED_ITERATION_ID, $changeset->artifact_link_value->linked_artifact->getId());
        self::assertSame(
            \Tuleap\Tracker\FormElement\Field\ArtifactLink\ArtifactLinkField::TYPE_IS_CHILD,
            (string) $changeset->artifact_link_value->type
        );
    }

    public function testItThrowsWhenNoArtifactLinkField(): void
    {
        $this->expectException(NoArtifactLinkFieldException::class);
        ArtifactLinkChangeset::fromMirroredProgramIncrement(
            $this->tracker_retriever,
            RetrieveArtifactLinkFieldStub::withError(),
            $this->mirrored_program_increment,
            $this->user,
            $this->artifact_link_value
        );
    }
}
