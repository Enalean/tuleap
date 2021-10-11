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

namespace Tuleap\ProgramManagement\Adapter\Program\Backlog\AsynchronousCreation;

use PHPUnit\Framework\MockObject\Stub;
use Tuleap\ProgramManagement\Adapter\Program\Backlog\ProgramIncrement\Source\Changeset\Values\ArtifactLinkValueFormatter;
use Tuleap\ProgramManagement\Adapter\Program\Backlog\ProgramIncrement\Source\Changeset\Values\ChangesetValuesFormatter;
use Tuleap\ProgramManagement\Adapter\Program\Backlog\ProgramIncrement\Source\Changeset\Values\DescriptionValueFormatter;
use Tuleap\ProgramManagement\Domain\Program\Backlog\AsynchronousCreation\MirroredTimeboxChangeset;
use Tuleap\ProgramManagement\Domain\Program\Backlog\AsynchronousCreation\NewChangesetCreationException;
use Tuleap\ProgramManagement\Domain\Workspace\ArtifactNotFoundException;
use Tuleap\ProgramManagement\Tests\Builder\MirroredTimeboxChangesetBuilder;
use Tuleap\ProgramManagement\Tests\Builder\SourceTimeboxChangesetValuesBuilder;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveUserStub;
use Tuleap\ProgramManagement\Tests\Stub\SynchronizedFieldsStubPreparation;
use Tuleap\ProgramManagement\Tests\Stub\UserIdentifierStub;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Artifact\Exception\FieldValidationException;
use Tuleap\Tracker\Artifact\XMLImport\TrackerImportConfig;
use Tuleap\Tracker\FormElement\Field\File\CreatedFileURLMapping;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;

final class ChangesetAdderTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private const TIMEBOX_ID                  = 49;
    private const USER_ID                     = 117;
    private const SUBMISSION_DATE             = 1889365945;
    private const TITLE_ID                    = 7469;
    private const TITLE_VALUE                 = 'unfluent';
    private const DESCRIPTION_ID              = 8775;
    private const DESCRIPTION_VALUE           = 'offensively';
    private const DESCRIPTION_FORMAT          = 'text';
    private const STATUS_ID                   = 2319;
    private const MAPPED_STATUS_BIND_VALUE_ID = 3971;
    private const START_DATE_ID               = 2225;
    private const START_DATE_VALUE            = '2011-02-21';
    private const END_PERIOD_ID               = 3513;
    private const END_PERIOD_VALUE            = '2022-05-21';
    private const ARTIFACT_LINK_ID            = 7248;
    /**
     * @var Stub&\Tracker_ArtifactFactory
     */
    private $artifact_factory;
    /**
     * @var Stub&\Tracker_Artifact_Changeset_NewChangesetCreator
     */
    private $changeset_creator;
    private \PFUser $pfuser;
    private Artifact $artifact;
    private MirroredTimeboxChangeset $changeset;

    protected function setUp(): void
    {
        $this->artifact_factory  = $this->createStub(\Tracker_ArtifactFactory::class);
        $this->changeset_creator = $this->createStub(\Tracker_Artifact_Changeset_NewChangesetCreator::class);

        $this->pfuser   = UserTestBuilder::buildWithId(self::USER_ID);
        $this->artifact = ArtifactTestBuilder::anArtifact(self::TIMEBOX_ID)->build();

        $fields = SynchronizedFieldsStubPreparation::withAllFields(
            self::TITLE_ID,
            self::DESCRIPTION_ID,
            self::STATUS_ID,
            self::START_DATE_ID,
            self::END_PERIOD_ID,
            self::ARTIFACT_LINK_ID
        );

        $source_values = SourceTimeboxChangesetValuesBuilder::buildWithValuesAndSubmissionDate(
            self::TITLE_VALUE,
            self::DESCRIPTION_VALUE,
            self::DESCRIPTION_FORMAT,
            ['superelevation'],
            self::START_DATE_VALUE,
            self::END_PERIOD_VALUE,
            self::SUBMISSION_DATE
        );

        $this->changeset = MirroredTimeboxChangesetBuilder::buildWithValues(
            self::TIMEBOX_ID,
            self::MAPPED_STATUS_BIND_VALUE_ID,
            $fields,
            $source_values,
            UserIdentifierStub::withId(self::USER_ID)
        );
    }

    private function getAdder(): ChangesetAdder
    {
        return new ChangesetAdder(
            $this->artifact_factory,
            RetrieveUserStub::withUser($this->pfuser),
            new ChangesetValuesFormatter(
                new ArtifactLinkValueFormatter(),
                new DescriptionValueFormatter()
            ),
            $this->changeset_creator
        );
    }

    public function testItCreatesANewChangesetInGivenMirroredTimeboxArtifact(): void
    {
        $this->artifact_factory->method('getArtifactById')->willReturn($this->artifact);
        $this->changeset_creator->expects(self::once())
            ->method('create')
            ->with(
                $this->artifact,
                [
                    self::ARTIFACT_LINK_ID => [
                        'new_values' => '',
                        'natures'    => []
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
                '',
                $this->pfuser,
                self::SUBMISSION_DATE,
                false,
                \Tracker_Artifact_Changeset_Comment::COMMONMARK_COMMENT,
                self::isInstanceOf(CreatedFileURLMapping::class),
                self::isInstanceOf(TrackerImportConfig::class),
                []
            );

        $this->getAdder()->addChangeset($this->changeset);
    }

    public function dataProviderExceptions(): array
    {
        return [
            'with field validation error' => [new FieldValidationException([])],
            'with DB error'               => [new \Tracker_ChangesetNotCreatedException()]
        ];
    }

    /**
     * @dataProvider dataProviderExceptions
     */
    public function testItWrapsTrackerException(\Throwable $exception): void
    {
        $this->artifact_factory->method('getArtifactById')->willReturn($this->artifact);
        $this->changeset_creator->method('create')->willThrowException($exception);

        $this->expectException(NewChangesetCreationException::class);
        $this->getAdder()->addChangeset($this->changeset);
    }

    public function testItIgnoresNoChangeException(): void
    {
        $this->artifact_factory->method('getArtifactById')->willReturn($this->artifact);
        $this->changeset_creator->method('create')->willThrowException(
            new \Tracker_NoChangeException(self::TIMEBOX_ID, sprintf('release #%d', self::TIMEBOX_ID))
        );

        $this->expectNotToPerformAssertions();
        $this->getAdder()->addChangeset($this->changeset);
    }

    public function testItThrowsWhenArtifactCantBeFound(): void
    {
        $this->artifact_factory->method('getArtifactById')->willReturn(null);

        $this->expectException(ArtifactNotFoundException::class);
        $this->getAdder()->addChangeset($this->changeset);
    }
}
