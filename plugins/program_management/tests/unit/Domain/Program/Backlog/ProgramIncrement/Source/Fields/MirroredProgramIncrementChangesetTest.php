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

namespace Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement;

use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Changeset\Values\ArtifactLinkValue;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Fields\MirroredProgramIncrementChangeset;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Fields\SynchronizedFieldReferences;
use Tuleap\ProgramManagement\Domain\Program\Backlog\TimeboxArtifactLinkType;
use Tuleap\ProgramManagement\Tests\Builder\SourceTimeboxChangesetValuesBuilder;
use Tuleap\ProgramManagement\Tests\Stub\GatherSynchronizedFieldsStub;
use Tuleap\ProgramManagement\Tests\Stub\MapStatusByValueStub;
use Tuleap\ProgramManagement\Tests\Stub\SynchronizedFieldsStubPreparation;
use Tuleap\ProgramManagement\Tests\Stub\TrackerIdentifierStub;

final class MirroredProgramIncrementChangesetTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private const MAPPED_STATUS_BIND_VALUE_ID = 3001;
    private const ARTIFACT_LINK_ID            = 1001;
    private const TITLE_ID                    = 1002;
    private const DESCRIPTION_ID              = 1003;
    private const STATUS_ID                   = 1004;
    private const START_DATE_ID               = 1005;
    private const END_DATE_ID                 = 1006;
    private const SOURCE_PROGRAM_INCREMENT_ID = 112;
    private const TITLE_VALUE                 = 'Program Release';
    private const DESCRIPTION_CONTENT         = '<p>Description</p>';
    private const DESCRIPTION_FORMAT          = 'html';
    private const START_DATE_VALUE            = '2020-10-01';
    private const END_DATE_VALUE              = '2020-10-10';

    public function testItReturnsFieldsDataAsArrayForArtifactCreator(): void
    {
        $status_mapper       = MapStatusByValueStub::withValues(self::MAPPED_STATUS_BIND_VALUE_ID);
        $values              = SourceTimeboxChangesetValuesBuilder::buildWithValues(
            self::TITLE_VALUE,
            self::DESCRIPTION_CONTENT,
            self::DESCRIPTION_FORMAT,
            ['Planned'],
            self::START_DATE_VALUE,
            self::END_DATE_VALUE,
            self::SOURCE_PROGRAM_INCREMENT_ID
        );
        $artifact_link_value = ArtifactLinkValue::fromSourceTimeboxValues($values);
        $target_fields       = SynchronizedFieldReferences::fromTrackerIdentifier(
            GatherSynchronizedFieldsStub::withFieldsPreparations(
                new SynchronizedFieldsStubPreparation(
                    self::TITLE_ID,
                    self::DESCRIPTION_ID,
                    self::STATUS_ID,
                    self::START_DATE_ID,
                    self::END_DATE_ID,
                    self::ARTIFACT_LINK_ID
                )
            ),
            TrackerIdentifierStub::buildWithDefault(),
            null
        );

        $changeset = MirroredProgramIncrementChangeset::fromSourceChangesetValuesAndSynchronizedFields(
            $status_mapper,
            $values,
            $artifact_link_value,
            $target_fields
        );

        self::assertEquals(
            [
                self::ARTIFACT_LINK_ID => [
                    'new_values' => (string) self::SOURCE_PROGRAM_INCREMENT_ID,
                    'natures'    => [
                        (string) self::SOURCE_PROGRAM_INCREMENT_ID => TimeboxArtifactLinkType::ART_LINK_SHORT_NAME
                    ]
                ],
                self::TITLE_ID         => self::TITLE_VALUE,
                self::DESCRIPTION_ID   => [
                    'content' => self::DESCRIPTION_CONTENT,
                    'format'  => self::DESCRIPTION_FORMAT
                ],
                self::STATUS_ID        => [self::MAPPED_STATUS_BIND_VALUE_ID],
                self::START_DATE_ID    => self::START_DATE_VALUE,
                self::END_DATE_ID      => self::END_DATE_VALUE
            ],
            $changeset->toFieldsDataArray()
        );
    }
}
