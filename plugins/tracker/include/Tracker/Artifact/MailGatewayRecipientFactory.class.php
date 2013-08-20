<?php
/**
 * Copyright (c) Enalean, 2013. All Rights Reserved.
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

/**
 * Builds instances of MailGatewayRecipient
 */
class Tracker_Artifact_MailGatewayRecipientFactory {

    const EMAIL_PATTERN     = '/^(?P<artifact_id>\d+)-(?P<hash>[^-]+)-(?P<user_id>\d+)@/';
    const ARTIFACT_ID_INDEX = 'artifact_id';
    const USER_ID_INDEX     = 'user_id';
    const HASH_INDEX        = 'hash';

    /** @var Tracker_ArtifactFactory */
    private $artifact_factory;

    /** @var UserManager */
    private $user_manager;

    /** @var string */
    private $salt;

    /** @var string */
    private $host;


    public function __construct(Tracker_ArtifactFactory $artifact_factory, UserManager $user_manager, $salt, $host) {
        $this->artifact_factory = $artifact_factory;
        $this->user_manager     = $user_manager;
        $this->salt             = $salt;
        $this->host             = $host;
    }

    /** @return Tracker_Artifact_MailGatewayRecipient */
    public function getFromEmail($email) {
        preg_match(self::EMAIL_PATTERN, $email, $email_parts);
        $artifact = $this->getArtifact((int)$email_parts[self::ARTIFACT_ID_INDEX]);
        $user     = $this->getUser((int)$email_parts[self::USER_ID_INDEX]);

        $this->checkHash($user, $artifact, $email_parts[self::HASH_INDEX]);

        return new Tracker_Artifact_MailGatewayRecipient(
            $user,
            $artifact,
            $email
        );
    }

    /** @return Tracker_Artifact_MailGatewayRecipient */
    public function getFromUserAndArtifact($user, $artifact) {
        return new Tracker_Artifact_MailGatewayRecipient(
            $user,
            $artifact,
            $this->getEmail($user, $artifact)
        );
    }

    private function getArtifact($artifact_id) {
        $artifact = $this->artifact_factory->getArtifactById($artifact_id);
        if (! $artifact) {
            throw new Tracker_Artifact_MailGatewayRecipientArtifactDoesNotExistException();
        }

        return $artifact;
    }

    private function getUser($user_id) {
        $user = $this->user_manager->getUserById($user_id);
        if (! $user) {
            throw new Tracker_Artifact_MailGatewayRecipientUserDoesNotExistException();
        }

        return $user;
    }

    private function checkHash(PFUser $user, Tracker_Artifact $artifact, $submitted_hash) {
        if ($this->getHash($user, $artifact) != $submitted_hash) {
            throw new Tracker_Artifact_MailGatewayRecipientInvalidHashException();
        }
    }

    private function getHash(PFUser $user, Tracker_Artifact $artifact) {
        return md5($user->getId() . "-" . $artifact->getId() . "-" . $this->salt);
    }

    private function getEmail(PFUser $user, Tracker_Artifact $artifact) {
        return $artifact->getId() . "-" .
            $this->getHash($user, $artifact) . "-" .
            $user->getId() .
            "@" . $this->host;
    }
}

?>
