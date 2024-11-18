<?php
/**
 * Copyright (c) Enalean, 2013 - Present. All Rights Reserved.
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

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Tuleap\Tracker\Artifact\MailGateway\IncomingMail;

//phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
class MailGatewayTokenTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;

    protected $user;
    protected $mailgateway;
    protected $artifact;
    protected $body          = 'justaucorps';
    protected $stripped_body = 'stripped justaucorps';
    protected $incoming_mail_dao;
    protected $tracker_config;
    protected $tracker;
    protected $incoming_message;
    /**
     * @var \Mockery\MockInterface
     */
    protected $incoming_mail;
    /**
     * @var Tracker_Artifact_MailGateway_IncomingMessageFactory&\Mockery\MockInterface
     */
    private $incoming_message_factory;
    /**
     * @var Tracker_FormElementFactory&\Mockery\MockInterface
     */
    private $formelement_factory;
    /**
     * @var \Psr\Log\LoggerInterface&\Mockery\MockInterface
     */
    private $logger;
    /**
     * @var Tracker_Artifact_MailGateway_Notifier&\Mockery\MockInterface
     */
    private $notifier;
    /**
     * @var \Mockery\MockInterface&Tracker_Artifact_MailGateway_CitationStripper
     */
    private $citation_stripper;
    private \Tuleap\Tracker\Artifact\Creation\TrackerArtifactCreator & \Mockery\MockInterface $artifact_creator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artifact                 = \Mockery::spy(\Tuleap\Tracker\Artifact\Artifact::class);
        $this->user                     = \Mockery::spy(\PFUser::class);
        $this->tracker                  = \Mockery::spy(\Tracker::class);
        $this->incoming_message_factory = \Mockery::spy(\Tracker_Artifact_MailGateway_IncomingMessageFactory::class);
        $this->artifact_creator         = \Mockery::spy(\Tuleap\Tracker\Artifact\Creation\TrackerArtifactCreator::class);
        $this->formelement_factory      = \Mockery::spy(\Tracker_FormElementFactory::class);
        $this->tracker_config           = \Mockery::spy(\Tuleap\Tracker\Artifact\MailGateway\MailGatewayConfig::class);
        $this->logger                   = \Mockery::spy(\Psr\Log\LoggerInterface::class);
        $this->notifier                 = \Mockery::spy(\Tracker_Artifact_MailGateway_Notifier::class);
        $this->incoming_mail_dao        = \Mockery::spy(\Tracker_Artifact_Changeset_IncomingMailDao::class);

        $this->citation_stripper = \Mockery::spy(\Tracker_Artifact_MailGateway_CitationStripper::class)->shouldReceive('stripText')->with($this->body)->andReturns($this->stripped_body)->getMock();

        $this->tracker->shouldReceive('getId')->andReturns(888);

        $this->incoming_message = \Mockery::spy(\Tracker_Artifact_MailGateway_IncomingMessage::class);
        $this->incoming_message->shouldReceive('getUser')->andReturns($this->user);
        $this->incoming_message->shouldReceive('getArtifact')->andReturns($this->artifact);
        $this->incoming_message->shouldReceive('getTracker')->andReturns($this->tracker);
        $this->incoming_message->shouldReceive('getBody')->andReturns($this->body);

        $this->incoming_message_factory->shouldReceive('build')->andReturns($this->incoming_message);

        $this->incoming_mail = Mockery::spy(IncomingMail::class);
        $this->incoming_mail->shouldReceive('getRawMail')->andReturns('Raw mail');

        $filter = \Mockery::spy(\Tuleap\Tracker\Artifact\MailGateway\MailGatewayFilter::class);

        $this->mailgateway = new Tracker_Artifact_MailGateway_TokenMailGateway(
            $this->incoming_message_factory,
            $this->citation_stripper,
            $this->notifier,
            $this->incoming_mail_dao,
            $this->artifact_creator,
            $this->formelement_factory,
            new Tracker_ArtifactByEmailStatus($this->tracker_config),
            $this->logger,
            $filter
        );

        $filter->shouldReceive('isAnAutoReplyMail')->andReturns(false);
    }

    public function testItDoesNotCreateArtifact(): void
    {
        $this->tracker_config->shouldReceive('isInsecureEmailgatewayEnabled')->andReturns(false);
        $this->tracker_config->shouldReceive('isTokenBasedEmailgatewayEnabled')->andReturns(true);
        $this->incoming_message->shouldReceive('isAFollowUp')->andReturns(false);
        $this->artifact_creator->shouldReceive('create')->never();

        $this->mailgateway->process($this->incoming_mail);
    }

    public function testItCreatesANewChangeset(): void
    {
        $this->tracker_config->shouldReceive('isInsecureEmailgatewayEnabled')->andReturns(false);
        $this->tracker_config->shouldReceive('isTokenBasedEmailgatewayEnabled')->andReturns(true);
        $this->incoming_message->shouldReceive('isAFollowUp')->andReturns(true);
        $this->artifact->shouldReceive('userCanUpdate')->with($this->user)->andReturns(true);

        $this->artifact->shouldReceive('createNewChangeset')->with([], $this->stripped_body, $this->user, \Mockery::any(), \Mockery::any())->once();

        $this->mailgateway->process($this->incoming_mail);
    }

    public function testItCreatesANewChangesetEvenIfPlatformIsInInsecureMode(): void
    {
        $this->tracker_config->shouldReceive('isInsecureEmailgatewayEnabled')->andReturns(true);
        $this->tracker->shouldReceive('isEmailgatewayEnabled')->andReturns(true);
        $this->tracker_config->shouldReceive('isTokenBasedEmailgatewayEnabled')->andReturns(false);
        $this->incoming_message->shouldReceive('isAFollowUp')->andReturns(true);
        $this->artifact->shouldReceive('userCanUpdate')->with($this->user)->andReturns(true);

        $this->artifact->shouldReceive('createNewChangeset')->with([], $this->stripped_body, $this->user, \Mockery::any(), \Mockery::any())->once();

        $this->mailgateway->process($this->incoming_mail);
    }

    public function testItCreatesNothingWhenGatewayIsDisabled(): void
    {
        $this->tracker_config->shouldReceive('isInsecureEmailgatewayEnabled')->andReturns(false);
        $this->tracker_config->shouldReceive('isTokenBasedEmailgatewayEnabled')->andReturns(false);
        $this->incoming_message->shouldReceive('isAFollowUp')->andReturns(true);
        $this->artifact->shouldReceive('userCanUpdate')->with($this->user)->andReturns(true);

        $this->artifact->shouldReceive('createNewChangeset')->never();

        $this->mailgateway->process($this->incoming_mail);
    }

    public function testItDoesNotCreateWhenUserCannotUpdate(): void
    {
        $this->tracker_config->shouldReceive('isInsecureEmailgatewayEnabled')->andReturns(false);
        $this->tracker_config->shouldReceive('isTokenBasedEmailgatewayEnabled')->andReturns(true);
        $this->incoming_message->shouldReceive('isAFollowUp')->andReturns(true);
        $this->artifact->shouldReceive('userCanUpdate')->with($this->user)->andReturns(false);

        $this->artifact->shouldReceive('createNewChangeset')->never();

        $this->mailgateway->process($this->incoming_mail);
    }

    public function testItUpdatesArtifact(): void
    {
        $this->tracker_config->shouldReceive('isInsecureEmailgatewayEnabled')->andReturns(false);
        $this->tracker_config->shouldReceive('isTokenBasedEmailgatewayEnabled')->andReturns(true);
        $this->incoming_message->shouldReceive('isAFollowUp')->andReturns(true);
        $this->artifact->shouldReceive('userCanUpdate')->with($this->user)->andReturns(true);

        $this->artifact->shouldReceive('createNewChangeset')->once();

        $this->mailgateway->process($this->incoming_mail);
    }

    public function testItDoesNotUpdateArtifactWhenMailGatewayIsDisabled(): void
    {
        $this->tracker_config->shouldReceive('isInsecureEmailgatewayEnabled')->andReturns(false);
        $this->tracker_config->shouldReceive('isTokenBasedEmailgatewayEnabled')->andReturns(false);
        $this->incoming_message->shouldReceive('isAFollowUp')->andReturns(true);
        $this->artifact->shouldReceive('userCanUpdate')->with($this->user)->andReturns(true);

        $this->artifact->shouldReceive('createNewChangeset')->never();

        $this->mailgateway->process($this->incoming_mail);
    }

    public function testItLinksRawEmailToCreatedChangeset(): void
    {
        $this->tracker_config->shouldReceive('isInsecureEmailgatewayEnabled')->andReturns(false);
        $this->tracker_config->shouldReceive('isTokenBasedEmailgatewayEnabled')->andReturns(true);
        $changeset = \Mockery::spy(\Tracker_Artifact_Changeset::class)->shouldReceive('getId')->andReturns(666)->getMock();
        $this->incoming_message->shouldReceive('isAFollowUp')->andReturns(true);
        $this->artifact->shouldReceive('userCanUpdate')->with($this->user)->andReturns(true);
        $this->artifact->shouldReceive('createNewChangeset')->andReturns($changeset);

        $this->incoming_mail_dao->shouldReceive('save')->with(666, 'Raw mail')->once();

        $this->mailgateway->process($this->incoming_mail);
    }
}
