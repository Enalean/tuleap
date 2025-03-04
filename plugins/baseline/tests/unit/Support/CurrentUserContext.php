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

namespace Tuleap\Baseline\Support;

use PHPUnit\Framework\Attributes\Before;
use Tuleap\Baseline\Adapter\UserProxy;
use Tuleap\Baseline\Domain\UserIdentifier;
use Tuleap\Test\Builders\UserTestBuilder;

trait CurrentUserContext
{
    /** @var UserIdentifier */
    protected $current_user;

    /** @var \PFUser */
    protected $current_tuleap_user;

    #[Before]
    protected function buildCurrentUser(): void
    {
        $this->current_tuleap_user = UserTestBuilder::aUser()->build();
        $this->current_user        = UserProxy::fromUser($this->current_tuleap_user);
    }
}
