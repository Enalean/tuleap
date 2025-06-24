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

use Psr\Log\LoggerInterface;
use Tuleap\Tracker\Artifact\Creation\TrackerArtifactCreator;
use Tuleap\Tracker\Artifact\MailGateway\IncomingMail;
use Tuleap\Tracker\Artifact\MailGateway\MailGatewayFilter;
use Tuleap\Tracker\Semantic\Description\RetrieveSemanticDescriptionField;

final readonly class Tracker_Artifact_MailGateway_MailGatewayBuilder
{
    public function __construct(
        private Tracker_Artifact_MailGateway_IncomingMessageFactory $incoming_message_factory,
        private Tracker_Artifact_MailGateway_CitationStripper $citation_stripper,
        private Tracker_Artifact_MailGateway_Notifier $notifier,
        private Tracker_Artifact_Changeset_IncomingMailDao $incoming_mail_dao,
        private TrackerArtifactCreator $artifact_creator,
        private Tracker_FormElementFactory $formelement_factory,
        private Tracker_ArtifactByEmailStatus $tracker_artifactbyemail,
        private LoggerInterface $logger,
        private MailGatewayFilter $mail_filter,
        private RetrieveSemanticDescriptionField $retrieve_description_field,
    ) {
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
                $this->mail_filter,
                $this->retrieve_description_field,
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
            $this->mail_filter,
            $this->retrieve_description_field,
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
