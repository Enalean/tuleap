<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\Tracker\Artifact\Creation;

use PHPUnit\Framework\MockObject\MockObject;
use Psr\EventDispatcher\EventDispatcherInterface;
use Tracker;
use Tracker_Artifact_Changeset;
use Tracker_Artifact_Changeset_InitialChangesetFieldsValidator;
use Tracker_ArtifactDao;
use Tracker_ArtifactFactory;
use Tuleap\GlobalLanguageMock;
use Tuleap\Option\Option;
use Tuleap\Test\DB\DBTransactionExecutorPassthrough;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Artifact\Changeset\CreateInitialChangeset;
use Tuleap\Tracker\Artifact\ChangesetValue\ArtifactLink\NewArtifactLinkInitialChangesetValue;
use Tuleap\Tracker\Artifact\ChangesetValue\InitialChangesetValuesContainer;
use Tuleap\Tracker\Artifact\RecentlyVisited\VisitRecorder;
use Tuleap\Tracker\Changeset\Validation\NullChangesetValidationContext;
use Tuleap\Tracker\Test\Stub\CreateInitialChangesetStub;
use Tuleap\Tracker\Test\Stub\Tracker\Artifact\Creation\AddReverseLinksStub;
use Tuleap\Tracker\TrackerColor;

final class TrackerArtifactCreatorTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use GlobalLanguageMock;

    /** @var \Tracker_Artifact_Changeset_FieldsValidator */
    private $fields_validator;

    /** @var Tracker_ArtifactFactory */
    private $artifact_factory;

    /** @var \Tracker */
    private $tracker;

    /** @var \PFUser */
    private $user;

    /** @var Tracker_ArtifactDao */
    private $dao;

    /** @var Artifact */
    private $bare_artifact;
    /**
     * @var \Tuleap\Tracker\Artifact\RecentlyVisited\VisitRecorder
     */
    private $visit_recorder;

    private $fields_data       = [];
    private $submitted_on      = 1234567890;
    private $send_notification = true;
    private EventDispatcherInterface|MockObject $event_dispatcher;

    public function setUp(): void
    {
        Tracker_ArtifactFactory::clearInstance();
        $this->artifact_factory = Tracker_ArtifactFactory::instance();
        $this->fields_validator = $this->createMock(Tracker_Artifact_Changeset_InitialChangesetFieldsValidator::class);
        $this->dao              = $this->createMock(Tracker_ArtifactDao::class);
        $this->visit_recorder   = $this->createMock(VisitRecorder::class);

        $this->artifact_factory->setDao($this->dao);

        $this->tracker = new Tracker(
            123,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            TrackerColor::default(),
            null
        );

        $this->user          = new \PFUser(['user_id' => 101, 'language_id' => 'en_US']);
        $this->bare_artifact = new Artifact(0, 123, 101, 1234567890, 0);

        $this->event_dispatcher = $this->createMock(EventDispatcherInterface::class);
    }

    public function tearDown(): void
    {
        Tracker_ArtifactFactory::clearInstance();
    }

    private function getCreator(
        CreateInitialChangeset $create_initial_changeset_stub,
        AddReverseLinks $reverse_links,
    ): MockObject&TrackerArtifactCreator {
        $changeset = $this->createMock(Tracker_Artifact_Changeset::class);
        $changeset->method("executePostCreationActions");

        $creator = $this->getMockBuilder(TrackerArtifactCreator::class)
            ->onlyMethods(['createNewChangeset'])
            ->setConstructorArgs([
                $this->artifact_factory,
                $this->fields_validator,
                $create_initial_changeset_stub,
                $this->visit_recorder,
                new \Psr\Log\NullLogger(),
                new DBTransactionExecutorPassthrough(),
                $this->event_dispatcher,
                $reverse_links,
            ])->getMock();

        $creator->method("createNewChangeset")->willReturn(
            $changeset
        );

        return $creator;
    }

    public function testItValidateFields(): void
    {
        $context = new NullChangesetValidationContext();
        $this->fields_validator->expects(self::once())
            ->method('validate')
            ->with(
                self::callback(
                    function ($artifact) {
                        return $artifact->getId() === $this->bare_artifact->getId() &&
                            $artifact->getSubmittedOn() === $this->bare_artifact->getSubmittedOn() &&
                            $artifact->getSubmittedBy() === $this->bare_artifact->getSubmittedBy();
                    }
                ),
                $this->user,
                $this->fields_data,
                $context
            );

        $artifact_creator = $this->getCreator(
            CreateInitialChangesetStub::withChangesetCreationExpected(),
            AddReverseLinksStub::build(),
        );
        $artifact_creator->create(
            $this->tracker,
            new InitialChangesetValuesContainer($this->fields_data, Option::nothing(NewArtifactLinkInitialChangesetValue::class)),
            $this->user,
            $this->submitted_on,
            $this->send_notification,
            true,
            $context,
            false,
        );
    }

    public function testItReturnsNullIfFieldsAreNotValid(): void
    {
        $this->fields_validator->method('validate')->willReturn(false);

        $this->dao->expects(self::never())->method('create');

        $artifact_creator = $this->getCreator(
            CreateInitialChangesetStub::withNoChangesetCreationExpected(),
            AddReverseLinksStub::build(),
        );
        $result           = $artifact_creator->create(
            $this->tracker,
            new InitialChangesetValuesContainer($this->fields_data, Option::nothing(NewArtifactLinkInitialChangesetValue::class)),
            $this->user,
            $this->submitted_on,
            $this->send_notification,
            true,
            new NullChangesetValidationContext(),
            false,
        );

        self::assertNull($result);
    }

    public function testItCreateArtifactsInDbIfFieldsAreValid(): void
    {
        $this->fields_validator->method('validate')->willReturn(true);

        $this->dao->expects(self::once())->method('create')->with(123, 101, 1234567890, 0);

        $artifact_creator = $this->getCreator(
            CreateInitialChangesetStub::withChangesetCreationExpected(),
            AddReverseLinksStub::build(),
        );
        $artifact_creator->create(
            $this->tracker,
            new InitialChangesetValuesContainer($this->fields_data, Option::nothing(NewArtifactLinkInitialChangesetValue::class)),
            $this->user,
            $this->submitted_on,
            $this->send_notification,
            true,
            new NullChangesetValidationContext(),
            false,
        );
    }

    public function testItDoesNotAskToAddReverseLinks(): void
    {
        $this->fields_validator->method('validate')->willReturn(true);

        $this->dao->method('create')->willReturn(101);
        $this->event_dispatcher->method('dispatch');
        $this->visit_recorder->method('record');

        $reverse_links = AddReverseLinksStub::build();

        $artifact_creator = $this->getCreator(
            CreateInitialChangesetStub::withChangesetCreationExpected(),
            $reverse_links,
        );
        $artifact_creator->create(
            $this->tracker,
            new InitialChangesetValuesContainer($this->fields_data, Option::nothing(NewArtifactLinkInitialChangesetValue::class)),
            $this->user,
            $this->submitted_on,
            $this->send_notification,
            true,
            new NullChangesetValidationContext(),
            false,
        );

        self::assertFalse($reverse_links->hasBeenCalled());
    }

    public function testAsksToAddReverseLinks(): void
    {
        $this->fields_validator->method('validate')->willReturn(true);

        $this->dao->method('create')->willReturn(101);
        $this->event_dispatcher->method('dispatch');
        $this->visit_recorder->method('record');

        $reverse_links = AddReverseLinksStub::build();

        $artifact_creator = $this->getCreator(
            CreateInitialChangesetStub::withChangesetCreationExpected(),
            $reverse_links,
        );
        $artifact_creator->create(
            $this->tracker,
            new InitialChangesetValuesContainer($this->fields_data, Option::nothing(NewArtifactLinkInitialChangesetValue::class)),
            $this->user,
            $this->submitted_on,
            $this->send_notification,
            true,
            new NullChangesetValidationContext(),
            true,
        );

        self::assertTrue($reverse_links->hasBeenCalled());
    }

    public function testItReturnsNullIfCreateArtifactsInDbFails(): void
    {
        $this->fields_validator->method('validate')->willReturn(true);
        $this->dao->method('create')->willReturn(false);

        $artifact_creator = $this->getCreator(
            CreateInitialChangesetStub::withNoChangesetCreationExpected(),
            AddReverseLinksStub::build(),
        );
        $result           = $artifact_creator->create(
            $this->tracker,
            new InitialChangesetValuesContainer($this->fields_data, Option::nothing(NewArtifactLinkInitialChangesetValue::class)),
            $this->user,
            $this->submitted_on,
            $this->send_notification,
            true,
            new NullChangesetValidationContext(),
            false,
        );

        self::assertNull($result);
    }

    public function testItCreateChangesetIfCreateArtifactsInDbSucceeds(): void
    {
        $this->send_notification = false;
        $this->fields_validator->method('validate')->willReturn(true);
        $this->dao->method('create')->willReturn(1001);

        $this->bare_artifact->setId(1001);

        $this->event_dispatcher->expects(self::once())->method('dispatch');
        $this->visit_recorder->expects(self::once())->method('record');

        $create_initial_changeset_stub = CreateInitialChangesetStub::withChangesetCreationExpected();
        $this->getCreator(
            $create_initial_changeset_stub,
            AddReverseLinksStub::build(),
        )->create(
            $this->tracker,
            new InitialChangesetValuesContainer($this->fields_data, Option::nothing(NewArtifactLinkInitialChangesetValue::class)),
            $this->user,
            $this->submitted_on,
            $this->send_notification,
            true,
            new NullChangesetValidationContext(),
            false,
        );

        self::assertEquals(1, $create_initial_changeset_stub->getCallCount());
    }

    public function testItMarksTheArtifactAsVisitedWhenSuccessfullyCreated(): void
    {
        $this->fields_validator->method('validate')->willReturn(true);
        $this->dao->method('create')->willReturn(1001);

        $this->send_notification = false;
        $this->bare_artifact->setId(1001);

        $this->visit_recorder->expects(self::once())->method('record');
        $this->event_dispatcher->expects(self::once())->method('dispatch');

        $artifact_creator = $this->getCreator(
            CreateInitialChangesetStub::withChangesetCreationExpected(),
            AddReverseLinksStub::build(),
        );
        $artifact_creator->create(
            $this->tracker,
            new InitialChangesetValuesContainer($this->fields_data, Option::nothing(NewArtifactLinkInitialChangesetValue::class)),
            $this->user,
            $this->submitted_on,
            $this->send_notification,
            true,
            new NullChangesetValidationContext(),
            false,
        );
    }

    public function testItDoesNotMarksTheArtifactAsVisitedWhenNotNeeded(): void
    {
        $this->fields_validator->method('validate')->willReturn(true);
        $this->dao->method('create')->willReturn(1001);

        $this->send_notification = false;
        $this->bare_artifact->setId(1001);

        $this->visit_recorder->expects(self::never())->method('record');

        $this->event_dispatcher->expects(self::once())->method('dispatch');

        $artifact_creator = $this->getCreator(
            CreateInitialChangesetStub::withChangesetCreationExpected(),
            AddReverseLinksStub::build(),
        );
        $artifact_creator->create(
            $this->tracker,
            new InitialChangesetValuesContainer($this->fields_data, Option::nothing(NewArtifactLinkInitialChangesetValue::class)),
            $this->user,
            $this->submitted_on,
            $this->send_notification,
            false,
            new NullChangesetValidationContext(),
            false,
        );
    }
}
