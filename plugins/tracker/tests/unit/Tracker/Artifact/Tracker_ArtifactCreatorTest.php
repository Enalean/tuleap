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

namespace Tuleap\Tracker\Artifact;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tracker;
use Tracker_Artifact;
use Tracker_Artifact_Changeset_InitialChangesetCreator;
use Tracker_Artifact_Changeset_InitialChangesetFieldsValidator;
use Tracker_ArtifactCreator;
use Tracker_ArtifactDao;
use Tracker_ArtifactFactory;
use Tuleap\DB\DBTransactionExecutor;
use Tuleap\Test\DB\DBTransactionExecutorPassthrough;
use Tuleap\Tracker\Artifact\RecentlyVisited\VisitRecorder;
use Tuleap\Tracker\TrackerColor;

class Tracker_ArtifactCreatorTest extends TestCase // phpcs:ignore
{
    use MockeryPHPUnitIntegration;

    /** @var \Tracker_Artifact_Changeset_InitialChangesetCreatorBase */
    private $changeset_creator;

    /** @var \Tracker_Artifact_Changeset_FieldsValidator */
    private $fields_validator;

    /** @var Tracker_ArtifactFactory */
    private $artifact_factory;

    /** @var Tracker_ArtifactCreator */
    private $creator;

    /** @var \Tracker */
    private $tracker;

    /** @var \PFUser */
    private $user;

    /** @var Tracker_ArtifactDao */
    private $dao;

    /** @var Tracker_Artifact */
    private $bare_artifact;
    /**
     * @var \Tuleap\Tracker\Artifact\RecentlyVisited\VisitRecorder
     */
    private $visit_recorder;

    private $fields_data       = array();
    private $submitted_on      = 1234567890;
    private $send_notification = true;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|DBTransactionExecutor
     */
    private $db_transaction_executor;

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
        $this->bare_artifact = new Tracker_Artifact(0, 123, 101, 1234567890, 0);

        $this->db_transaction_executor = new DBTransactionExecutorPassthrough();

        $this->creator = new Tracker_ArtifactCreator(
            $this->artifact_factory,
            $this->fields_validator,
            $this->changeset_creator,
            $this->visit_recorder,
            new \Psr\Log\NullLogger(),
            $this->db_transaction_executor
        );
    }

    public function tearDown(): void
    {
        Tracker_ArtifactFactory::clearInstance();
    }

    public function testItValidateFields()
    {
        $this->fields_validator->shouldReceive('validate')
            ->with(
                Mockery::on(function ($artifact) {
                    return $artifact->getId() === $this->bare_artifact->getId() &&
                        $artifact->getSubmittedOn() === $this->bare_artifact->getSubmittedOn() &&
                        $artifact->getSubmittedBy() === $this->bare_artifact->getSubmittedBy();
                }),
                $this->user,
                $this->fields_data
            )
            ->once();

        $this->creator->create(
            $this->tracker,
            $this->fields_data,
            $this->user,
            $this->submitted_on,
            $this->send_notification
        );
    }

    public function testItReturnsFalseIfFIeldsAreNotValid()
    {
        $this->fields_validator->shouldReceive('validate')->andReturns(false);

        $this->dao->shouldNotReceive('create');
        $this->changeset_creator->shouldNotReceive('create');

        $result = $this->creator->create(
            $this->tracker,
            $this->fields_data,
            $this->user,
            $this->submitted_on,
            $this->send_notification
        );

        $this->assertFalse($result);
    }

    public function testItCreateArtifactsInDbIfFieldsAreValid()
    {
        $this->fields_validator->shouldReceive('validate')->andReturns(true);

        $this->dao->shouldReceive('create')->with(123, 101, 1234567890, 0)->once();

        $this->creator->create(
            $this->tracker,
            $this->fields_data,
            $this->user,
            $this->submitted_on,
            $this->send_notification
        );
    }

    public function testItReturnsFalseIfCreateArtifactsInDbFails()
    {
        $this->fields_validator->shouldReceive('validate')->andReturns(true);
        $this->dao->shouldReceive('create')->andReturn(false);

        $this->changeset_creator->shouldNotReceive('create');

        $result = $this->creator->create(
            $this->tracker,
            $this->fields_data,
            $this->user,
            $this->submitted_on,
            $this->send_notification
        );

        $this->assertFalse($result);
    }

    public function testItCreateChangesetIfCreateArtifactsInDbSucceeds()
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
                Mockery::type(\Tuleap\Tracker\FormElement\Field\File\CreatedFileURLMapping::class)
            )
            ->once();

        $this->creator->create(
            $this->tracker,
            $this->fields_data,
            $this->user,
            $this->submitted_on,
            $this->send_notification
        );
    }

    public function testItMarksTheArtifactAsVisitedWhenSuccessfullyCreated()
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
            $this->send_notification
        );
    }
}
