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

//phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
class UserXMLExporter
{

    /**
     * @var UserManager
     */
    private $user_manager;

    /**
     * @var UserXMLExportedCollection
     */
    private $collection;

    public function __construct(UserManager $user_manager, UserXMLExportedCollection $collection)
    {
        $this->user_manager = $user_manager;
        $this->collection   = $collection;
    }

    public static function build(): self
    {
        return new self(
            UserManager::instance(),
            new UserXMLExportedCollection(new XML_RNGValidator(), new XML_SimpleXMLCDATAFactory())
        );
    }

    public function exportUser(PFUser $user, SimpleXMLElement $members_node, $child_name)
    {
        if ($user->getLdapId()) {
            $member_node = $members_node->addChild($child_name, $user->getLdapId());
            $member_node->addAttribute('format', 'ldap');
        } else {
            $member_node = $members_node->addChild($child_name, $user->getUserName());
            $member_node->addAttribute('format', 'username');
        }
        $this->collection->add($user);
    }

    public function exportUserByUserId($user_id, SimpleXMLElement $members_node, $child_name)
    {
        $user = $this->user_manager->getUserById($user_id);

        $this->exportUser($user, $members_node, $child_name);
    }

    public function exportUserByMail($email, SimpleXMLElement $members_node, $child_name)
    {
        $member_node = $members_node->addChild($child_name, $email);
        $member_node->addAttribute('format', 'email');
    }
}
