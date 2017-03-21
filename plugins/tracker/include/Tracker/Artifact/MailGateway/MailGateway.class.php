<?php
/**
 * Copyright (c) Enalean, 2014-2017. All Rights Reserved.
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

abstract class Tracker_Artifact_MailGateway_MailGateway {

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
    protected $tracker_artifactbyemail;

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

    public function process($raw_mail) {
        $raw_mail_parsed = $this->parser->parse($raw_mail);
        try {
            if ($this->mail_filter->isAnAutoReplyMail($raw_mail_parsed) === false) {
                $changeset = $this->createChangeset($raw_mail_parsed);
                if ($changeset) {
                    $this->linkRawMailToChangeset($raw_mail, $changeset);
                }
            } else {
                $this->logger->debug('Auto-reply mail detected ');
            }
        } catch (Tracker_Artifact_MailGateway_MultipleUsersExistException $e) {
            $this->logger->debug('Multiple users match with ' . $raw_mail_parsed['headers']['from']);
            $this->notifier->sendErrorMailMultipleUsers($raw_mail_parsed);
        } catch(Tracker_Artifact_MailGateway_RecipientUserDoesNotExistException $e) {
            $this->logger->debug('No user match with ' . $raw_mail_parsed['headers']['from']);
            $this->notifier->sendErrorMailNoUserMatch($raw_mail_parsed);
        } catch (Tracker_Exception $e) {
            $this->logger->error($e->getMessage());
            $this->notifier->sendErrorMailTrackerGeneric($raw_mail_parsed);
        }
    }

    /**
     * @return bool
     */
    protected abstract function canCreateArtifact(Tracker $tracker);

    /**
     * @return bool
     */
    protected abstract function canUpdateArtifact(Tracker $tracker);

    /**
     * @return Tracker_Artifact_Changeset|null
     */
    private function createChangeset(array $raw_mail_parsed) {
        $changeset        = null;
        $incoming_message = $this->incoming_message_factory->build($raw_mail_parsed);
        $body             = $this->citation_stripper->stripText($incoming_message->getBody());

        if ($incoming_message->isAFollowUp()) {
            if ($this->canUpdateArtifact($incoming_message->getTracker())) {
                $changeset = $this->addFollowUp($incoming_message->getUser(), $incoming_message->getArtifact(), $body);
            } else {
                $this->logNoSufficientRightsToCreateChangeset($incoming_message, $raw_mail_parsed);
            }
        } else if ($this->canCreateArtifact($incoming_message->getTracker())) {
            $artifact = $this->createArtifact(
                $incoming_message->getUser(),
                $incoming_message->getTracker(),
                $incoming_message->getSubject(),
                $body
            );
            if ($artifact) {
                $this->logger->debug('New artifact created: '. $artifact->getXRef());
                $changeset = $artifact->getFirstChangeset();
            }
        } else {
            $this->logNoSufficientRightsToCreateChangeset($incoming_message, $raw_mail_parsed);
        }

        return $changeset;
    }

    private function linkRawMailToChangeset($raw_mail, Tracker_Artifact_Changeset $changeset)
    {
        $this->logger->debug('Linking created changeset (' . $changeset->getId() . ') to the raw mail.');
        $raw_mail_utf8 = mb_convert_encoding($raw_mail, 'utf-8');
        $this->incoming_mail_dao->save($changeset->getId(), $raw_mail_utf8);
    }

    /** @return Tracker_Artifact_Changeset|null */
    private function addFollowUp(PFUser $user, Tracker_Artifact $artifact, $body) {
        $this->logger->debug("Receiving new follow-up comment from ". $user->getUserName());

        if (! $artifact->userCanUpdate($user)) {
            $this->logger->info("User ". $user->getUnixName() ." has no right to update the artifact #" . $artifact->getId());
            $this->notifier->sendErrorMailInsufficientPermissionUpdate($user->getEmail(), $artifact->getId());
            return;
        }

        return $artifact->createNewChangeset(
            array(),
            $body,
            $user,
            true,
            Tracker_Artifact_Changeset_Comment::TEXT_COMMENT
        );
    }

    /** @return Tracker_Artifact */
    private function createArtifact(PFUser $user, Tracker $tracker, $title, $body) {
        $this->logger->debug("Receiving new artifact from ". $user->getUserName());

        if (! $tracker->userCanSubmitArtifact($user)) {
            $this->logger->info("User ". $user->getUnixName() ." has no right to create an artifact in tracker #" . $tracker->getId());
            $this->notifier->sendErrorMailInsufficientPermissionCreation($user->getEmail(), $title);
            return;
        }

        $title_field       = $tracker->getTitleField();
        $description_field = $tracker->getDescriptionField();
        if (! $title_field || ! $description_field) {
            throw new Tracker_Artifact_MailGateway_TrackerMissingSemanticException();
        }

        $field_data = array(
            $title_field->getId()       => $title,
            $description_field->getId() => $body
        );

        UserManager::instance()->setCurrentUser($user);
        return $this->artifact_factory->createArtifact($tracker, $field_data, $user, '');
    }

    private function logNoSufficientRightsToCreateChangeset(
        Tracker_Artifact_MailGateway_IncomingMessage $incoming_message,
        $raw_mail_parsed
    ) {
        $this->logger->info(
            'An artifact for the tracker #' . $incoming_message->getTracker()->getId() .
            ' has been received but this tracker does not allow create/reply by mail or' .
            ' his configuration is not compatible with this feature'
        );
        $this->notifier->sendErrorMailTrackerGeneric($raw_mail_parsed);
    }
}
