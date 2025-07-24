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

namespace Tuleap\Baseline\Stub;

use Tuleap\Baseline\Adapter\UserProxy;
use Tuleap\Baseline\Domain\CurrentUserProvider;
use Tuleap\Baseline\Domain\UserIdentifier;
use Tuleap\Test\Builders\UserTestBuilder;

/**
 * Implementation of CurrentUserProvider used for tests.
 */
class CurrentUserProviderStub implements CurrentUserProvider
{
    /** @var UserIdentifier */
    private $user;

    public function __construct()
    {
        $this->user = UserProxy::fromUser(
            UserTestBuilder::aUser()->withTimezone('GMT+3')->build()
        );
    }

    public function setUser(UserIdentifier $current_user): void
    {
        $this->user = $current_user;
    }

    #[\Override]
    public function getUser(): UserIdentifier
    {
        return $this->user;
    }
}
