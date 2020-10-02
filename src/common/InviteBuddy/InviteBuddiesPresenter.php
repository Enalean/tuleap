<?php
/*
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\InviteBuddy;

use EventManager;

/**
 * @psalm-immutable
 */
class InviteBuddiesPresenter
{
    /**
     * @var bool
     */
    public $can_buddies_be_invited;
    /**
     * @var bool
     */
    public $is_limit_reached;
    /**
     * @var string
     */
    public $instance_name;
    /**
     * @var int
     */
    public $max_limit_by_day;

    public function __construct(
        bool $can_buddies_be_invited,
        bool $is_limit_reached,
        int $max_limit_by_day
    ) {
        $this->can_buddies_be_invited = $can_buddies_be_invited;
        $this->is_limit_reached       = $is_limit_reached;
        $this->instance_name          = (string) \ForgeConfig::get('sys_name');
        $this->max_limit_by_day       = $max_limit_by_day;
    }

    public static function build(\PFUser $user): self
    {
        $event_manager              = \EventManager::instance();
        $limit_checker              = new InvitationLimitChecker(
            new InvitationDao(),
            new InviteBuddyConfiguration($event_manager)
        );
        $invite_buddy_configuration = new InviteBuddyConfiguration(EventManager::instance());
        $can_buddies_be_invited     = $invite_buddy_configuration->canBuddiesBeInvited($user);
        $is_limit_reached           = $limit_checker->isLimitReached($user);
        $max_limit_by_day           = $invite_buddy_configuration->getNbMaxInvitationsByDay();

        return new self($can_buddies_be_invited, $is_limit_reached, $max_limit_by_day);
    }
}
