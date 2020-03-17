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

use PFUser;
use SimpleXMLElement;
use UserManager;
use Psr\Log\LoggerInterface;
use RuntimeException;

class Mapping implements IFindUserFromXMLReference
{

    private static $NONE_USERNAME = 'None';

    /** @var UserManager */
    private $user_manager;

    /** @var ReadyToBeImportedUsersCollection */
    private $collection;

    public function __construct(
        UserManager $user_manager,
        ReadyToBeImportedUsersCollection $collection,
        LoggerInterface $logger
    ) {
        $this->user_manager = $user_manager;
        $this->collection   = $collection;
        $this->logger       = $logger;
    }

    public function getUser(SimpleXMLElement $xml_element): PFUser
    {
        try {
            return $this->getUserFromXML($xml_element);
        } catch (UserNotFoundException $exception) {
            $this->logger->error(
                'It seems that the user referenced by ' . (string) $xml_element
                . ' (format = ' . (string) $xml_element['format'] . ')'
                . ' does not match an existing user.'
            );
            throw $exception;
        }
    }

    /**
     * @throws UserNotFoundException
     */
    private function getUserFromXML(SimpleXMLElement $xml_element): PFUser
    {
        $format = (string) $xml_element['format'];

        switch ($format) {
            case 'email':
                $email = (string) $xml_element;
                $user  = $this->user_manager->getUserByEmail($email);
                if (! $user) {
                    $user = $this->user_manager->getUserAnonymous();
                    $user->setEmail((string) $xml_element);
                }
                break;
            case 'username':
                $username = (string) $xml_element;
                if ($username === self::$NONE_USERNAME) {
                    $user = $this->user_manager->getUserByUserName($username);
                } else {
                    $imported_user = $this->collection->getUserByUserName($username);
                    $user          = $imported_user->getRealUser($this->user_manager);
                }
                break;
            case 'ldap':
                $ldap_id       = (string) $xml_element;
                $imported_user = $this->collection->getUserByLdapId($ldap_id);
                $user          = $imported_user->getRealUser($this->user_manager);
                break;
            case 'id':
                $user_id       = (int) $xml_element;
                $imported_user = $this->collection->getUserById($user_id);
                $user          = $imported_user->getRealUser($this->user_manager);
                break;
            default:
                // should not get here since xml is validated beforehand
                throw new RuntimeException("Unknown user format $format");
        }

        return $user;
    }
}
