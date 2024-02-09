<?php
/**
 * Copyright (c) Enalean, 2014-Present. All Rights Reserved.
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

use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Artifact\MailGateway\IncomingMail;
use Tuleap\Tracker\Artifact\MailGateway\MailGatewayFilter;

abstract class Tracker_Artifact_MailGateway_MailGateway
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
     * @var Tracker_ArtifactFactory
     */
    private $artifact_factory;

    /**
     * @var Tracker_FormElementFactory
     */
    private $formelement_factory;

    /**
     * @var Tracker_ArtifactByEmailStatus
     */
    protected $tracker_artifactbyemail;

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

    public function __construct(
        Tracker_Artifact_MailGateway_IncomingMessageFactory $incoming_message_factory,
        Tracker_Artifact_MailGateway_CitationStripper $citation_stripper,
        Tracker_Artifact_MailGateway_Notifier $notifier,
        Tracker_Artifact_Changeset_IncomingMailDao $incoming_mail_dao,
        Tracker_ArtifactFactory $artifact_factory,
        Tracker_FormElementFactory $formelement_factory,
        Tracker_ArtifactByEmailStatus $tracker_artifactbyemail,
        \Psr\Log\LoggerInterface $logger,
        MailGatewayFilter $mail_filter,
    ) {
        $this->logger                   = $logger;
        $this->incoming_message_factory = $incoming_message_factory;
        $this->citation_stripper        = $citation_stripper;
        $this->notifier                 = $notifier;
        $this->artifact_factory         = $artifact_factory;
        $this->formelement_factory      = $formelement_factory;
        $this->tracker_artifactbyemail  = $tracker_artifactbyemail;
        $this->incoming_mail_dao        = $incoming_mail_dao;
        $this->mail_filter              = $mail_filter;
    }

    public function process(IncomingMail $incoming_mail)
    {
        try {
            if ($this->mail_filter->isAnAutoReplyMail($incoming_mail) === false) {
                $changeset = $this->createChangeset($incoming_mail);
                if ($changeset) {
                    $this->linkRawMailToChangeset($incoming_mail, $changeset);
                }
            } else {
                $this->logger->debug('Auto-reply mail detected ');
            }
        } catch (Tracker_Artifact_MailGateway_MultipleUsersExistException $e) {
            $this->logger->debug('Multiple users match with ' . implode(', ', $incoming_mail->getFrom()));
            $this->notifier->sendErrorMailMultipleUsers($incoming_mail);
        } catch (Tracker_Artifact_MailGateway_RecipientUserDoesNotExistException $e) {
            $this->logger->debug('No user match with ' . implode(', ', $incoming_mail->getFrom()));
            $this->notifier->sendErrorMailNoUserMatch($incoming_mail);
        } catch (Tracker_Exception $e) {
            $this->logger->error($e->getMessage());
            $this->notifier->sendErrorMailTrackerGeneric($incoming_mail);
        }
    }

    /**
     * @return bool
     */
    abstract protected function canCreateArtifact(Tracker $tracker);

    /**
     * @return bool
     */
    abstract protected function canUpdateArtifact(Tracker $tracker);

    /**
     * @return Tracker_Artifact_Changeset|null
     */
    private function createChangeset(IncomingMail $incoming_mail)
    {
        $changeset        = null;
        $incoming_message = $this->incoming_message_factory->build($incoming_mail);
        $body             = $this->citation_stripper->stripText($incoming_message->getBody());

        if ($incoming_message->isAFollowUp()) {
            if ($this->canUpdateArtifact($incoming_message->getTracker())) {
                $changeset = $this->addFollowUp($incoming_message->getUser(), $incoming_message->getArtifact(), $body);
            } else {
                $this->logNoSufficientRightsToCreateChangeset($incoming_message, $incoming_mail);
            }
        } elseif ($this->canCreateArtifact($incoming_message->getTracker())) {
            $artifact = $this->createArtifact(
                $incoming_message->getUser(),
                $incoming_message->getTracker(),
                $incoming_message->getSubject(),
                $body
            );
            if ($artifact) {
                $this->logger->debug('New artifact created: ' . $artifact->getXRef());
                $changeset = $artifact->getFirstChangeset();
            }
        } else {
            $this->logNoSufficientRightsToCreateChangeset($incoming_message, $incoming_mail);
        }

        return $changeset;
    }

    private function linkRawMailToChangeset(IncomingMail $incoming_mail, Tracker_Artifact_Changeset $changeset)
    {
        $this->logger->debug('Linking created changeset (' . $changeset->getId() . ') to the raw mail.');
        $raw_mail_utf8 = mb_convert_encoding($incoming_mail->getRawMail(), 'utf-8');
        $this->incoming_mail_dao->save($changeset->getId(), $raw_mail_utf8);
    }

    /** @return Tracker_Artifact_Changeset|null */
    private function addFollowUp(PFUser $user, Artifact $artifact, $body)
    {
        $this->logger->debug("Receiving new follow-up comment from " . $user->getUserName());

        if (! $artifact->userCanUpdate($user)) {
            $this->logger->info("User " . $user->getUserName() . " has no right to update the artifact #" . $artifact->getId());
            $this->notifier->sendErrorMailInsufficientPermissionUpdate($user->getEmail(), $artifact->getId());
            return;
        }

        return $artifact->createNewChangeset(
            [],
            $body,
            $user,
            true,
            Tracker_Artifact_Changeset_Comment::TEXT_COMMENT
        );
    }

    private function createArtifact(PFUser $user, Tracker $tracker, $title, $body): ?Artifact
    {
        $this->logger->debug("Receiving new artifact from " . $user->getUserName());

        if (! $tracker->userCanSubmitArtifact($user)) {
            $this->logger->info("User " . $user->getUserName() . " has no right to create an artifact in tracker #" . $tracker->getId());
            $this->notifier->sendErrorMailInsufficientPermissionCreation($user->getEmail(), $title);
            return null;
        }

        $title_field       = $tracker->getTitleField();
        $description_field = $tracker->getDescriptionField();
        if (! $title_field || ! $description_field) {
            throw new Tracker_Artifact_MailGateway_TrackerMissingSemanticException();
        }

        $field_data = [
            $title_field->getId()       => $title,
            $description_field->getId() => $body,
        ];
        $field_data = $this->formelement_factory->getUsedFieldsWithDefaultValue($tracker, $field_data, $user);

        UserManager::instance()->setCurrentUser(
            \Tuleap\User\CurrentUserWithLoggedInInformation::fromLoggedInUser($user)
        );
        return $this->artifact_factory->createArtifact($tracker, $field_data, $user, true, true);
    }

    private function logNoSufficientRightsToCreateChangeset(
        Tracker_Artifact_MailGateway_IncomingMessage $incoming_message,
        IncomingMail $incoming_mail,
    ) {
        $this->logger->info(
            'An artifact for the tracker #' . $incoming_message->getTracker()->getId() .
            ' has been received but this tracker does not allow create/reply by mail or' .
            ' his configuration is not compatible with this feature'
        );
        $this->notifier->sendErrorMailTrackerGeneric($incoming_mail);
    }
}
