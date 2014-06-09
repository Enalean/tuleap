<?php
/**
 * Copyright (c) Enalean, 2013 - 2014. All Rights Reserved.
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

    public function itCreatesANewChangeset(){
        $artifact      = mock('Tracker_Artifact');
        $user          = mock('PFUser');
        $email         = 'whatever';
        $raw_email     = '...';
        $parser        = mock('Tracker_Artifact_MailGateway_Parser');
        $logger        = mock('Logger');
        $recipient     = new Tracker_Artifact_MailGateway_Recipient($user, $artifact, $email);
        $body          = 'justaucorps';
        $stripped_body = 'stripped justaucorps';

        $citation_stripper = stub('Tracker_Artifact_MailGateway_CitationStripper')
            ->stripText($body)->returns($stripped_body);

        $incoming_message = new Tracker_Artifact_MailGateway_IncomingMessage($body, $recipient);
        stub($parser)->parse($raw_email)->returns($incoming_message);

        $mailgateway = new Tracker_Artifact_MailGateway_MailGateway($parser, $citation_stripper, $logger);

        expect($artifact)->createNewChangeset(array(), $stripped_body, $user, '*', '*')->once();

        $mailgateway->process($raw_email);
    }
}
