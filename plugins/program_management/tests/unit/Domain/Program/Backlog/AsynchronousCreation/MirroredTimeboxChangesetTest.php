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

use Tuleap\ProgramManagement\Adapter\Team\MirroredTimeboxes\MirroredTimeboxProxy;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Changeset\Values\ArtifactLinkValue;
use Tuleap\ProgramManagement\Tests\Builder\SourceTimeboxChangesetValuesBuilder;
use Tuleap\ProgramManagement\Tests\Stub\GatherSynchronizedFieldsStub;
use Tuleap\ProgramManagement\Tests\Stub\MapStatusByValueStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveTrackerOfArtifactStub;
use Tuleap\ProgramManagement\Tests\Stub\TrackerIdentifierStub;
use Tuleap\ProgramManagement\Tests\Stub\UserIdentifierStub;

final class MirroredTimeboxChangesetTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private const MIRRORED_TIMEBOX_ID  = 821;
    private const USER_ID              = 189;
    private const SUBMISSION_TIMESTAMP = 1408642745;
    private RetrieveTrackerOfArtifactStub $tracker_retriever;
    private GatherSynchronizedFieldsStub $fields_gatherer;
    private MapStatusByValueStub $status_mapper;
    private MirroredTimeboxProxy $timebox;

    protected function setUp(): void
    {
        $this->tracker_retriever = RetrieveTrackerOfArtifactStub::withTrackers(
            TrackerIdentifierStub::buildWithDefault()
        );
        $this->fields_gatherer   = GatherSynchronizedFieldsStub::withDefaults();
        $this->status_mapper     = MapStatusByValueStub::withValues(2105);

        $this->timebox = new MirroredTimeboxProxy(self::MIRRORED_TIMEBOX_ID);
    }

    public function testItBuildsFromMirroredTimebox(): void
    {
        $changeset = MirroredTimeboxChangeset::fromMirroredTimebox(
            $this->tracker_retriever,
            $this->fields_gatherer,
            $this->status_mapper,
            $this->timebox,
            SourceTimeboxChangesetValuesBuilder::buildWithSubmissionDate(self::SUBMISSION_TIMESTAMP),
            ArtifactLinkValue::buildEmptyValue(),
            UserIdentifierStub::withId(self::USER_ID)
        );

        self::assertSame(self::MIRRORED_TIMEBOX_ID, $changeset->mirrored_timebox->getId());
        self::assertSame(self::USER_ID, $changeset->user->getId());
        self::assertNotNull($changeset->values);
        self::assertSame(self::SUBMISSION_TIMESTAMP, $changeset->submission_date->getValue());
    }
}
