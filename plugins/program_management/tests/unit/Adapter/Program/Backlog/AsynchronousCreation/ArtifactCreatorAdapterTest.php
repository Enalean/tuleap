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

namespace Tuleap\ProgramManagement\Adapter\Program\Backlog\AsynchronousCreation;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;
use Tuleap\ProgramManagement\Adapter\Program\Backlog\ProgramIncrement\Source\Changeset\Values\ArtifactLinkValueFormatter;
use Tuleap\ProgramManagement\Adapter\Program\Backlog\ProgramIncrement\Source\Changeset\Values\ChangesetValuesFormatter;
use Tuleap\ProgramManagement\Adapter\Program\Backlog\ProgramIncrement\Source\Changeset\Values\DescriptionValueFormatter;
use Tuleap\ProgramManagement\Domain\Program\Backlog\AsynchronousCreation\ArtifactCreationException;
use Tuleap\ProgramManagement\Domain\Program\Backlog\AsynchronousCreation\MirroredTimeboxFirstChangeset;
use Tuleap\ProgramManagement\Domain\Program\Backlog\TimeboxArtifactLinkType;
use Tuleap\ProgramManagement\Domain\TrackerNotFoundException;
use Tuleap\ProgramManagement\Tests\Builder\MirroredTimeboxFirstChangesetBuilder;
use Tuleap\ProgramManagement\Tests\Builder\SourceTimeboxChangesetValuesBuilder;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveUserStub;
use Tuleap\ProgramManagement\Tests\Stub\SynchronizedFieldsStubPreparation;
use Tuleap\ProgramManagement\Tests\Stub\TrackerIdentifierStub;
use Tuleap\ProgramManagement\Tests\Stub\UserIdentifierStub;
use Tuleap\Tracker\Artifact\Creation\TrackerArtifactCreator;
use Tuleap\Tracker\Changeset\Validation\ChangesetWithFieldsValidationContext;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

final class ArtifactCreatorAdapterTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private const NEW_MIRRORED_TIMEBOX_ID     = 201;
    private const SOURCE_PROGRAM_INCREMENT_ID = 101;
    private const MIRRORED_TIMEBOX_TRACKER_ID = 33;
    private const USER_ID                     = 198;
    private const SUBMISSION_TIMESTAMP        = 1648451521;
    private const TITLE_ID                    = 392;
    private const TITLE_VALUE                 = 'welfaring';
    private const DESCRIPTION_ID              = 675;
    private const DESCRIPTION_VALUE           = 'preinstill';
    private const DESCRIPTION_FORMAT          = 'text';
    private const STATUS_ID                   = 439;
    private const MAPPED_STATUS_BIND_VALUE_ID = 1080;
    private const START_DATE_ID               = 980;
    private const START_DATE_VALUE            = '2020-11-02';
    private const END_PERIOD_ID               = 483;
    private const END_PERIOD_VALUE            = '2020-11-06';
    private const ARTIFACT_LINK_ID            = 842;

    /**
     * @var MockObject&TrackerArtifactCreator
     */
    private $creator;
    /**
     * @var Stub&\TrackerFactory
     */
    private $tracker_factory;
    private MirroredTimeboxFirstChangeset $changeset;

    protected function setUp(): void
    {
        $this->creator         = $this->createMock(TrackerArtifactCreator::class);
        $this->tracker_factory = $this->createStub(\TrackerFactory::class);

        $fields = SynchronizedFieldsStubPreparation::withAllFields(
            self::TITLE_ID,
            self::DESCRIPTION_ID,
            self::STATUS_ID,
            self::START_DATE_ID,
            self::END_PERIOD_ID,
            self::ARTIFACT_LINK_ID
        );

        $source_values = SourceTimeboxChangesetValuesBuilder::buildWithValues(
            self::TITLE_VALUE,
            self::DESCRIPTION_VALUE,
            self::DESCRIPTION_FORMAT,
            ['reverentness'],
            self::START_DATE_VALUE,
            self::END_PERIOD_VALUE,
            self::SOURCE_PROGRAM_INCREMENT_ID,
            self::SUBMISSION_TIMESTAMP
        );

        $this->changeset = MirroredTimeboxFirstChangesetBuilder::buildWithValues(
            TrackerIdentifierStub::withId(self::MIRRORED_TIMEBOX_TRACKER_ID),
            self::MAPPED_STATUS_BIND_VALUE_ID,
            $fields,
            $source_values,
            UserIdentifierStub::withId(self::USER_ID)
        );
    }

    private function getCreator(): ArtifactCreatorAdapter
    {
        return new ArtifactCreatorAdapter(
            $this->creator,
            $this->tracker_factory,
            RetrieveUserStub::withGenericUser(),
            new ChangesetValuesFormatter(
                new ArtifactLinkValueFormatter(),
                new DescriptionValueFormatter()
            )
        );
    }

    public function testItCreatesAnArtifact(): void
    {
        $full_tracker = TrackerTestBuilder::aTracker()->build();
        $this->tracker_factory->method('getTrackerById')->willReturn($full_tracker);
        $this->creator->expects(self::once())
            ->method('create')
            ->with(
                self::isInstanceOf(\Tracker::class),
                [
                    self::ARTIFACT_LINK_ID => [
                        'new_values' => (string) self::SOURCE_PROGRAM_INCREMENT_ID,
                        'natures'    => [self::SOURCE_PROGRAM_INCREMENT_ID => TimeboxArtifactLinkType::ART_LINK_SHORT_NAME]
                    ],
                    self::TITLE_ID         => self::TITLE_VALUE,
                    self::DESCRIPTION_ID   => [
                        'content' => self::DESCRIPTION_VALUE,
                        'format'  => self::DESCRIPTION_FORMAT
                    ],
                    self::STATUS_ID        => [self::MAPPED_STATUS_BIND_VALUE_ID],
                    self::START_DATE_ID    => self::START_DATE_VALUE,
                    self::END_PERIOD_ID    => self::END_PERIOD_VALUE
                ],
                self::isInstanceOf(\PFUser::class),
                self::SUBMISSION_TIMESTAMP,
                false,
                false,
                self::isInstanceOf(ChangesetWithFieldsValidationContext::class)
            )
            ->willReturn(
                ArtifactTestBuilder::anArtifact(self::NEW_MIRRORED_TIMEBOX_ID)
                    ->withSubmissionTimestamp(self::SUBMISSION_TIMESTAMP)
                    ->build()
            );

        $new_artifact = $this->getCreator()->create($this->changeset);
        self::assertSame(self::NEW_MIRRORED_TIMEBOX_ID, $new_artifact->getId());
    }

    public function testItThrowsIfItCantFindTracker(): void
    {
        $this->creator->expects(self::never())->method('create');
        $this->tracker_factory->method('getTrackerById')->willReturn(null);

        $this->expectException(TrackerNotFoundException::class);
        $this->getCreator()->create($this->changeset);
    }

    public function testItThrowsWhenThereIsAnErrorDuringCreation(): void
    {
        $full_tracker = TrackerTestBuilder::aTracker()->build();
        $this->tracker_factory->method('getTrackerById')->willReturn($full_tracker);
        $this->creator->method('create')->willReturn(null);

        $this->expectException(ArtifactCreationException::class);
        $this->getCreator()->create($this->changeset);
    }
}
