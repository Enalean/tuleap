<?php
/**
 * Copyright (c) Enalean, 2013 - 2018. All Rights Reserved.
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

use Tuleap\Tracker\Artifact\MailGateway\IncomingMail;

require_once __DIR__.'/../../../bootstrap.php';

class Tracker_Artifact_MailGateway_MailGateway_BaseTest extends TuleapTestCase
{

    protected $user;
    protected $mailgateway;
    protected $artifact;
    protected $body          = 'justaucorps';
    protected $stripped_body = 'stripped justaucorps';
    protected $incoming_mail_dao;
    protected $tracker_config;
    protected $tracker;
    protected $incoming_message;
    protected $artifact_factory;
    /**
     * @var \Mockery\MockInterface
     */
    protected $incoming_mail;

    public function setUp()
    {
        parent::setUp();
        $this->artifact                 = mock('Tracker_Artifact');
        $this->user                     = mock('PFUser');
        $this->tracker                  = mock('Tracker');
        $this->incoming_message_factory = mock('Tracker_Artifact_MailGateway_IncomingMessageFactory');
        $this->artifact_factory         = mock('Tracker_ArtifactFactory');
        $this->formelement_factory      = mock('Tracker_FormElementFactory');
        $this->tracker_config           = mock('Tuleap\Tracker\Artifact\MailGateway\MailGatewayConfig');
        $this->logger                   = mock('Logger');
        $this->notifier                 = mock('Tracker_Artifact_MailGateway_Notifier');
        $this->incoming_mail_dao        = mock('Tracker_Artifact_Changeset_IncomingMailDao');

        $this->citation_stripper = stub('Tracker_Artifact_MailGateway_CitationStripper')
            ->stripText($this->body)
            ->returns($this->stripped_body);

        $this->incoming_message = mock('Tracker_Artifact_MailGateway_IncomingMessage');
        stub($this->incoming_message)->getUser()->returns($this->user);
        stub($this->incoming_message)->getArtifact()->returns($this->artifact);
        stub($this->incoming_message)->getTracker()->returns($this->tracker);
        stub($this->incoming_message)->getBody()->returns($this->body);

        stub($this->incoming_message_factory)->build()->returns($this->incoming_message);

        $this->incoming_mail = Mockery::spy(IncomingMail::class);
        $this->incoming_mail->shouldReceive('getRawMail')->andReturns('Raw mail');
    }
}

class Tracker_Artifact_MailGateway_MailGateway_TokenTest extends Tracker_Artifact_MailGateway_MailGateway_BaseTest
{

    public function setUp()
    {
        parent::setUp();

        $filter = mock('Tuleap\Tracker\Artifact\MailGateway\MailGatewayFilter');

        $this->mailgateway = new Tracker_Artifact_MailGateway_TokenMailGateway(
            $this->incoming_message_factory,
            $this->citation_stripper,
            $this->notifier,
            $this->incoming_mail_dao,
            $this->artifact_factory,
            $this->formelement_factory,
            new Tracker_ArtifactByEmailStatus($this->tracker_config),
            $this->logger,
            $filter
        );

        stub($filter)->isAnAutoReplyMail()->returns(false);
    }

    public function itDoesNotCreateArtifact()
    {
        stub($this->tracker_config)->isInsecureEmailgatewayEnabled()->returns(false);
        stub($this->tracker_config)->isTokenBasedEmailgatewayEnabled()->returns(true);
        stub($this->incoming_message)->isAFollowUp()->returns(false);
        expect($this->artifact_factory)->createArtifact()->never();

        $this->mailgateway->process($this->incoming_mail);
    }

    public function itCreatesANewChangeset()
    {
        stub($this->tracker_config)->isInsecureEmailgatewayEnabled()->returns(false);
        stub($this->tracker_config)->isTokenBasedEmailgatewayEnabled()->returns(true);
        stub($this->incoming_message)->isAFollowUp()->returns(true);
        stub($this->artifact)->userCanUpdate($this->user)->returns(true);

        expect($this->artifact)->createNewChangeset(array(), $this->stripped_body, $this->user, '*', '*')->once();

        $this->mailgateway->process($this->incoming_mail);
    }

