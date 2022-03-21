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

namespace Tuleap\Tracker\Artifact\Creation;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Tracker;
use Tracker_Artifact_Changeset;
use Tracker_Artifact_Changeset_InitialChangesetFieldsValidator;
use Tracker_ArtifactDao;
use Tracker_ArtifactFactory;
use Tuleap\GlobalLanguageMock;
use Tuleap\Test\DB\DBTransactionExecutorPassthrough;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Artifact\Changeset\CreateInitialChangeset;
use Tuleap\Tracker\Artifact\RecentlyVisited\VisitRecorder;
use Tuleap\Tracker\Changeset\Validation\NullChangesetValidationContext;
use Tuleap\Tracker\Test\Stub\CreateInitialChangesetStub;
use Tuleap\Tracker\TrackerColor;

final class TrackerArtifactCreatorTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;
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

    public function setUp(): void
    {
        Tracker_ArtifactFactory::clearInstance();
        $this->artifact_factory = Tracker_ArtifactFactory::instance();
        $this->fields_validator = \Mockery::spy(Tracker_Artifact_Changeset_InitialChangesetFieldsValidator::class);
        $this->dao              = \Mockery::spy(Tracker_ArtifactDao::class);
        $this->visit_recorder   = \Mockery::spy(VisitRecorder::class);

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
    }

    public function tearDown(): void
    {
        Tracker_ArtifactFactory::clearInstance();
    }

    /**
     * @return Mockery\Mock & TrackerArtifactCreator
     */
    private function getCreator(CreateInitialChangeset $create_initial_changeset_stub)
    {
        $changeset = Mockery::mock(Tracker_Artifact_Changeset::class);
        $changeset->shouldReceive("executePostCreationActions");

        $creator = Mockery::mock(TrackerArtifactCreator::class, [
            $this->artifact_factory,
            $this->fields_validator,
            $create_initial_changeset_stub,
            $this->visit_recorder,
            new \Psr\Log\NullLogger(),
            new DBTransactionExecutorPassthrough(),
        ])->makePartial()->shouldAllowMockingProtectedMethods();


        $creator->shouldReceive("createNewChangeset")->andReturn(
            $changeset
        );

        return $creator;
    }

    public function testItValidateFields(): void
    {
        $context = new NullChangesetValidationContext();
        $this->fields_validator->shouldReceive('validate')
            ->with(
                Mockery::on(
                    function ($artifact) {
                        return $artifact->getId() === $this->bare_artifact->getId() &&
                            $artifact->getSubmittedOn() === $this->bare_artifact->getSubmittedOn() &&
                            $artifact->getSubmittedBy() === $this->bare_artifact->getSubmittedBy();
                    }
                ),
                $this->user,
                $this->fields_data,
                $context
            )
            ->once();

        $artifact_creator = $this->getCreator(CreateInitialChangesetStub::withChangesetCreationExpected());
        $artifact_creator->create(
            $this->tracker,
            $this->fields_data,
            $this->user,
            $this->submitted_on,
            $this->send_notification,
            true,
            $context
        );
    }

    public function testItReturnsNullIfFieldsAreNotValid(): void
    {
        $this->fields_validator->shouldReceive('validate')->andReturns(false);

        $this->dao->shouldNotReceive('create');

        $artifact_creator = $this->getCreator(CreateInitialChangesetStub::withNoChangesetCreationExpected());
        $result           = $artifact_creator->create(
            $this->tracker,
            $this->fields_data,
            $this->user,
            $this->submitted_on,
            $this->send_notification,
            true,
            new NullChangesetValidationContext()
        );

        $this->assertNull($result);
    }

    public function testItCreateArtifactsInDbIfFieldsAreValid(): void
    {
        $this->fields_validator->shouldReceive('validate')->andReturns(true);

        $this->dao->shouldReceive('create')->with(123, 101, 1234567890, 0)->once();

        $artifact_creator = $this->getCreator(CreateInitialChangesetStub::withChangesetCreationExpected());
        $artifact_creator->create(
            $this->tracker,
            $this->fields_data,
            $this->user,
            $this->submitted_on,
            $this->send_notification,
            true,
            new NullChangesetValidationContext()
        );
    }

    public function testItReturnsNullIfCreateArtifactsInDbFails(): void
    {
        $this->fields_validator->shouldReceive('validate')->andReturns(true);
        $this->dao->shouldReceive('create')->andReturn(false);

        $artifact_creator = $this->getCreator(CreateInitialChangesetStub::withNoChangesetCreationExpected());
        $result           = $artifact_creator->create(
            $this->tracker,
            $this->fields_data,
            $this->user,
            $this->submitted_on,
            $this->send_notification,
            true,
            new NullChangesetValidationContext()
        );

        $this->assertNull($result);
    }

    public function testItCreateChangesetIfCreateArtifactsInDbSucceeds(): void
    {
        $this->send_notification = false;
        $this->fields_validator->shouldReceive('validate')->andReturns(true);
        $this->dao->shouldReceive('create')->andReturn(1001);

        $this->bare_artifact->setId(1001);

        $create_initial_changeset_stub = CreateInitialChangesetStub::withChangesetCreationExpected();
        $this->getCreator($create_initial_changeset_stub)->create(
            $this->tracker,
            $this->fields_data,
            $this->user,
            $this->submitted_on,
            $this->send_notification,
            true,
            new NullChangesetValidationContext()
        );

        self::assertEquals(1, $create_initial_changeset_stub->getCallCount());
    }

    public function testItMarksTheArtifactAsVisitedWhenSuccessfullyCreated(): void
    {
        $this->fields_validator->shouldReceive('validate')->andReturns(true);
        $this->dao->shouldReceive('create')->andReturn(1001);

        $this->send_notification = false;
        $this->bare_artifact->setId(1001);

        $this->visit_recorder->shouldReceive('record')->once();

        $artifact_creator = $this->getCreator(CreateInitialChangesetStub::withChangesetCreationExpected());
        $artifact_creator->create(
            $this->tracker,
            $this->fields_data,
            $this->user,
            $this->submitted_on,
            $this->send_notification,
            true,
            new NullChangesetValidationContext()
        );
    }

    public function testItDoesNotMarksTheArtifactAsVisitedWhenNotNeeded(): void
    {
        $this->fields_validator->shouldReceive('validate')->andReturns(true);
        $this->dao->shouldReceive('create')->andReturn(1001);

        $this->send_notification = false;
        $this->bare_artifact->setId(1001);

        $this->visit_recorder->shouldReceive('record')->never();

        $artifact_creator = $this->getCreator(CreateInitialChangesetStub::withChangesetCreationExpected());
        $artifact_creator->create(
            $this->tracker,
            $this->fields_data,
            $this->user,
            $this->submitted_on,
            $this->send_notification,
            false,
            new NullChangesetValidationContext()
        );
    }
}
