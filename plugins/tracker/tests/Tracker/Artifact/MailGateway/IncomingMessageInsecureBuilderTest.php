<?php
/**
 * Copyright (c) Enalean, 2015. All Rights Reserved.
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

require_once __DIR__.'/../../../bootstrap.php';

class Tracker_Artifact_IncomingMessageInsecureBuilderTest extends TuleapTestCase {
    const USER_MAIL     = 'user@example.com';
    const TRACKER_ID    = 1;
    const TRACKER_MAIL  = 'forge__tracker+1@example.com';
    const ARTIFACT_ID   = 1;
    const ARTIFACT_MAIL = 'forge__artifact+1@example.com';

    private $user_manager;
    private $tracker_factory;
    private $artifact_factory;

    public function setUp() {
        $this->user_manager     = mock('UserManager');
        $this->tracker_factory  = mock('TrackerFactory');
        $this->artifact_factory = mock('Tracker_ArtifactFactory');
    }

    public function itDoesNotAcceptInvalidFromHeader() {
        stub($this->tracker_factory)->getTrackerById(self::TRACKER_ID)->returns(mock('Tracker'));
        stub($this->user_manager)->getAllUsersByEmail(self::USER_MAIL)->returns(array(mock('PFUser')));

        $incoming_message_builder = new Tracker_Artifact_IncomingMessageInsecureBuilder(
            $this->user_manager,
            $this->tracker_factory,
            $this->artifact_factory
        );

        $raw_mail = array(
            'headers' => array(
                'from'    => '',
                'to'      => '',
                'subject' => ''
            ),
            'body'    => ''
        );

        $this->expectException('Tracker_Artifact_MailGateway_InvalidMailHeadersException');
        $incoming_message_builder->build($raw_mail);
    }

    public function itDoesNotAcceptInvalidToHeader() {
        stub($this->tracker_factory)->getTrackerById(self::TRACKER_ID)->returns(mock('Tracker'));
        stub($this->user_manager)->getAllUsersByEmail(self::USER_MAIL)->returns(array(mock('PFUser')));

        $incoming_message_builder = new Tracker_Artifact_IncomingMessageInsecureBuilder(
            $this->user_manager,
            $this->tracker_factory,
            $this->artifact_factory
        );

        $raw_mail = array(
            'headers' => array(
                'from'    => self::USER_MAIL,
                'to'      => '',
                'subject' => ''
            ),
            'body'    => ''
        );

        $raw_mail['headers']['to'] = trackerPlugin::EMAILGATEWAY_INSECURE_ARTIFACT_CREATION . '@example.com';
        try {
            $incoming_message_builder->build($raw_mail);
            $this->fail();
        } catch (Tracker_Artifact_MailGateway_TrackerIdMissingException $e) {}
    }

    public function itFindsUserAndTrackerToHeader() {
        stub($this->tracker_factory)->getTrackerById(self::TRACKER_ID)->returns(mock('Tracker'));
        stub($this->user_manager)->getAllUsersByEmail(self::USER_MAIL)->returns(array(mock('PFUser')));

        $incoming_message_builder = new Tracker_Artifact_IncomingMessageInsecureBuilder(
            $this->user_manager,
            $this->tracker_factory,
            $this->artifact_factory
        );

        $raw_mail = array(
            'headers' => array(
                'from'    => self::USER_MAIL . ' (User Name)',
                'to'      => self::TRACKER_MAIL,
                'subject' => ''
            ),
            'body'    => ''
        );

        $incoming_message = $incoming_message_builder->build($raw_mail);
        $user             = $incoming_message->getUser();
        $tracker          = $incoming_message->getTracker();
        $this->assertNotNull($user);
        $this->assertNotNull($tracker);
    }

    public function itFindsUserAndTrackerCcHeader() {
        stub($this->tracker_factory)->getTrackerById(self::TRACKER_ID)->returns(mock('Tracker'));
        stub($this->user_manager)->getAllUsersByEmail(self::USER_MAIL)->returns(array(mock('PFUser')));

        $incoming_message_builder = new Tracker_Artifact_IncomingMessageInsecureBuilder(
            $this->user_manager,
            $this->tracker_factory,
            $this->artifact_factory
        );

        $raw_mail = array(
            'headers' => array(
                'from'    => self::USER_MAIL . ' (User Name)',
                'to'      => '',
                'cc'      => self::TRACKER_MAIL,
                'subject' => ''
            ),
            'body'    => ''
        );

        $incoming_message = $incoming_message_builder->build($raw_mail);
        $user             = $incoming_message->getUser();
        $tracker          = $incoming_message->getTracker();
        $this->assertNotNull($user);
        $this->assertNotNull($tracker);
    }

    public function itFindsUserAndTrackerMultipleUsers() {
        stub($this->tracker_factory)->getTrackerById(self::TRACKER_ID)->returns(mock('Tracker'));
        stub($this->user_manager)->getAllUsersByEmail(self::USER_MAIL)->returns(array(mock('PFUser')));
        $incoming_message_builder = new Tracker_Artifact_IncomingMessageInsecureBuilder(
            $this->user_manager,
            $this->tracker_factory,
            $this->artifact_factory
        );

        $raw_mail = array(
            'headers' => array(
                'from'    => self::USER_MAIL . ' (User Name)',
                'to'      => self::TRACKER_MAIL  . ',' . 'unknown@example.com',
                'subject' => ''
            ),
            'body'    => ''
        );

        $incoming_message = $incoming_message_builder->build($raw_mail);
        $user             = $incoming_message->getUser();
        $tracker          = $incoming_message->getTracker();
        $this->assertNotNull($user);
        $this->assertNotNull($tracker);
    }

    public function itFindsArtifactToHeader() {
        $artifact_mock = stub('Tracker_Artifact')->getTracker()->returns(mock('Tracker'));
        stub($this->artifact_factory)->getArtifactById(self::ARTIFACT_ID)->returns($artifact_mock);
        stub($this->user_manager)->getAllUsersByEmail(self::USER_MAIL)->returns(array(mock('PFUser')));

        $incoming_message_builder = new Tracker_Artifact_IncomingMessageInsecureBuilder(
            $this->user_manager,
            $this->tracker_factory,
            $this->artifact_factory
        );

        $raw_mail = array(
            'headers' => array(
                'from'    => self::USER_MAIL . ' (User Name)',
                'to'      => self::ARTIFACT_MAIL,
                'subject' => ''
            ),
            'body'    => ''
        );

        $incoming_message  = $incoming_message_builder->build($raw_mail);
        $artifact          = $incoming_message->getArtifact();
        $this->assertNotNull($artifact);
    }

    public function itFindsArtifactCcHeader() {
        $artifact_mock = stub('Tracker_Artifact')->getTracker()->returns(mock('Tracker'));
        stub($this->artifact_factory)->getArtifactById(self::ARTIFACT_ID)->returns($artifact_mock);
        stub($this->user_manager)->getAllUsersByEmail(self::USER_MAIL)->returns(array(mock('PFUser')));

        $incoming_message_builder = new Tracker_Artifact_IncomingMessageInsecureBuilder(
            $this->user_manager,
            $this->tracker_factory,
            $this->artifact_factory
        );

        $raw_mail = array(
            'headers' => array(
                'from'    => self::USER_MAIL . ' (User Name)',
                'to'      => '',
                'cc'      => self::ARTIFACT_MAIL,
                'subject' => ''
            ),
            'body'    => ''
        );

        $incoming_message  = $incoming_message_builder->build($raw_mail);
        $artifact          = $incoming_message->getArtifact();
        $this->assertNotNull($artifact);
    }

    public function itFindsArtifactMultipleUsers() {
        $artifact_mock = stub('Tracker_Artifact')->getTracker()->returns(mock('Tracker'));
        stub($this->artifact_factory)->getArtifactById(self::ARTIFACT_ID)->returns($artifact_mock);
        stub($this->user_manager)->getAllUsersByEmail(self::USER_MAIL)->returns(array(mock('PFUser')));

        $incoming_message_builder = new Tracker_Artifact_IncomingMessageInsecureBuilder(
            $this->user_manager,
            $this->tracker_factory,
            $this->artifact_factory
        );

        $raw_mail = array(
            'headers' => array(
                'from'    => self::USER_MAIL . ' (User Name)',
                'to'      => self::ARTIFACT_MAIL . ',' . 'unknown@example.com',
                'subject' => ''
            ),
            'body'    => ''
        );

        $incoming_message  = $incoming_message_builder->build($raw_mail);
        $artifact          = $incoming_message->getArtifact();
        $this->assertNotNull($artifact);
    }

    public function itRejectsUnknownMail() {
        stub($this->tracker_factory)->getTrackerById(self::TRACKER_ID)->returns(mock('Tracker'));
        stub($this->user_manager)->getAllUsersByEmail(self::USER_MAIL)->returns(array(mock('PFUser')));

        $incoming_message_builder = new Tracker_Artifact_IncomingMessageInsecureBuilder(
            $this->user_manager,
            $this->tracker_factory,
            $this->artifact_factory
        );

        $raw_mail = array(
            'headers' => array(
                'from'    => 'unknown@example.com',
                'to'      => self::TRACKER_MAIL,
                'subject' => ''
            ),
            'body'    => ''
        );

        $this->expectException('Tracker_Artifact_MailGateway_RecipientUserDoesNotExistException');
        $incoming_message_builder->build($raw_mail);
    }

    public function itRejectsMailWithMultipleUsers() {
        stub($this->tracker_factory)->getTrackerById(self::TRACKER_ID)->returns(mock('Tracker'));
        stub($this->user_manager)->getAllUsersByEmail(self::USER_MAIL)->returns(array(mock('PFUser'), mock('PFUser')));

        $incoming_message_builder = new Tracker_Artifact_IncomingMessageInsecureBuilder(
            $this->user_manager,
            $this->tracker_factory,
            $this->artifact_factory
        );

        $raw_mail = array(
            'headers' => array(
                'from'    => self::USER_MAIL,
                'to'      => self::TRACKER_MAIL,
                'subject' => ''
            ),
            'body'    => ''
        );

        $this->expectException('Tracker_Artifact_MailGateway_MultipleUsersExistException');
        $incoming_message_builder->build($raw_mail);
    }

    public function itRejectsUnknownTracker() {
        stub($this->tracker_factory)->getTrackerById(self::TRACKER_ID)->returns(mock('Tracker'));
        stub($this->user_manager)->getAllUsersByEmail(self::USER_MAIL)->returns(array(mock('PFUser')));

        $incoming_message_builder = new Tracker_Artifact_IncomingMessageInsecureBuilder(
            $this->user_manager,
            $this->tracker_factory,
            $this->artifact_factory
        );

        $raw_mail = array(
            'headers' => array(
                'from'    => self::USER_MAIL,
                'to'      => trackerPlugin::EMAILGATEWAY_INSECURE_ARTIFACT_CREATION . '+99999999@example.com',
                'subject' => ''
            ),
            'body'    => ''
        );

        $this->expectException('Tracker_Artifact_MailGateway_TrackerDoesNotExistException');
        $incoming_message_builder->build($raw_mail);
    }

    public function itRejectsUnknowArtifact() {
        stub($this->artifact_factory)->getArtifactById(self::TRACKER_ID)->returns(mock('Tracker_Artifact'));
        stub($this->user_manager)->getAllUsersByEmail(self::USER_MAIL)->returns(array(mock('PFUser')));

        $incoming_message_builder = new Tracker_Artifact_IncomingMessageInsecureBuilder(
            $this->user_manager,
            $this->tracker_factory,
            $this->artifact_factory
        );

        $raw_mail = array(
            'headers' => array(
                'from'    => self::USER_MAIL,
                'to'      => trackerPlugin::EMAILGATEWAY_INSECURE_ARTIFACT_UPDATE . '+99999999@example.com',
                'subject' => ''
            ),
            'body'    => ''
        );

        $this->expectException('Tracker_Artifact_MailGateway_ArtifactDoesNotExistException');
        $incoming_message_builder->build($raw_mail);
    }

}