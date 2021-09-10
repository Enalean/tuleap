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

use Psr\Log\Test\TestLogger;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\ProgramIncrementUpdate;
use Tuleap\ProgramManagement\Tests\Builder\ProgramIncrementUpdateBuilder;
use Tuleap\ProgramManagement\Tests\Stub\GatherFieldValuesStub;
use Tuleap\ProgramManagement\Tests\Stub\GatherSynchronizedFieldsStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveChangesetSubmissionDateStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveFieldValuesGathererStub;
use Tuleap\ProgramManagement\Tests\Stub\SearchMirroredTimeboxesStub;

final class ProgramIncrementUpdateProcessorTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private const PROGRAM_INCREMENT_ID         = 63;
    private const USER_ID                      = 122;
    private const PROGRAM_INCREMENT_TRACKER_ID = 74;
    private const TITLE_VALUE                  = 'nocuously';
    private const FIRST_MIRROR_ID              = 137;
    private const SECOND_MIRROR_ID             = 194;
    private TestLogger $logger;
    private ProgramIncrementUpdate $update;

    protected function setUp(): void
    {
        $this->update = ProgramIncrementUpdateBuilder::buildWithIds(
            self::USER_ID,
            self::PROGRAM_INCREMENT_ID,
            self::PROGRAM_INCREMENT_TRACKER_ID,
            '3882'
        );
        $this->logger = new TestLogger();
    }

    private function getProcessor(): ProgramIncrementUpdateProcessor
    {
        return new ProgramIncrementUpdateProcessor(
            $this->logger,
            GatherSynchronizedFieldsStub::withDefaults(),
            RetrieveFieldValuesGathererStub::withGatherer(
                GatherFieldValuesStub::withValues(
                    self::TITLE_VALUE,
                    'unbowsome',
                    'text',
                    '2015-09-20',
                    '2016-06-08',
                    ['challote']
                )
            ),
            RetrieveChangesetSubmissionDateStub::withDefaults(),
            SearchMirroredTimeboxesStub::withIds(self::FIRST_MIRROR_ID, self::SECOND_MIRROR_ID)
        );
    }

    public function testItProcessesProgramIncrementUpdate(): void
    {
        $this->getProcessor()->processProgramIncrementUpdate($this->update);
        self::assertTrue(
            $this->logger->hasDebug(
                sprintf(
                    'Processing program increment update with program increment #%d for user #%d',
                    self::PROGRAM_INCREMENT_ID,
                    self::USER_ID
                )
            )
        );
        self::assertTrue($this->logger->hasDebug('Title value: ' . self::TITLE_VALUE));
        self::assertTrue(
            $this->logger->hasDebug(sprintf('Mirror ids: %d,%d', self::FIRST_MIRROR_ID, self::SECOND_MIRROR_ID))
        );
    }
}
