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
namespace User\XML\Import;

use UserManager;
use SimpleXMLElement;
use PFUser;
use XML_Security;
use XML_RNGValidator;
use Tuleap\Project\XML\Import\ArchiveInterface;

class UsersToBeImportedCollectionBuilder
{

    /**
     * @var XML_RNGValidator
     */
    private $xml_validator;

    /**
     * @var XML_Security
     */
    private $security;

    /** @var UserManager */
    private $user_manager;

    public function __construct(
        UserManager $user_manager,
        XML_Security $security,
        XML_RNGValidator $xml_validator
    ) {
        $this->user_manager  = $user_manager;
        $this->security      = $security;
        $this->xml_validator = $xml_validator;
    }

    /** @return UsersToBeImportedCollection */
    public function build(ArchiveInterface $archive)
    {
        $collection = new UsersToBeImportedCollection();

        foreach ($this->getXMLFromArchive($archive) as $user) {
            $to_be_imported_user = $this->instantiateUserToBeImported($user);
            $collection->add($to_be_imported_user);
        }

        return $collection;
    }

    /** @return UsersToBeImportedCollection */
    public function buildWithoutEmail(ArchiveInterface $archive)
    {
        $collection = new UsersToBeImportedCollection();

        foreach ($this->getXMLFromArchive($archive) as $user) {
            $to_be_imported_user = $this->instantiateUserToBeImportedWithoutEmail($user);
            $collection->add($to_be_imported_user);
        }

        return $collection;
    }

    /**
     *
     * @return SimpleXMLElement
     * @throws UsersXMLNotFoundException
     */
    private function getXMLFromArchive(ArchiveInterface $archive)
    {
        $xml_contents = $archive->getUsersXML();
        if (! $xml_contents) {
            throw new UsersXMLNotFoundException();
        }

        $xml_element = $this->security->loadString($xml_contents);

        $rng_path = realpath(__DIR__ . '/../../../xml/resources/users.rng');
        $this->xml_validator->validate($xml_element, $rng_path);

        return $xml_element;
    }

    private function instantiateUserToBeImportedWithoutEmail(SimpleXMLElement $xml_user)
    {
        $tuleap_user = $this->getExistingUserFromXML($xml_user);

        if ($tuleap_user) {
            return $this->instantiateMatchingUser($tuleap_user, $xml_user);
        }

        return new ToBeCreatedUser(
            (string) $xml_user->username,
            (string) $xml_user->realname,
            (string) $xml_user->email,
            (string) $xml_user->id,
            (string) $xml_user->ldapid
        );
    }

    private function instantiateUserToBeImported(SimpleXMLElement $user)
    {
        $existing_user = $this->getExistingUserFromXML($user);

        if (! $existing_user) {
            return $this->instantiateUserByMail($user);
        }

        if ($existing_user->getLdapId()) {
            return $this->instantiateMatchingUser($existing_user, $user);
        }

        $email_found_in_xml = (string) $user->email;
        if ($existing_user->getEmail() !== $email_found_in_xml) {
            return new EmailDoesNotMatchUser(
                $existing_user,
                $email_found_in_xml,
                (string) $user->id,
                (string) $user->ldapid
            );
        }

        return $this->instantiateMatchingUser($existing_user, $user);
    }

    private function instantiateUserByMail(SimpleXMLElement $user)
    {
        $matching_users = $this->user_manager->getAllUsersByEmail((string) $user->email);

        if (empty($matching_users)) {
            return new ToBeCreatedUser(
                (string) $user->username,
                (string) $user->realname,
                (string) $user->email,
                (string) $user->id,
                (string) $user->ldapid
            );
        }

        return new ToBeMappedUser(
            (string) $user->username,
            (string) $user->realname,
            $matching_users,
            (string) $user->id,
            (string) $user->ldapid
        );
    }

    /** @return \PFUser|null */
    private function getExistingUserFromXML(SimpleXMLElement $user)
    {
        $ldap_id = (string) $user->ldapid;

        $existing_user = null;
        if ($ldap_id) {
            $existing_user = $this->user_manager->getUserByIdentifier("ldapId:$ldap_id");
        }

        if (! $existing_user) {
            $existing_user = $this->user_manager->getUserByIdentifier((string) $user->username);
        }

        return $existing_user;
    }

    private function instantiateMatchingUser(PFUser $user, SimpleXMLElement $xml_user)
    {
        if ($user->isAlive()) {
            $to_be_imported_user = new AlreadyExistingUser($user, (string) $xml_user->id, (string) $xml_user->ldapid);
        } else {
            $to_be_imported_user = new ToBeActivatedUser($user, (string) $xml_user->id, (string) $xml_user->ldapid);
        }

        return $to_be_imported_user;
    }
}
