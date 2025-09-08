<?php
/**
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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

namespace Tuleap\Gitlab\Group;

use Tuleap\NeverThrow\Err;
use Tuleap\NeverThrow\Fault;
use Tuleap\NeverThrow\Ok;
use Tuleap\NeverThrow\Result;

final class GroupLinkRetriever implements RetrieveGroupLink
{
    public function __construct(private RetrieveGroupLinkById $group_link_retriever)
    {
    }

    /**
     * @return Ok<GroupLink> | Err<Fault>
     */
    #[\Override]
    public function retrieveGroupLink(int $group_link_id): Ok|Err
    {
        $group_link = $this->group_link_retriever->retrieveGroupLink($group_link_id);
        if (! $group_link) {
            return Result::err(GroupLinkNotFoundFault::fromId($group_link_id));
        }
        return Result::ok($group_link);
    }
}
