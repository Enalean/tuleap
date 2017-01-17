<?php
/**
 * Copyright (c) Enalean, 2015 - 2017. All Rights Reserved.
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

use Tuleap\Tracker\Artifact\MailGateway\MailGatewayFilter;

class Tracker_Artifact_MailGateway_MailGatewayBuilder {

    /**
     * @var Tracker_Artifact_MailGateway_CitationStripper
     */
    private $citation_stripper;

    /**
     * @var Tracker_Artifact_MailGateway_Parser
     */
    private $parser;

    /**
     * @var Tracker_Artifact_MailGateway_IncomingMessageFactory
     */
    private $incoming_message_factory;

    /**
     * @var Tracker_Artifact_MailGateway_Notifier
     */
    private $notifier;

    /**
     * @var Tracker_ArtifactFactory
     */
    private $artifact_factory;

    /**
     * @var Tracker_ArtifactByEmailStatus
     */
    private $tracker_artifactbyemail;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var Tracker_Artifact_Changeset_IncomingMailDao
     */
    private $incoming_mail_dao;
    /**
     * @var MailGatewayFilter
     */
    private $mail_filter;

    public function __construct(
        Tracker_Artifact_MailGateway_Parser $parser,
        Tracker_Artifact_MailGateway_IncomingMessageFactory $incoming_message_factory,
        Tracker_Artifact_MailGateway_CitationStripper $citation_stripper,
        Tracker_Artifact_MailGateway_Notifier $notifier,
        Tracker_Artifact_Changeset_IncomingMailDao $incoming_mail_dao,
        Tracker_ArtifactFactory $artifact_factory,
        Tracker_ArtifactByEmailStatus $tracker_artifactbyemail,
        Logger $logger,
        MailGatewayFilter $mail_filter
    ) {
        $this->logger                   = $logger;
        $this->parser                   = $parser;
        $this->incoming_message_factory = $incoming_message_factory;
        $this->citation_stripper        = $citation_stripper;
        $this->notifier                 = $notifier;
        $this->artifact_factory         = $artifact_factory;
        $this->tracker_artifactbyemail  = $tracker_artifactbyemail;
        $this->incoming_mail_dao        = $incoming_mail_dao;
        $this->mail_filter              = $mail_filter;
    }

    public function build($raw_mail)
    {
        if ($this->isATokenMail($raw_mail)) {
            return new Tracker_Artifact_MailGateway_TokenMailGateway(
                $this->parser,
                $this->incoming_message_factory,
                $this->citation_stripper,
                $this->notifier,
                $this->incoming_mail_dao,
                $this->artifact_factory,
                $this->tracker_artifactbyemail,
                $this->logger,
                $this->mail_filter
            );
        }

        return new Tracker_Artifact_MailGateway_InsecureMailGateway(
            $this->parser,
            $this->incoming_message_factory,
            $this->citation_stripper,
            $this->notifier,
            $this->incoming_mail_dao,
            $this->artifact_factory,
            $this->tracker_artifactbyemail,
            $this->logger,
            $this->mail_filter
        );
    }

    private function isATokenMail($raw_mail) {
        $raw_mail_parsed = $this->parser->parse($raw_mail);

        return strpos($raw_mail_parsed['headers']['to'], trackerPlugin::EMAILGATEWAY_TOKEN_ARTIFACT_UPDATE) !== false;
    }
}
