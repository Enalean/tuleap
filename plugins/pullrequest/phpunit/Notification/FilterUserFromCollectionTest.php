<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

namespace Tuleap\PullRequest\Notification;

use PHPUnit\Framework\TestCase;

final class FilterUserFromCollectionTest extends TestCase
{
    public function testSpecificUserCanBeRemovedFromTheCollection(): void
    {
        $user_to_remove = $this->buildUser(102);
        $another_user   = $this->buildUser(103);

        $filter = new FilterUserFromCollection();

        $this->assertEqualsCanonicalizing(
            [$another_user],
            $filter->filter($user_to_remove, $user_to_remove, $another_user)
        );
    }

    private function buildUser(int $user_id): \PFUser
    {
        return new \PFUser(['user_id' => $user_id, 'language_id' => 'en_US']);
    }
}
