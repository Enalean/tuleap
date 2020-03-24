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

namespace Tuleap\PullRequest\REST\v1\Reviewer;

use PFUser;
use Tuleap\User\REST\MinimalUserRepresentation;

final class ReviewersRepresentation
{
    /**
     * @var MinimalUserRepresentation[]
     * @psalm-var list<MinimalUserRepresentation>
     * @psalm-readonly
     */
    public $users;

    private function __construct(MinimalUserRepresentation ...$user_representations)
    {
        $this->users = $user_representations;
    }

    public static function fromUsers(PFUser ...$users): self
    {
        $representations = [];

        foreach ($users as $user) {
            $representation = new MinimalUserRepresentation();
            $representation->build($user);
            $representations[] = $representation;
        }

        return new self(...$representations);
    }
}
