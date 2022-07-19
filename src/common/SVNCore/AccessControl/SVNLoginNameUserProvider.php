<?php
/**
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

declare(strict_types=1);

namespace Tuleap\SVNCore\AccessControl;

use Psr\EventDispatcher\EventDispatcherInterface;

class SVNLoginNameUserProvider
{
    public function __construct(private \UserManager $user_manager, private EventDispatcherInterface $event_dispatcher)
    {
    }

    public function getUserFromSVNLoginName(string $svn_login_name, \Project $project): ?\PFUser
    {
        $event = $this->event_dispatcher->dispatch(new UserRetrieverBySVNLoginNameEvent($project, $svn_login_name));

        if ($event->user) {
            return $event->user;
        }

        if (! $event->can_user_be_provided_by_other_means) {
            return null;
        }

        return $this->user_manager->getUserByUserName($svn_login_name);
    }
}
