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
use PHPUnit\Framework\TestCase;
use Tracker;
use Tracker_Artifact_Changeset_InitialChangesetCreator;
use Tracker_Artifact_Changeset_InitialChangesetFieldsValidator;
use Tracker_ArtifactDao;
use Tracker_ArtifactFactory;
use Tuleap\Test\DB\DBTransactionExecutorPassthrough;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Artifact\RecentlyVisited\VisitRecorder;
use Tuleap\Tracker\Changeset\Validation\ChangesetValidationContext;
use Tuleap\Tracker\Changeset\Validation\NullChangesetValidationContext;
use Tuleap\Tracker\TrackerColor;

final class TrackerArtifactCreatorTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /** @var \Tracker_Artifact_Changeset_InitialChangesetCreatorBase */
    private $changeset_creator;

    /** @var \Tracker_Artifact_Changeset_FieldsValidator */
    private $fields_validator;

    /** @var Tracker_ArtifactFactory */
    private $artifact_factory;

    /** @var TrackerArtifactCreator */
    private $creator;

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
        $this->artifact_factory  = Tracker_ArtifactFactory::instance();
        $this->changeset_creator = \Mockery::spy(Tracker_Artifact_Changeset_InitialChangesetCreator::class);
        $this->fields_validator  = \Mockery::spy(Tracker_Artifact_Changeset_InitialChangesetFieldsValidator::class);
        $this->dao               = \Mockery::spy(Tracker_ArtifactDao::class);
        $this->visit_recorder    = \Mockery::spy(VisitRecorder::class);

        $this->artifact_factory->setDao($this->dao);

        $this->tracker       = new Tracker(
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

        $this->creator = new TrackerArtifactCreator(
            $this->artifact_factory,
            $this->fields_validator,
            $this->changeset_creator,
            $this->visit_recorder,
            new \Psr\Log\NullLogger(),
            new DBTransactionExecutorPassthrough()
        );
    }

    public function tearDown(): void
    {
        Tracker_ArtifactFactory::clearInstance();
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

        $this->creator->create(
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
        $this->changeset_creator->shouldNotReceive('create');

        $result = $this->creator->create(
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

        $this->creator->create(
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

        $this->changeset_creator->shouldNotReceive('create');

        $result = $this->creator->create(
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

        $this->changeset_creator->shouldReceive('create')
            ->with(
                Mockery::on(function ($artifact) {
                    return $artifact->getId() === $this->bare_artifact->getId() &&
                        $artifact->getSubmittedOn() === $this->bare_artifact->getSubmittedOn() &&
                        $artifact->getSubmittedBy() === $this->bare_artifact->getSubmittedBy();
                }),
                $this->fields_data,
                $this->user,
                $this->submitted_on,
                Mockery::type(\Tuleap\Tracker\FormElement\Field\File\CreatedFileURLMapping::class),
                Mockery::type(\Tuleap\Tracker\Artifact\XMLImport\TrackerNoXMLImportLoggedConfig::class),
                Mockery::type(ChangesetValidationContext::class)
            )
            ->once();

        $this->creator->create(
            $this->tracker,
            $this->fields_data,
            $this->user,
            $this->submitted_on,
            $this->send_notification,
            true,
            new NullChangesetValidationContext()
        );
    }

    public function testItMarksTheArtifactAsVisitedWhenSuccessfullyCreated(): void
    {
        $this->fields_validator->shouldReceive('validate')->andReturns(true);
        $this->dao->shouldReceive('create')->andReturn(1001);
        $this->changeset_creator->shouldReceive('create')->andReturn(1);

        $this->send_notification = false;
        $this->bare_artifact->setId(1001);

        $this->visit_recorder->shouldReceive('record')->once();

        $this->creator->create(
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
        $this->changeset_creator->shouldReceive('create')->andReturn(1);

        $this->send_notification = false;
        $this->bare_artifact->setId(1001);

        $this->visit_recorder->shouldReceive('record')->never();

        $this->creator->create(
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
