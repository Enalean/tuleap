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

use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Changeset\Values\BuildEndPeriodValue;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Changeset\Values\BuildStartDateValue;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Changeset\Values\BuildStatusValue;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Changeset\Values\EndPeriodValue;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Changeset\Values\StartDateValue;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Changeset\Values\StatusValue;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Fields\BuildSynchronizedFields;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Fields\Field;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Fields\SynchronizedFields;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\ReplicationData;
use Tuleap\ProgramManagement\Domain\ProgramTracker;
use Tuleap\ProgramManagement\Tests\Builder\ReplicationDataBuilder;
use Tuleap\ProgramManagement\Tests\Builder\SynchronizedFieldsBuilder;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveDescriptionValueStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveTitleValueStub;

final class SourceChangesetValuesCollectionAdapterTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private const TITLE_VALUE                         = 'pseudographeme';
    private const DESCRIPTION_VALUE                   = '<p>chondrofibroma overfeel</p>';
    private const DESCRIPTION_FORMAT                  = 'html';
    private const SOURCE_TIMEBOX_ID                   = 11;
    private const SOURCE_TIMEBOX_SUBMISSION_TIMESTAMP = 1628844094;

    private BuildSynchronizedFields $fields_gatherer;
    private RetrieveTitleValueStub $title_retriever;
    private RetrieveDescriptionValueStub $description_retriever;

    protected function setUp(): void
    {
        $this->fields_gatherer       = $this->getFieldsGatherer();
        $this->title_retriever       = RetrieveTitleValueStub::withValue(self::TITLE_VALUE);
        $this->description_retriever = RetrieveDescriptionValueStub::withValue(
            self::DESCRIPTION_VALUE,
            self::DESCRIPTION_FORMAT
        );
    }

    private function getAdapter(): SourceChangesetValuesCollectionAdapter
    {
        return new SourceChangesetValuesCollectionAdapter(
            $this->fields_gatherer,
            $this->title_retriever,
            $this->description_retriever,
            $this->getStatusBuilder(),
            $this->getStartDateBuilder(),
            $this->getEndDateBuilder()
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
        self::assertEquals(1059, $values->getStatusValue()->getListValues()[0]->getId());
        self::assertSame('2013-07-24', $values->getStartDateValue()->getValue());
        self::assertSame('2016-10-17', $values->getEndPeriodValue()->getValue());
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

    private function getStatusBuilder(): BuildStatusValue
    {
        return new class implements BuildStatusValue {
            public function build(Field $field_status_data, ReplicationData $replication_data): StatusValue
            {
                $list_bind_value = new \Tracker_FormElement_Field_List_Bind_StaticValue(1059, 'Ongoing', '', 1, false);
                return new StatusValue([$list_bind_value]);
            }
        };
    }

    private function getStartDateBuilder(): BuildStartDateValue
    {
        return new class implements BuildStartDateValue {
            public function build(Field $field_start_date_data, ReplicationData $replication_data): StartDateValue
            {
                return new StartDateValue('2013-07-24');
            }
        };
    }

    private function getEndDateBuilder(): BuildEndPeriodValue
    {
        return new class implements BuildEndPeriodValue {
            public function build(Field $end_period_field_data, ReplicationData $replication_data): EndPeriodValue
            {
                return new EndPeriodValue('2016-10-17');
            }
        };
    }
}
