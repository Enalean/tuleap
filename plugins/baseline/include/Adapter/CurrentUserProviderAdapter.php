<?php
/**
 * Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\Baseline\Adapter;

use PFUser;
use Tuleap\Baseline\Domain\CurrentUserProvider;
use UserManager;

class CurrentUserProviderAdapter implements CurrentUserProvider
{
    /** @var UserManager */
    private $current_user_manager;

    public function __construct(UserManager $current_user_manager)
    {
        $this->current_user_manager = $current_user_manager;
    }

    public function getUser(): PFUser
    {
        return $this->current_user_manager->getCurrentUser();
    }
}
