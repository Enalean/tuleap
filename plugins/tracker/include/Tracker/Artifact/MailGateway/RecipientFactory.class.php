<?php
/**
 * Copyright (c) Enalean, 2013-2018. All Rights Reserved.
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
 * Builds instances of Tracker_Artifact_MailGateway_Recipient
 */
class Tracker_Artifact_MailGateway_RecipientFactory
{

    public const ARTIFACT_ID_INDEX = 'artifact_id';
    public const USER_ID_INDEX     = 'user_id';
    public const HASH_INDEX        = 'hash';
    public const EMAIL_PATTERN     = '/
        <
        (?P<artifact_id>\d+)
        -
        (?P<hash>[^-]+)
        -
        (?P<user_id>\d+)
        -
        (?P<changeset_id>\d+)
        @
        (?P<domain>.*)
        >
        /x';

    /** @var Tracker_ArtifactFactory */
    private $artifact_factory;

    /** @var UserManager */
    private $user_manager;

    /** @var string */
    private $salt;

    /** @var string */
    private $host;

    public function __construct(
        Tracker_ArtifactFactory $artifact_factory,
        UserManager $user_manager,
        $salt,
        $host
    ) {
        $this->artifact_factory = $artifact_factory;
        $this->user_manager     = $user_manager;
        $this->salt             = $salt;
        $this->host             = $host;
    }

    /**
     * @return Tracker_Artifact_MailGateway_RecipientFactory
     */
    public static function build()
    {
        $dao = new MailGatewaySaltDao();
        $row = $dao->searchMailSalt()->getRow();

        return new Tracker_Artifact_MailGateway_RecipientFactory(
            Tracker_ArtifactFactory::instance(),
            UserManager::instance(),
            $row['salt'],
            ForgeConfig::get('sys_default_domain')
        );
    }

    /**
     * @param string $email the email message id
     *
     * @throws Tracker_Artifact_MailGateway_ArtifactDoesNotExistException
     * @throws Tracker_Artifact_MailGateway_RecipientUserDoesNotExistException
     * @throws Tracker_Artifact_MailGateway_RecipientInvalidHashException
     *
     * @return Tracker_Artifact_MailGateway_Recipient
     */
    public function getFromEmail($email)
    {
        preg_match(self::EMAIL_PATTERN, $email, $email_parts);
        $artifact = $this->getArtifact((int) $email_parts[self::ARTIFACT_ID_INDEX]);
        $user     = $this->getUser((int) $email_parts[self::USER_ID_INDEX]);

        $this->checkHash($user, $artifact, $email_parts[self::HASH_INDEX]);

        return new Tracker_Artifact_MailGateway_Recipient(
            $user,
            $artifact,
            $email
        );
    }

    /** @return Tracker_Artifact_MailGateway_Recipient */
    public function getFromUserAndChangeset(
        PFUser $user,
        Tracker_Artifact_Changeset $changeset
    ) {
        $artifact = $changeset->getArtifact();
        return new Tracker_Artifact_MailGateway_Recipient(
            $user,
            $artifact,
            $this->getEmail($user, $artifact, $changeset)
        );
    }

    private function getEmail(
        PFUser $user,
        Tracker_Artifact $artifact,
        Tracker_Artifact_Changeset $changeset
    ) {
        return $artifact->getId() . "-" .
            $this->getHash($user, $artifact) . "-" .
            $user->getId() . "-" .
            $changeset->getId() .
            "@" . $this->host;
    }

    private function getArtifact($artifact_id)
    {
        $artifact = $this->artifact_factory->getArtifactById($artifact_id);
        if (! $artifact) {
            throw new Tracker_Artifact_MailGateway_ArtifactDoesNotExistException();
        }

        return $artifact;
    }

    private function getUser($user_id)
    {
        $user = $this->user_manager->getUserById($user_id);
        if (! $user) {
            throw new Tracker_Artifact_MailGateway_RecipientUserDoesNotExistException();
        }

        return $user;
    }

    private function checkHash(
        PFUser $user,
        Tracker_Artifact $artifact,
        $submitted_hash
    ) {
        if ($this->getHash($user, $artifact) != $submitted_hash) {
            throw new Tracker_Artifact_MailGateway_RecipientInvalidHashException();
        }
    }

    private function getHash(
        PFUser $user,
        Tracker_Artifact $artifact
    ) {
        return md5($user->getId() . "-" . $artifact->getId() . "-" . $this->salt);
    }
}
