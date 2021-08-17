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

namespace Tuleap\ProgramManagement\Adapter\Program\Backlog\ProgramIncrement;

use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Fields\BuildSynchronizedFields;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Fields\SynchronizedFields;
use Tuleap\ProgramManagement\Domain\ProgramTracker;
use Tuleap\ProgramManagement\Tests\Builder\ReplicationDataBuilder;
use Tuleap\ProgramManagement\Tests\Builder\SynchronizedFieldsBuilder;
use Tuleap\ProgramManagement\Tests\Stub\GatherFieldValuesStub;

final class SourceChangesetValuesCollectionAdapterTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private const TITLE_VALUE                         = 'pseudographeme';
    private const DESCRIPTION_VALUE                   = '<p>chondrofibroma overfeel</p>';
    private const DESCRIPTION_FORMAT                  = 'html';
    private const SOURCE_TIMEBOX_ID                   = 11;
    private const SOURCE_TIMEBOX_SUBMISSION_TIMESTAMP = 1628844094;
    private const START_DATE_VALUE                    = '2013-07-24';
    private const END_PERIOD_VALUE                    = '2016-10-17';
    private const STATUS_VALUE                        = 'Ongoing';

    private function getAdapter(): SourceChangesetValuesCollectionAdapter
    {
        return new SourceChangesetValuesCollectionAdapter(
            $this->getFieldsGatherer(),
            GatherFieldValuesStub::withValues(
                self::TITLE_VALUE,
                self::DESCRIPTION_VALUE,
                self::DESCRIPTION_FORMAT,
                self::START_DATE_VALUE,
                self::END_PERIOD_VALUE,
                [self::STATUS_VALUE]
            )
        );
    }

    public function testItBuildsFromReplicationData(): void
    {
        $replication = ReplicationDataBuilder::buildWithArtifactIdAndSubmissionDate(
            self::SOURCE_TIMEBOX_ID,
            self::SOURCE_TIMEBOX_SUBMISSION_TIMESTAMP
        );
        $values      = $this->getAdapter()->buildCollection($replication);
        self::assertSame(self::TITLE_VALUE, $values->getTitleValue()->getValue());
        self::assertContains(self::DESCRIPTION_VALUE, $values->getDescriptionValue()->getValue());
        self::assertContains(self::DESCRIPTION_FORMAT, $values->getDescriptionValue()->getValue());
        self::assertEquals(self::STATUS_VALUE, $values->getStatusValue()->getListValues()[0]->getLabel());
        self::assertSame(self::START_DATE_VALUE, $values->getStartDateValue()->getValue());
        self::assertSame(self::END_PERIOD_VALUE, $values->getEndPeriodValue()->getValue());
        self::assertSame(self::SOURCE_TIMEBOX_ID, $values->getSourceArtifactId());
        self::assertSame(self::SOURCE_TIMEBOX_SUBMISSION_TIMESTAMP, $values->getSubmittedOn()->getValue());
        self::assertContainsEquals(self::SOURCE_TIMEBOX_ID, $values->getArtifactLinkValue()->getValues());
    }

    private function getFieldsGatherer(): BuildSynchronizedFields
    {
        return new class implements BuildSynchronizedFields {
            public function build(ProgramTracker $source_tracker): SynchronizedFields
            {
                return SynchronizedFieldsBuilder::buildWithIds(3001, 9041, 1635, 4382, 2729, 1995);
            }
        };
    }
}
