<?php
/**
 * Copyright (c) Enalean, 2015 - Present. All Rights Reserved.
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

use Tuleap\Tracker\Artifact\Creation\TrackerArtifactCreator;
use Tuleap\Tracker\Artifact\MailGateway\IncomingMail;
use Tuleap\Tracker\Artifact\MailGateway\MailGatewayFilter;

class Tracker_Artifact_MailGateway_MailGatewayBuilder
{
    /**
     * @var Tracker_Artifact_MailGateway_CitationStripper
     */
    private $citation_stripper;

    /**
     * @var Tracker_Artifact_MailGateway_IncomingMessageFactory
     */
    private $incoming_message_factory;

    /**
     * @var Tracker_Artifact_MailGateway_Notifier
     */
    private $notifier;

    /**
     * @var Tracker_ArtifactByEmailStatus
     */
    private $tracker_artifactbyemail;

    /**
     * @var \Psr\Log\LoggerInterface
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
    /**
     * @var Tracker_FormElementFactory
     */
    private $formelement_factory;

    public function __construct(
        Tracker_Artifact_MailGateway_IncomingMessageFactory $incoming_message_factory,
        Tracker_Artifact_MailGateway_CitationStripper $citation_stripper,
        Tracker_Artifact_MailGateway_Notifier $notifier,
        Tracker_Artifact_Changeset_IncomingMailDao $incoming_mail_dao,
        private readonly TrackerArtifactCreator $artifact_creator,
        Tracker_FormElementFactory $formelement_factory,
        Tracker_ArtifactByEmailStatus $tracker_artifactbyemail,
        \Psr\Log\LoggerInterface $logger,
        MailGatewayFilter $mail_filter,
    ) {
        $this->logger                   = $logger;
        $this->incoming_message_factory = $incoming_message_factory;
        $this->citation_stripper        = $citation_stripper;
        $this->notifier                 = $notifier;
        $this->formelement_factory      = $formelement_factory;
        $this->tracker_artifactbyemail  = $tracker_artifactbyemail;
        $this->incoming_mail_dao        = $incoming_mail_dao;
        $this->mail_filter              = $mail_filter;
    }

    public function build(IncomingMail $incoming_mail)
    {
        if ($this->isATokenMail($incoming_mail)) {
            return new Tracker_Artifact_MailGateway_TokenMailGateway(
                $this->incoming_message_factory,
                $this->citation_stripper,
                $this->notifier,
                $this->incoming_mail_dao,
                $this->artifact_creator,
                $this->formelement_factory,
                $this->tracker_artifactbyemail,
                $this->logger,
                $this->mail_filter
            );
        }

        return new Tracker_Artifact_MailGateway_InsecureMailGateway(
            $this->incoming_message_factory,
            $this->citation_stripper,
            $this->notifier,
            $this->incoming_mail_dao,
            $this->artifact_creator,
            $this->formelement_factory,
            $this->tracker_artifactbyemail,
            $this->logger,
            $this->mail_filter
        );
    }

    /**
     * @return bool
     */
    private function isATokenMail(IncomingMail $mail)
    {
        foreach ($mail->getTo() as $address) {
            if (strpos($address, trackerPlugin::EMAILGATEWAY_TOKEN_ARTIFACT_UPDATE) === 0) {
                return true;
            }
        }
        return false;
    }
}