    public function itCreatesANewChangesetEvenIfPlatformIsInInsecureMode()
    {
        stub($this->tracker_config)->isInsecureEmailgatewayEnabled()->returns(true);
        stub($this->tracker)->isEmailgatewayEnabled()->returns(true);
        stub($this->tracker_config)->isTokenBasedEmailgatewayEnabled()->returns(false);
        stub($this->incoming_message)->isAFollowUp()->returns(true);
        stub($this->artifact)->userCanUpdate($this->user)->returns(true);

        expect($this->artifact)->createNewChangeset(array(), $this->stripped_body, $this->user, '*', '*')->once();

        $this->mailgateway->process($this->incoming_mail);
    }

    public function itCreatesNothingWhenGatewayIsDisabled()
    {
        stub($this->tracker_config)->isInsecureEmailgatewayEnabled()->returns(false);
        stub($this->tracker_config)->isTokenBasedEmailgatewayEnabled()->returns(false);
        stub($this->incoming_message)->isAFollowUp()->returns(true);
        stub($this->artifact)->userCanUpdate($this->user)->returns(true);

        expect($this->artifact)->createNewChangeset()->never();

        $this->mailgateway->process($this->incoming_mail);
    }

    public function itDoesNotCreateWhenUserCannotUpdate()
    {
        stub($this->tracker_config)->isInsecureEmailgatewayEnabled()->returns(false);
        stub($this->tracker_config)->isTokenBasedEmailgatewayEnabled()->returns(true);
        stub($this->incoming_message)->isAFollowUp()->returns(true);
        stub($this->artifact)->userCanUpdate($this->user)->returns(false);

        expect($this->artifact)->createNewChangeset()->never();

        $this->mailgateway->process($this->incoming_mail);
    }

    public function itUpdatesArtifact()
    {
        stub($this->tracker_config)->isInsecureEmailgatewayEnabled()->returns(false);
        stub($this->tracker_config)->isTokenBasedEmailgatewayEnabled()->returns(true);
        stub($this->incoming_message)->isAFollowUp()->returns(true);
        stub($this->artifact)->userCanUpdate($this->user)->returns(true);

        expect($this->artifact)->createNewChangeset()->once();

        $this->mailgateway->process($this->incoming_mail);
    }

    public function itDoesNotUpdateArtifactWhenMailGatewayIsDisabled()
    {
        stub($this->tracker_config)->isInsecureEmailgatewayEnabled()->returns(false);
        stub($this->tracker_config)->isTokenBasedEmailgatewayEnabled()->returns(false);
        stub($this->incoming_message)->isAFollowUp()->returns(true);
        stub($this->artifact)->userCanUpdate($this->user)->returns(true);

        expect($this->artifact)->createNewChangeset()->never();

        $this->mailgateway->process($this->incoming_mail);
    }

    public function itLinksRawEmailToCreatedChangeset()
    {
        stub($this->tracker_config)->isInsecureEmailgatewayEnabled()->returns(false);
        stub($this->tracker_config)->isTokenBasedEmailgatewayEnabled()->returns(true);
        $changeset = stub('Tracker_Artifact_Changeset')->getId()->returns(666);
        stub($this->incoming_message)->isAFollowUp()->returns(true);
        stub($this->artifact)->userCanUpdate($this->user)->returns(true);
        stub($this->artifact)->createNewChangeset()->returns($changeset);

        expect($this->incoming_mail_dao)->save(666, 'Raw mail')->once();

        $this->mailgateway->process($this->incoming_mail);
    }
}

class Tracker_Artifact_MailGateway_MailGateway_InsecureTest extends Tracker_Artifact_MailGateway_MailGateway_BaseTest
{

    public function setUp()
    {
        parent::setUp();

        $title_field       = aStringField()->build();
        $description_field = aTextField()->build();

        stub($this->tracker)->getTitleField()->returns($title_field);
        stub($this->tracker)->getDescriptionField()->returns($description_field);
        stub($this->tracker)->getFormElementFields()->returns(array($title_field, $description_field));

        $this->changeset = stub('Tracker_Artifact_Changeset')->getId()->returns(666);
        $filter = mock('Tuleap\Tracker\Artifact\MailGateway\MailGatewayFilter');

        $this->mailgateway = new Tracker_Artifact_MailGateway_InsecureMailGateway(
            $this->incoming_message_factory,
            $this->citation_stripper,
            $this->notifier,
            $this->incoming_mail_dao,
            $this->artifact_factory,
            $this->formelement_factory,
            new Tracker_ArtifactByEmailStatus($this->tracker_config),
            $this->logger,
            $filter
        );
        stub($filter)->isAnAutoReplyMail()->returns(false);
    }

