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

/**
 * Given a comma separated list of email address, I return a list of valid address.
 *
 * A valid address is the address of an active/restricted/… user or an external address.
 */
class Mail_RecipientListBuilder
{

    /**
     * @var UserManager
     */
    private $user_manager;

    private $allowed_status = [
        PFUser::STATUS_ACTIVE ,
        PFUser::STATUS_RESTRICTED,
        PFUser::STATUS_PENDING,
        PFUser::STATUS_VALIDATED,
        PFUser::STATUS_VALIDATED_RESTRICTED
    ];

    public function __construct(UserManager $user_manager)
    {
        $this->user_manager = $user_manager;
    }

    /**
     * @param PFUser[] $users
     *
     * @return array of array('email' => 'jdoe@example.com', 'real_name' => 'John Doe')
     */
    public function getValidRecipientsFromUsers(array $users)
    {
        $recipients = [];
        foreach ($users as $user) {
            if ($this->hasAllowedStatus($user)) {
                $this->addUser($recipients, $user);
            }
        }

        return $recipients;
    }

    /**
     * @param string[] $addresses array('jdoe@example.com', …)
     *
     * @return array of array('email' => 'jdoe@example.com', 'real_name' => 'John Doe')
     */
    public function getValidRecipientsFromAddresses(array $addresses)
    {
        $recipients = [];
        foreach ($addresses as $address) {
            $matching_users = $this->user_manager->getAllUsersByEmail($address);
            if ($matching_users) {
                $this->addUserFromMatchingOnes($recipients, $matching_users);
            } else {
                $this->fallbackOnFindUser($recipients, $address);
            }
        }

        return $recipients;
    }

    private function fallbackOnFindUser(array &$recipients, $identifier)
    {
        $user = $this->user_manager->findUser($identifier);
        if ($user) {
            $this->addUser($recipients, $user);
        } else {
            $this->fallbackOnExternalAddress($recipients, $identifier);
        }
    }

    private function fallbackOnExternalAddress(array &$recipients, $address)
    {
        $this->addExternalAddress($recipients, $address);
    }

    private function addUserFromMatchingOnes(array &$recipients, array $matching_users)
    {
        foreach ($matching_users as $user) {
            if ($this->hasAllowedStatus($user)) {
                $this->addUser($recipients, $user);
                break;
            }
        }
    }

    private function hasAllowedStatus(PFUser $user)
    {
        return in_array($user->getStatus(), $this->allowed_status);
    }

    private function addExternalAddress(array &$recipients, $address)
    {
        $recipients[] = [
            'email'     => $address,
            'real_name' => ''
        ];
    }

    private function addUser(array &$recipients, PFUser $user)
    {
        $recipients[] = [
            'email'     => $user->getEmail(),
            'real_name' => $user->getRealName()
        ];
    }
}
