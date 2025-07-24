<?php
/**
 * Copyright (c) Enalean 2020 -  Present. All Rights Reserved.
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

namespace Tuleap\ProgramManagement\Adapter\Program\Backlog\AsynchronousCreation;

use Tuleap\ProgramManagement\Domain\Program\Backlog\AsynchronousCreation\ProgramIncrementArtifactCreationException;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Changeset\Values\SourceTimeboxChangesetValues;
use Tuleap\ProgramManagement\Domain\Program\Backlog\TimeboxArtifactLinkType;
use Tuleap\ProgramManagement\Domain\Team\MirroredTimebox\MirroredProgramIncrementTrackerIdentifierCollection;
use Tuleap\ProgramManagement\Domain\Workspace\UserIdentifier;
use Tuleap\ProgramManagement\Tests\Builder\MirroredProgramIncrementTrackerIdentifierCollectionBuilder;
use Tuleap\ProgramManagement\Tests\Builder\SourceTimeboxChangesetValuesBuilder;
use Tuleap\ProgramManagement\Tests\Stub\CreateArtifactStub;
use Tuleap\ProgramManagement\Tests\Stub\GatherSynchronizedFieldsStub;
use Tuleap\ProgramManagement\Tests\Stub\MapStatusByValueStub;
use Tuleap\ProgramManagement\Tests\Stub\SynchronizedFieldsStubPreparation;
use Tuleap\ProgramManagement\Tests\Stub\UserIdentifierStub;
use Tuleap\Test\DB\DBTransactionExecutorPassthrough;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class ProgramIncrementsCreatorTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private const PROGRAM_INCREMENT_ID                         = 112;
    private const USER_ID                                      = 101;
    private const FIRST_MIRRORED_PROGRAM_INCREMENT_TRACKER_ID  = 99;
    private const SECOND_MIRRORED_PROGRAM_INCREMENT_TRACKER_ID = 2048;
    private const SUBMISSION_DATE                              = 1234567890;
    private const TITLE_VALUE                                  = 'Program Release';
    private const DESCRIPTION_VALUE                            = 'Description';
    private const DESCRIPTION_FORMAT                           = 'text';
    private const FIRST_MAPPED_STATUS_BIND_VALUE_ID            = 2271;
    private const SECOND_MAPPED_STATUS_BIND_VALUE_ID           = 6281;
    private const START_DATE_VALUE                             = 1601579528;
    private const END_DATE_VALUE                               = 1602288660;
    private const FIRST_TITLE_FIELD_ID                         = 604;
    private const FIRST_DESCRIPTION_FIELD_ID                   = 335;
    private const FIRST_STATUS_FIELD_ID                        = 772;
    private const FIRST_START_DATE_FIELD_ID                    = 876;
    private const FIRST_END_DATE_FIELD_ID                      = 790;
    private const FIRST_ARTIFACT_LINK_FIELD_ID                 = 608;
    private const SECOND_TITLE_FIELD_ID                        = 810;
    private const SECOND_DESCRIPTION_FIELD_ID                  = 887;
    private const SECOND_STATUS_FIELD_ID                       = 506;
    private const SECOND_START_DATE_FIELD_ID                   = 873;
    private const SECOND_END_DATE_FIELD_ID                     = 524;
    private const SECOND_ARTIFACT_LINK_FIELD_ID                = 866;

    private CreateArtifactStub $artifact_creator;
    private SourceTimeboxChangesetValues $field_values;
    private MirroredProgramIncrementTrackerIdentifierCollection $mirrored_trackers;
    private UserIdentifier $user_identifier;
    private GatherSynchronizedFieldsStub $fields_gatherer;

    #[\Override]
    protected function setUp(): void
    {
        $this->artifact_creator = CreateArtifactStub::withIds(39, 40);

        $this->user_identifier = UserIdentifierStub::buildGenericUser();
        $this->field_values    = SourceTimeboxChangesetValuesBuilder::build();

        $this->mirrored_trackers = MirroredProgramIncrementTrackerIdentifierCollectionBuilder::buildWithIds(self::FIRST_MIRRORED_PROGRAM_INCREMENT_TRACKER_ID, self::SECOND_MIRRORED_PROGRAM_INCREMENT_TRACKER_ID);

        $this->fields_gatherer = GatherSynchronizedFieldsStub::withFieldsPreparations(
            SynchronizedFieldsStubPreparation::withAllFields(self::FIRST_TITLE_FIELD_ID, self::FIRST_DESCRIPTION_FIELD_ID, self::FIRST_STATUS_FIELD_ID, self::FIRST_START_DATE_FIELD_ID, self::FIRST_END_DATE_FIELD_ID, self::FIRST_ARTIFACT_LINK_FIELD_ID),
            SynchronizedFieldsStubPreparation::withAllFields(
                self::FIRST_TITLE_FIELD_ID,
                self::FIRST_DESCRIPTION_FIELD_ID,
                self::FIRST_STATUS_FIELD_ID,
                self::FIRST_START_DATE_FIELD_ID,
                self::FIRST_END_DATE_FIELD_ID,
                self::FIRST_ARTIFACT_LINK_FIELD_ID
            ),
            SynchronizedFieldsStubPreparation::withAllFields(
                self::SECOND_TITLE_FIELD_ID,
                self::SECOND_DESCRIPTION_FIELD_ID,
                self::SECOND_STATUS_FIELD_ID,
                self::SECOND_START_DATE_FIELD_ID,
                self::SECOND_END_DATE_FIELD_ID,
                self::SECOND_ARTIFACT_LINK_FIELD_ID
            ),
        );
    }

    private function getCreator(): ProgramIncrementsCreator
    {
        return new ProgramIncrementsCreator(
            new DBTransactionExecutorPassthrough(),
            MapStatusByValueStub::withSuccessiveBindValueIds(self::FIRST_MAPPED_STATUS_BIND_VALUE_ID, self::SECOND_MAPPED_STATUS_BIND_VALUE_ID),
            $this->artifact_creator,
            $this->fields_gatherer
        );
    }

    public function testItCreatesMirrorProgramIncrements(): void
    {
        $this->getCreator()->createProgramIncrements(
            $this->field_values,
            $this->mirrored_trackers,
            $this->user_identifier
        );

        self::assertSame(2, $this->artifact_creator->getCallCount());
        [$first_changeset] = $this->artifact_creator->getArguments();
        $first_values      = $first_changeset->values;
        self::assertSame(
            self::FIRST_MIRRORED_PROGRAM_INCREMENT_TRACKER_ID,
            $first_changeset->mirrored_timebox_tracker->getId()
        );
        self::assertSame(self::FIRST_TITLE_FIELD_ID, $first_values->title_field->getId());
        self::assertSame(self::FIRST_DESCRIPTION_FIELD_ID, $first_values->description_field->getId());
        self::assertSame(self::FIRST_STATUS_FIELD_ID, $first_values->status_field->getId());
        self::assertEquals([self::FIRST_MAPPED_STATUS_BIND_VALUE_ID], $first_values->mapped_status_value->getValues());
        self::assertSame(self::FIRST_START_DATE_FIELD_ID, $first_values->start_date_field->getId());
        self::assertSame(self::FIRST_END_DATE_FIELD_ID, $first_values->end_period_field->getId());
        self::assertSame(self::FIRST_ARTIFACT_LINK_FIELD_ID, $first_values->artifact_link_field->getId());

        foreach ($this->artifact_creator->getArguments() as $changeset) {
            $values = $changeset->values;
            self::assertSame(self::TITLE_VALUE, $values->title_value->getValue());
            self::assertSame(self::DESCRIPTION_VALUE, $values->description_value->value);
            self::assertSame(self::DESCRIPTION_FORMAT, $values->description_value->format);
            self::assertSame(self::START_DATE_VALUE, $values->start_date_value->getValue());
            self::assertSame(self::END_DATE_VALUE, $values->end_period_value->getValue());
            self::assertSame(self::PROGRAM_INCREMENT_ID, $values->artifact_link_value?->linked_artifact?->getId());
            self::assertSame(TimeboxArtifactLinkType::ART_LINK_SHORT_NAME, (string) $values->artifact_link_value?->type);
            self::assertSame(self::USER_ID, $changeset->user->getId());
            self::assertSame(self::SUBMISSION_DATE, $changeset->submission_date->getValue());
        }
    }

    public function testItThrowsWhenThereIsAnErrorDuringCreation(): void
    {
        $this->artifact_creator = CreateArtifactStub::withError();

        $this->expectException(ProgramIncrementArtifactCreationException::class);
        $this->getCreator()->createProgramIncrements(
            $this->field_values,
            $this->mirrored_trackers,
            $this->user_identifier
        );
    }
}
