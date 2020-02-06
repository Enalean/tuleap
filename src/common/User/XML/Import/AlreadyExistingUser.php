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
use Psr\Log\LoggerInterface;
use UserManager;

class AlreadyExistingUser extends ActionToBeTakenForUser implements ReadyToBeImportedUser
{

    private static $ALLOWED_ACTIONS = array(ToBeActivatedUser::ACTION, ToBeMappedUser::ACTION);

    /** @var PFUser */
    private $user;

    public function __construct(PFUser $user, $original_user_id, $original_ldap_id)
    {
        parent::__construct(
            $user->getUserName(),
            $user->getRealName(),
            $user->getEmail(),
            $original_user_id,
            $original_ldap_id
        );

        $this->user = $user;
    }

    /** @return array */
    public function getCSVData()
    {
        return array();
    }

    public function isActionAllowed($action)
    {
        return in_array($action, self::$ALLOWED_ACTIONS);
    }

    public function process(UserManager $user_manager, LoggerInterface $logger)
    {
        $logger->info("Nothing to do for $this->username");
    }

    public function getRealUser(UserManager $user_manager)
    {
        return $this->user;
    }
}
