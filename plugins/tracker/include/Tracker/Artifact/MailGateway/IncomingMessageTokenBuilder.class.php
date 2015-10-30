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

class Tracker_Artifact_IncomingMessageTokenBuilder {

    /**
     * @var Tracker_Artifact_MailGateway_RecipientFactory
     */
    private $recipient_factory;

    public function __construct(Tracker_Artifact_MailGateway_RecipientFactory $recipient_factory) {
        $this->recipient_factory = $recipient_factory;
    }

    /**
     * @return Tracker_Artifact_MailGateway_IncomingMessage
     */
    public function build(array $raw_mail) {
        if (! isset($raw_mail['headers']['references'])) {
            throw new Tracker_Artifact_MailGateway_InvalidMailHeadersException();
        }
        preg_match(
            Tracker_Artifact_MailGateway_RecipientFactory::EMAIL_PATTERN,
            $raw_mail['headers']['references'],
            $matches
        );
        $recipient     = $this->recipient_factory->getFromEmail($matches[0]);
        $subject       = $raw_mail['headers']['subject'];
        $body          = $raw_mail['body'];

        $incoming_message = new Tracker_Artifact_MailGateway_IncomingMessage(
            $raw_mail['headers'],
            $subject,
            $body,
            $recipient->getUser(),
            $recipient->getArtifact()->getTracker(),
            $recipient->getArtifact()
        );
        return $incoming_message;
    }

}