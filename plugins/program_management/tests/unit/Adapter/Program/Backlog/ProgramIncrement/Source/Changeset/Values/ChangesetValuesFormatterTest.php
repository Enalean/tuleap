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

namespace Tuleap\ProgramManagement\Adapter\Program\Backlog\ProgramIncrement\Source\Changeset\Values;

use Tuleap\ProgramManagement\Domain\Program\Backlog\AsynchronousCreation\MirroredTimeboxChangesetValues;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Changeset\Values\ArtifactLinkValue;
use Tuleap\ProgramManagement\Domain\Program\Backlog\TimeboxArtifactLinkType;
use Tuleap\ProgramManagement\Tests\Builder\MirroredTimeboxChangesetValuesBuilder;
use Tuleap\ProgramManagement\Tests\Stub\ArtifactIdentifierStub;
use Tuleap\ProgramManagement\Tests\Stub\ArtifactLinkFieldReferenceStub;

final class ChangesetValuesFormatterTest extends \Tuleap\Test\PHPUnit\TestCase
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
    private MirroredTimeboxChangesetValues $values;

    protected function setUp(): void
    {
        $this->values = MirroredTimeboxChangesetValuesBuilder::buildWithIdsAndValues(
            self::TITLE_ID,
            self::TITLE_VALUE,
            self::DESCRIPTION_ID,
            self::DESCRIPTION_CONTENT,
            self::DESCRIPTION_FORMAT,
            self::STATUS_ID,
            self::MAPPED_STATUS_BIND_VALUE_ID,
            self::START_DATE_ID,
            self::START_DATE_VALUE,
            self::END_DATE_ID,
            self::END_DATE_VALUE,
            self::ARTIFACT_LINK_ID,
            ArtifactLinkValue::fromArtifactAndType(
                ArtifactIdentifierStub::withId(self::SOURCE_PROGRAM_INCREMENT_ID),
                ArtifactLinkTypeProxy::fromMirrorTimeboxType()
            )
        );
    }

    private function getFormatter(): ChangesetValuesFormatter
    {
        return new ChangesetValuesFormatter(
            new ArtifactLinkValueFormatter(),
            new DescriptionValueFormatter()
        );
    }

    public function testItFormatsChangesetValuesToArrayExpectedByTrackerPluginAPI(): void
    {
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
            $this->getFormatter()->format($this->values)
        );
    }

    public function testItFormatsArtifactLinkChangesetValueToArrayExpectedByTrackerPluginAPI(): void
    {
        $artifact_link_field = ArtifactLinkFieldReferenceStub::withId(self::ARTIFACT_LINK_ID);
        $value               = ArtifactLinkValue::fromArtifactAndType(
            ArtifactIdentifierStub::withId(self::SOURCE_PROGRAM_INCREMENT_ID),
            ArtifactLinkTypeProxy::fromIsChildType()
        );
        self::assertEquals(
            [
                self::ARTIFACT_LINK_ID => [
                    'new_values' => (string) self::SOURCE_PROGRAM_INCREMENT_ID,
                    'natures'    => [
                        (string) self::SOURCE_PROGRAM_INCREMENT_ID => \Tracker_FormElement_Field_ArtifactLink::NATURE_IS_CHILD
                    ]
                ]
            ],
            $this->getFormatter()->formatArtifactLink($artifact_link_field, $value)
        );
    }
}
