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

class Tracker_Artifact_IncomingMessageInsecureBuilder {
    /**
     * @var UserManager
     */
    private $user_manager;

    /**
     * @var TrackerFactory
     */
    private $tracker_factory;

    private $artifact_factory;

    public function __construct(UserManager $user_manager, TrackerFactory $tracker_factory,
                                    Tracker_ArtifactFactory $artifact_factory) {
        $this->user_manager     = $user_manager;
        $this->tracker_factory  = $tracker_factory;
        $this->artifact_factory = $artifact_factory;
    }

    /**
     * @return Tracker_Artifact_MailGateway_IncomingMessage
     */
    public function build(array $raw_mail) {
        $subject        = $raw_mail['headers']['subject'];
        $body           = $raw_mail['body'];
        $user           = $this->getUserFromMailHeader($raw_mail['headers']['from']);
        $is_a_followup  = $this->isAFollowUp($raw_mail['headers']['to']);
        if ($is_a_followup) {
            $artifact   = $this->getArtifactFromMailHeader($raw_mail['headers']['to']);
            $tracker    = $artifact->getTracker();
        } else {
            $tracker    = $this->getTrackerFromMailHeader($raw_mail['headers']['to']);
            $artifact   = null;
        }

        $incoming_message = new Tracker_Artifact_MailGateway_IncomingMessage(
            $raw_mail['headers'],
            $subject,
            $body,
            $user,
            $tracker,
            $artifact
        );

        return $incoming_message;
    }

    private function getUserFromMailHeader($mail_header) {
        $user_mail = $this->extractMailFromHeader($mail_header);
        $users     = $this->user_manager->getAllUsersByEmail($user_mail);

        if (count($users) > 1) {
            throw new Tracker_Artifact_MailGateway_MultipleUsersExistException();
        } elseif (count($users) === 0) {
            throw new Tracker_Artifact_MailGateway_RecipientUserDoesNotExistException();
        }

        return $users[0];
    }

    /**
     * @return Tracker
     */
    private function getTrackerFromMailHeader($mail_header) {
        $mail_userpart = $this->extractMailUserParts($mail_header);

        if (count($mail_userpart) !== 2) {
            throw new Tracker_Artifact_MailGateway_TrackerIdMissingException();
        }

        $tracker_id = (int) $mail_userpart[1];
        $tracker = $this->tracker_factory->getTrackerById($tracker_id);

        if ($tracker === null) {
            throw new Tracker_Artifact_MailGateway_TrackerDoesNotExistException();
        }

        return $tracker;
    }

    /**
     * @return Tracker_Artifact
     */
    private function getArtifactFromMailHeader($mail_header) {
        $mail_userpart = $this->extractMailUserParts($mail_header);

        if (count($mail_userpart) !== 2) {
            throw new Tracker_Artifact_MailGateway_ArtifactIdMissingException();
        }

        $artifact_id = (int)$mail_userpart[1];
        $artifact = $this->artifact_factory->getArtifactById($artifact_id);

        if ($artifact === null) {
            throw new Tracker_Artifact_MailGateway_ArtifactDoesNotExistException();
        }

        return $artifact;
    }

    /**
     * @return array
     */
    private function extractMailUserParts($mail_header) {
        $mail_receiver = $this->extractMailFromHeader($mail_header);
        $mail_splitted = explode('@', $mail_receiver);
        $mail_userpart = explode('+', $mail_splitted[0]);

        return $mail_userpart;
    }

    /**
     * @return bool
     */
    private function isAFollowUp($mail_header) {
        return strpos($mail_header, trackerPlugin::EMAILGATEWAY_INSECURE_ARTIFACT_UPDATE) !== false;
    }

    private function extractMailFromHeader($mail_header) {
        $mail_addresses = imap_rfc822_parse_adrlist($mail_header, '');
        if (!is_array($mail_addresses) || count($mail_addresses) !== 1) {
            throw new Tracker_Artifact_MailGateway_InvalidMailHeadersException();
        }

        return $mail_addresses[0]->mailbox . '@' . $mail_addresses[0]->host;
    }

}