    public function itUpdatesArtifact()
    {
        stub($this->tracker_config)->isInsecureEmailgatewayEnabled()->returns(true);
        stub($this->tracker_config)->isTokenBasedEmailgatewayEnabled()->returns(false);
        stub($this->tracker)->isEmailgatewayEnabled()->returns(true);
        stub($this->incoming_message)->isAFollowUp()->returns(true);
        stub($this->artifact)->userCanUpdate($this->user)->returns(true);

        expect($this->artifact)->createNewChangeset(array(), $this->stripped_body, $this->user, '*', '*')->once();

        $this->mailgateway->process($this->incoming_mail);
    }

    public function itDoesNotUpdatesArtifactWhenGatewayIsDisabled()
    {
        stub($this->tracker_config)->isInsecureEmailgatewayEnabled()->returns(true);
        stub($this->tracker_config)->isTokenBasedEmailgatewayEnabled()->returns(false);
        stub($this->tracker)->isEmailgatewayEnabled()->returns(false);
        stub($this->incoming_message)->isAFollowUp()->returns(true);
        stub($this->artifact)->userCanUpdate($this->user)->returns(true);

        expect($this->artifact)->createNewChangeset()->never();

        $this->mailgateway->process($this->incoming_mail);
    }

    public function itCreatesArtifact()
    {
        stub($this->tracker_config)->isInsecureEmailgatewayEnabled()->returns(true);
        stub($this->tracker_config)->isTokenBasedEmailgatewayEnabled()->returns(false);
        stub($this->tracker)->isEmailgatewayEnabled()->returns(true);
        stub($this->incoming_message)->isAFollowUp()->returns(false);
        stub($this->tracker)->userCanSubmitArtifact()->returns(true);

        expect($this->artifact_factory)->createArtifact()->once();

        $this->mailgateway->process($this->incoming_mail);
    }

    public function itUsesDefaultValuesForFields()
    {
        stub($this->tracker_config)->isInsecureEmailgatewayEnabled()->returns(true);
        stub($this->tracker_config)->isTokenBasedEmailgatewayEnabled()->returns(false);
        stub($this->tracker)->isEmailgatewayEnabled()->returns(true);
        stub($this->incoming_message)->isAFollowUp()->returns(false);
        stub($this->tracker)->userCanSubmitArtifact()->returns(true);

        expect($this->formelement_factory)->getUsedFieldsWithDefaultValue(
            $this->tracker,
            '*',
            $this->user
        )->once();

        $this->mailgateway->process($this->incoming_mail);
    }

    public function itDoesNotCreateArtifactWhenGatewayIsDisabled()
    {
        stub($this->tracker_config)->isInsecureEmailgatewayEnabled()->returns(true);
        stub($this->tracker_config)->isTokenBasedEmailgatewayEnabled()->returns(false);
        stub($this->tracker)->isEmailgatewayEnabled()->returns(false);
        stub($this->incoming_message)->isAFollowUp()->returns(false);
        stub($this->tracker)->userCanSubmitArtifact()->returns(true);

        expect($this->artifact_factory)->createArtifact()->never();

        $this->mailgateway->process($this->incoming_mail);
    }

    public function itLinksRawEmailToCreatedChangeset()
    {
        stub($this->tracker_config)->isInsecureEmailgatewayEnabled()->returns(true);
        stub($this->tracker_config)->isTokenBasedEmailgatewayEnabled()->returns(false);
        $artifact = anArtifact()
            ->withChangesets(array($this->changeset))
            ->withTracker($this->tracker)
            ->build();
        stub($this->tracker)->isEmailgatewayEnabled()->returns(true);
        stub($this->incoming_message)->isAFollowUp()->returns(false);
        stub($this->artifact_factory)->createArtifact()->returns($artifact);
        stub($this->tracker)->userCanSubmitArtifact()->returns(true);

        expect($this->incoming_mail_dao)->save(666, 'Raw mail')->once();

        $this->mailgateway->process($this->incoming_mail);
    }
}
