<?php
/**
 * Copyright (c) Enalean, 2014. All Rights Reserved.
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

class Tracker_Artifact_MailGateway_MailGateway {

    /**
     * @var Tracker_Artifact_MailGateway_Parser
     */
    private $parser;

    /**
     * @var Logger
     */
    private $logger;

    public function __construct(Tracker_Artifact_MailGateway_Parser $parser, Logger $logger) {
        $this->logger = $logger;
        $this->parser = $parser;
    }

    public function process($raw_mail) {
        $incoming_message = $this->parser->parse($raw_mail);
        $user             = $incoming_message->getRecipient()->getUser();
        $artifact         = $incoming_message->getRecipient()->getArtifact();

        $this->logger->debug("Receiving new follow-up comment from ". $user->getUserName());

        $artifact->createNewChangeset(
            array(),
            $incoming_message->getBody(),
            $user,
            true,
            Tracker_Artifact_Changeset_Comment::TEXT_COMMENT
        );
    }
}
