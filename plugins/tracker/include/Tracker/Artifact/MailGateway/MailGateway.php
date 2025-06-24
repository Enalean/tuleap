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

use Psr\Log\LoggerInterface;
use Tuleap\Option\Option;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Artifact\Changeset\Comment\CommentFormatIdentifier;
use Tuleap\Tracker\Artifact\ChangesetValue\ArtifactLink\NewArtifactLinkInitialChangesetValue;
use Tuleap\Tracker\Artifact\ChangesetValue\InitialChangesetValuesContainer;
use Tuleap\Tracker\Artifact\Creation\TrackerArtifactCreator;
use Tuleap\Tracker\Artifact\MailGateway\IncomingMail;
use Tuleap\Tracker\Artifact\MailGateway\MailGatewayFilter;
use Tuleap\Tracker\Tracker;
use Tuleap\Tracker\Semantic\Description\RetrieveSemanticDescriptionField;

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotCamelCaps
abstract class Tracker_Artifact_MailGateway_MailGateway
{
    public function __construct(
        private Tracker_Artifact_MailGateway_IncomingMessageFactory $incoming_message_factory,
        private Tracker_Artifact_MailGateway_CitationStripper $citation_stripper,
        private Tracker_Artifact_MailGateway_Notifier $notifier,
        private Tracker_Artifact_Changeset_IncomingMailDao $incoming_mail_dao,
        private TrackerArtifactCreator $artifact_creator,
        private Tracker_FormElementFactory $formelement_factory,
        protected Tracker_ArtifactByEmailStatus $tracker_artifactbyemail,
        private LoggerInterface $logger,
        private MailGatewayFilter $mail_filter,
        private RetrieveSemanticDescriptionField $retrieve_description_field,
    ) {
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
        $this->logger->debug('Receiving new follow-up comment from ' . $user->getUserName());

        if (! $artifact->userCanUpdate($user)) {
            $this->logger->info('User ' . $user->getUserName() . ' has no right to update the artifact #' . $artifact->getId());
            $this->notifier->sendErrorMailInsufficientPermissionUpdate($user->getEmail(), $artifact->getId());
            return;
        }

        return $artifact->createNewChangeset(
            [],
            $body,
            $user,
            true,
            CommentFormatIdentifier::TEXT
        );
    }

    private function createArtifact(PFUser $user, Tracker $tracker, $title, $body): ?Artifact
    {
        $this->logger->debug('Receiving new artifact from ' . $user->getUserName());

        if (! $tracker->userCanSubmitArtifact($user)) {
            $this->logger->info('User ' . $user->getUserName() . ' has no right to create an artifact in tracker #' . $tracker->getId());
            $this->notifier->sendErrorMailInsufficientPermissionCreation($user->getEmail(), $title);
            return null;
        }

        $title_field       = $tracker->getTitleField();
        $description_field = $this->retrieve_description_field->fromTracker($tracker);
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

        return $this->artifact_creator->create(
            $tracker,
            new InitialChangesetValuesContainer($field_data, Option::nothing(NewArtifactLinkInitialChangesetValue::class)),
            $user,
            \Tuleap\Request\RequestTime::getTimestamp(),
            true,
            true,
            new \Tuleap\Tracker\Changeset\Validation\NullChangesetValidationContext(),
            false,
        );
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
