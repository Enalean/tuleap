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

use PHPUnit\Framework\Constraint\Callback;
use PHPUnit\Framework\MockObject\MockObject;
use Tuleap\ProgramManagement\Adapter\Program\Backlog\ProgramIncrement\Source\Changeset\Values\ArtifactLinkTypeProxy;
use Tuleap\ProgramManagement\Adapter\Program\Backlog\ProgramIncrement\Source\Changeset\Values\ArtifactLinkValueFormatter;
use Tuleap\ProgramManagement\Adapter\Program\Backlog\ProgramIncrement\Source\Changeset\Values\ChangesetValuesFormatter;
use Tuleap\ProgramManagement\Adapter\Program\Backlog\ProgramIncrement\Source\Changeset\Values\DateValueFormatter;
use Tuleap\ProgramManagement\Adapter\Program\Backlog\ProgramIncrement\Source\Changeset\Values\DescriptionValueFormatter;
use Tuleap\ProgramManagement\Domain\Program\Backlog\AsynchronousCreation\AddArtifactLinkChangesetException;
use Tuleap\ProgramManagement\Domain\Program\Backlog\AsynchronousCreation\MirroredTimeboxChangeset;
use Tuleap\ProgramManagement\Domain\Program\Backlog\AsynchronousCreation\NewChangesetCreationException;
use Tuleap\ProgramManagement\Domain\Team\MirroredTimebox\ArtifactLinkChangeset;
use Tuleap\ProgramManagement\Tests\Builder\ArtifactLinkChangesetBuilder;
use Tuleap\ProgramManagement\Tests\Builder\MirroredTimeboxChangesetBuilder;
use Tuleap\ProgramManagement\Tests\Builder\SourceTimeboxChangesetValuesBuilder;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveFullArtifactStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveUserStub;
use Tuleap\ProgramManagement\Tests\Stub\SynchronizedFieldsStubPreparation;
use Tuleap\ProgramManagement\Tests\Stub\UserIdentifierStub;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Artifact\Changeset\NewChangeset;
use Tuleap\Tracker\Artifact\Changeset\NewChangesetCreator;
use Tuleap\Tracker\Artifact\Changeset\PostCreation\PostCreationContext;
use Tuleap\Tracker\Artifact\Exception\FieldValidationException;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class ChangesetAdderTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private const TIMEBOX_ID                    = 49;
    private const USER_ID                       = 117;
    private const SUBMISSION_DATE               = 1889365945;
    private const TITLE_ID                      = 7469;
    private const TITLE_VALUE                   = 'unfluent';
    private const DESCRIPTION_ID                = 8775;
    private const DESCRIPTION_VALUE             = 'offensively';
    private const DESCRIPTION_FORMAT            = 'text';
    private const STATUS_ID                     = 2319;
    private const MAPPED_STATUS_BIND_VALUE_ID   = 3971;
    private const START_DATE_ID                 = 2225;
    private const START_DATE_VALUE              = 1298323326; // 2011-02-21T22:22:06+01:00
    private const END_PERIOD_ID                 = 3513;
    private const END_PERIOD_VALUE              = 1653168968; // 2022-05-21T23:36:08+02:00
    private const ARTIFACT_LINK_ID              = 7248;
    private const MIRRORED_PROGRAM_INCREMENT_ID = 86;
    private const MIRRORED_ITERATION_ID         = 33;

    /**
     * @var MockObject&NewChangesetCreator
     */
    private $changeset_creator;
    private \PFUser $pfuser;
    private MirroredTimeboxChangeset $changeset;
    private ArtifactLinkChangeset $artifact_link_changeset;
    private Artifact $artifact;

    #[\Override]
    protected function setUp(): void
    {
        $this->changeset_creator = $this->createMock(NewChangesetCreator::class);

        $this->pfuser = UserTestBuilder::buildWithId(self::USER_ID);

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
            ['superelevation'],
            self::START_DATE_VALUE,
            self::END_PERIOD_VALUE,
            112,
            self::SUBMISSION_DATE
        );

        $this->changeset = MirroredTimeboxChangesetBuilder::buildWithValues(
            self::TIMEBOX_ID,
            self::MAPPED_STATUS_BIND_VALUE_ID,
            $fields,
            $source_values,
            UserIdentifierStub::withId(self::USER_ID)
        );

        $this->artifact_link_changeset = ArtifactLinkChangesetBuilder::buildWithValues(
            self::MIRRORED_PROGRAM_INCREMENT_ID,
            self::ARTIFACT_LINK_ID,
            ArtifactLinkTypeProxy::fromIsChildType(),
            self::MIRRORED_ITERATION_ID,
            UserIdentifierStub::withId(self::USER_ID)
        );

        $this->artifact = ArtifactTestBuilder::anArtifact(self::TIMEBOX_ID)->build();
    }

    private function getAdder(): ChangesetAdder
    {
        return new ChangesetAdder(
            RetrieveFullArtifactStub::withArtifact($this->artifact),
            RetrieveUserStub::withUser($this->pfuser),
            new ChangesetValuesFormatter(
                new ArtifactLinkValueFormatter(),
                new DescriptionValueFormatter(),
                new DateValueFormatter()
            ),
            $this->changeset_creator
        );
    }

    public function testItCreatesANewChangesetInGivenMirroredTimeboxArtifact(): void
    {
        $this->changeset_creator->expects($this->once())
            ->method('create')
            ->with(
                NewChangeset::fromFieldsDataArrayWithEmptyComment(
                    $this->artifact,
                    [
                        self::ARTIFACT_LINK_ID => [
                            'new_values' => '',
                            'types'      => [],
                        ],
                        self::TITLE_ID         => self::TITLE_VALUE,
                        self::DESCRIPTION_ID   => [
                            'content' => self::DESCRIPTION_VALUE,
                            'format'  => self::DESCRIPTION_FORMAT,
                        ],
                        self::STATUS_ID        => [self::MAPPED_STATUS_BIND_VALUE_ID],
                        self::START_DATE_ID    => '2011-02-21',
                        self::END_PERIOD_ID    => '2022-05-21',
                    ],
                    $this->pfuser,
                    self::SUBMISSION_DATE,
                ),
                PostCreationContext::withNoConfig(false)
            );

        $this->getAdder()->addChangeset($this->changeset);
    }

    public static function dataProviderExceptions(): array
    {
        return [
            'with field validation error' => [new FieldValidationException([])],
            'with DB error'               => [new \Tracker_ChangesetNotCreatedException()],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('dataProviderExceptions')]
    public function testItWrapsTrackerException(\Throwable $exception): void
    {
        $this->changeset_creator->method('create')->willThrowException($exception);

        $this->expectException(NewChangesetCreationException::class);
        $this->getAdder()->addChangeset($this->changeset);
    }

    public function testItIgnoresNoChangeException(): void
    {
        $this->changeset_creator->method('create')->willThrowException(
            new \Tracker_NoChangeException(self::TIMEBOX_ID, sprintf('release #%d', self::TIMEBOX_ID))
        );

        $this->expectNotToPerformAssertions();
        $this->getAdder()->addChangeset($this->changeset);
    }

    public function testItCreatesANewChangesetToAddArtifactLink(): void
    {
        $this->changeset_creator->expects($this->once())
            ->method('create')
            ->with(
                new Callback(function (NewChangeset $new_changeset) {
                    if ($new_changeset->getArtifact() !== $this->artifact) {
                        return false;
                    }
                    $expected_fields_data = [
                        self::ARTIFACT_LINK_ID => [
                            'new_values' => (string) self::MIRRORED_ITERATION_ID,
                            'types'      => [
                                (string) self::MIRRORED_ITERATION_ID => \Tuleap\Tracker\FormElement\Field\ArtifactLink\ArtifactLinkField::TYPE_IS_CHILD,
                            ],
                        ],
                    ];
                    if ($new_changeset->getFieldsData() !== $expected_fields_data) {
                        return false;
                    }
                    if ($new_changeset->getSubmitter() !== $this->pfuser) {
                        return false;
                    }
                    return true;
                }),
                PostCreationContext::withNoConfig(false)
            );

        $this->getAdder()->addArtifactLinkChangeset($this->artifact_link_changeset);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('dataProviderExceptions')]
    public function testItWrapsTrackerExceptionForArtifactLink(\Throwable $exception): void
    {
        $this->changeset_creator->method('create')->willThrowException($exception);

        $this->expectException(AddArtifactLinkChangesetException::class);
        $this->getAdder()->addArtifactLinkChangeset($this->artifact_link_changeset);
    }

    public function testItIgnoresNoChangeExceptionForArtifactLink(): void
    {
        $this->changeset_creator->method('create')->willThrowException(
            new \Tracker_NoChangeException(self::MIRRORED_PROGRAM_INCREMENT_ID, sprintf('release #%d', self::MIRRORED_PROGRAM_INCREMENT_ID))
        );

        $this->expectNotToPerformAssertions();
        $this->getAdder()->addArtifactLinkChangeset($this->artifact_link_changeset);
    }
}
