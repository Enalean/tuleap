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

class Tracker_Artifact_MailGateway_MailGatewayTest extends TuleapTestCase {

    private $user;
    private $mailgateway;
    private $artifact;
    private $raw_email     = '...';
    private $body          = 'justaucorps';
    private $stripped_body = 'stripped justaucorps';

    public function setUp() {
        parent::setUp();
        $this->artifact           = mock('Tracker_Artifact');
        $this->user               = mock('PFUser');
        $tracker                  = mock('Tracker');
        $incoming_message_factory = mock('Tracker_Artifact_MailGateway_IncomingMessageFactory');
        $artifact_factory         = mock('Tracker_ArtifactFactory');
        $parser                   = mock('Tracker_Artifact_MailGateway_Parser');
        $tracker_config           = mock('TrackerPluginConfig');
        $logger                   = mock('Logger');
        $notifier                 = mock('Tracker_Artifact_MailGateway_Notifier');

        $citation_stripper = stub('Tracker_Artifact_MailGateway_CitationStripper')
            ->stripText($this->body)
            ->returns($this->stripped_body);

        $tracker->setReturnValue('isEmailgatewayEnabled', true);

        $incoming_message = mock('Tracker_Artifact_MailGateway_IncomingMessage');
        $incoming_message->setReturnValue('isAFollowUp', true);
        $incoming_message->setReturnValue('getUser', $this->user);
        $incoming_message->setReturnValue('getArtifact', $this->artifact);
        $incoming_message->setReturnValue('getTracker', $tracker);
        $incoming_message->setReturnValue('getBody', $this->body);

        $incoming_message_factory->setReturnValue('build', $incoming_message);

        $tracker_config->setReturnValue('isInsecureEmailgatewayEnabled', false);
        $tracker_config->setReturnValue('isTokenBasedEmailgatewayEnabled', true);

        $this->mailgateway = new Tracker_Artifact_MailGateway_MailGateway(
            $parser,
            $incoming_message_factory,
            $citation_stripper,
            $notifier,
            $artifact_factory,
            $tracker_config,
            $logger
        );
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
}
