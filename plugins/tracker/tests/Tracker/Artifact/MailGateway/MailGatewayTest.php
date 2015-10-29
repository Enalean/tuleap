<?php
/**
 * Copyright (c) Enalean, 2013 - 2015. All Rights Reserved.
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

require_once TRACKER_BASE_DIR . '/../tests/bootstrap.php';

class Tracker_Artifact_MailGateway_MailGateway_BaseTest extends TuleapTestCase {

    protected $user;
    protected $mailgateway;
    protected $artifact;
    protected $raw_email     = '...';
    protected $body          = 'justaucorps';
    protected $stripped_body = 'stripped justaucorps';
    protected $incoming_mail_dao;
    protected $tracker_config;
    protected $tracker;
    protected $incoming_message;
    protected $artifact_factory;

    public function setUp() {
        parent::setUp();
        $this->artifact           = mock('Tracker_Artifact');
        $this->user               = mock('PFUser');
        $this->tracker            = mock('Tracker');
        $incoming_message_factory = mock('Tracker_Artifact_MailGateway_IncomingMessageFactory');
        $this->artifact_factory   = mock('Tracker_ArtifactFactory');
        $parser                   = mock('Tracker_Artifact_MailGateway_Parser');
        $this->tracker_config     = mock('TrackerPluginConfig');
        $logger                   = mock('Logger');
        $notifier                 = mock('Tracker_Artifact_MailGateway_Notifier');
        $this->incoming_mail_dao  = mock('Tracker_Artifact_Changeset_IncomingMailDao');

        $citation_stripper = stub('Tracker_Artifact_MailGateway_CitationStripper')
            ->stripText($this->body)
            ->returns($this->stripped_body);

        stub($this->tracker)->isEmailgatewayEnabled()->returns(true);

        $this->incoming_message = mock('Tracker_Artifact_MailGateway_IncomingMessage');
        stub($this->incoming_message)->getUser()->returns($this->user);
        stub($this->incoming_message)->getArtifact()->returns($this->artifact);
        stub($this->incoming_message)->getTracker()->returns($this->tracker);
        stub($this->incoming_message)->getBody()->returns($this->body);

        stub($incoming_message_factory)->build()->returns($this->incoming_message);

        $this->mailgateway = new Tracker_Artifact_MailGateway_MailGateway(
            $parser,
            $incoming_message_factory,
            $citation_stripper,
            $notifier,
            $this->incoming_mail_dao,
            $this->artifact_factory,
            $this->tracker_config,
            $logger
        );
    }
}

class Tracker_Artifact_MailGateway_MailGateway_TokenTest extends Tracker_Artifact_MailGateway_MailGateway_BaseTest {

    public function setUp() {
        parent::setUp();

        stub($this->incoming_message)->isAFollowUp()->returns(true);

        stub($this->tracker_config)->isInsecureEmailgatewayEnabled()->returns(false);
        stub($this->tracker_config)->isTokenBasedEmailgatewayEnabled()->returns(true);
    }

    public function itCreatesANewChangeset() {
        stub($this->artifact)->userCanUpdate($this->user)->returns(true);

        expect($this->artifact)->createNewChangeset(array(), $this->stripped_body, $this->user, '*', '*')->once();

        $this->mailgateway->process($this->raw_email);
    }

    public function itDoesNotCreateWhenUserCannotUpdate() {
        stub($this->artifact)->userCanUpdate($this->user)->returns(false);

        expect($this->artifact)->createNewChangeset()->never();

        $this->mailgateway->process($this->raw_email);
    }

    public function itLinksRawEmailToCreatedChangeset() {
        $changeset = stub('Tracker_Artifact_Changeset')->getId()->returns(666);
        stub($this->artifact)->userCanUpdate($this->user)->returns(true);
        stub($this->artifact)->createNewChangeset()->returns($changeset);

        expect($this->incoming_mail_dao)->save(666, $this->raw_email)->once();

        $this->mailgateway->process($this->raw_email);
    }
}

class Tracker_Artifact_MailGateway_MailGateway_InsecureTest extends Tracker_Artifact_MailGateway_MailGateway_BaseTest {

    private $changeset_id = 666;

    public function setUp() {
        parent::setUp();

        stub($this->tracker_config)->isInsecureEmailgatewayEnabled()->returns(true);
        stub($this->tracker_config)->isTokenBasedEmailgatewayEnabled()->returns(false);

        $title_field       = aStringField()->build();
        $description_field = aTextField()->build();

        stub($this->tracker)->getTitleField()->returns($title_field);
        stub($this->tracker)->getDescriptionField()->returns($description_field);
        stub($this->tracker)->getFormElementFields()->returns(array($title_field, $description_field));

        $this->changeset = stub('Tracker_Artifact_Changeset')->getId()->returns(666);
    }

    public function itLinksRawEmailToCreatedChangeset() {
        $artifact = anArtifact()
            ->withChangesets(array($this->changeset))
            ->withTracker($this->tracker)
            ->build();
        stub($this->incoming_message)->isAFollowUp()->returns(false);
        stub($this->artifact_factory)->createArtifact()->returns($artifact);
        stub($this->tracker)->userCanSubmitArtifact()->returns(true);

        expect($this->incoming_mail_dao)->save(666, $this->raw_email)->once();

        $this->mailgateway->process($this->raw_email);
    }
}
