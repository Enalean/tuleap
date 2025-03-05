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

namespace Tuleap\ProgramManagement\Domain\Program\Backlog\AsynchronousCreation;

use Tuleap\ProgramManagement\Adapter\Program\Backlog\ProgramIncrement\Source\Changeset\Values\ArtifactLinkTypeProxy;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Changeset\Values\ArtifactLinkValue;
use Tuleap\ProgramManagement\Tests\Builder\SourceTimeboxChangesetValuesBuilder;
use Tuleap\ProgramManagement\Tests\Stub\GatherSynchronizedFieldsStub;
use Tuleap\ProgramManagement\Tests\Stub\MapStatusByValueStub;
use Tuleap\ProgramManagement\Tests\Stub\TrackerReferenceStub;
use Tuleap\ProgramManagement\Tests\Stub\UserIdentifierStub;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class MirroredTimeboxFirstChangesetTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private const MIRRORED_TIMEBOX_TRACKER_ID = 41;
    private const USER_ID                     = 112;
    private const SUBMISSION_TIMESTAMP        = 1872728943;
    private GatherSynchronizedFieldsStub $fields_gatherer;
    private MapStatusByValueStub $status_mapper;
    private TrackerReferenceStub $mirrored_timebox_tracker;

    protected function setUp(): void
    {
        $this->fields_gatherer = GatherSynchronizedFieldsStub::withDefaults();
        $this->status_mapper   = MapStatusByValueStub::withSuccessiveBindValueIds(1592);

        $this->mirrored_timebox_tracker = TrackerReferenceStub::withId(self::MIRRORED_TIMEBOX_TRACKER_ID);
    }

    public function testItBuildsFromMirroredTimeboxTracker(): void
    {
        $source_values       = SourceTimeboxChangesetValuesBuilder::buildWithSubmissionDate(self::SUBMISSION_TIMESTAMP);
        $artifact_link_value = ArtifactLinkValue::fromArtifactAndType(
            $source_values->getSourceTimebox(),
            ArtifactLinkTypeProxy::fromMirrorTimeboxType()
        );
        $changeset           = MirroredTimeboxFirstChangeset::fromMirroredTimeboxTracker(
            $this->fields_gatherer,
            $this->status_mapper,
            $this->mirrored_timebox_tracker,
            $source_values,
            $artifact_link_value,
            UserIdentifierStub::withId(self::USER_ID)
        );

        self::assertSame(self::MIRRORED_TIMEBOX_TRACKER_ID, $changeset->mirrored_timebox_tracker->getId());
        self::assertSame(self::USER_ID, $changeset->user->getId());
        self::assertNotNull($changeset->values);
        self::assertSame(self::SUBMISSION_TIMESTAMP, $changeset->submission_date->getValue());
    }
}
