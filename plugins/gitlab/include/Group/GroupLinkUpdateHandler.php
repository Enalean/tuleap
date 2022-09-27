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

use Tuleap\Gitlab\Core\ProjectRetriever;
use Tuleap\Gitlab\Permission\GitAdministratorChecker;
use Tuleap\NeverThrow\Err;
use Tuleap\NeverThrow\Fault;
use Tuleap\NeverThrow\Ok;

final class GroupLinkUpdateHandler
{
    public function __construct(
        private ProjectRetriever $project_retriever,
        private GitAdministratorChecker $checker,
        private GroupLinkRetriever $group_link_retriever,
        private GroupUpdator $updater,
    ) {
    }

    /**
     * @return Ok<GroupLink> | Err<Fault>
     */
    public function handleGroupLinkUpdate(UpdateGroupLinkCommand $command): Ok|Err
    {
        return $this->group_link_retriever->retrieveGroupLink($command->group_link_id)
            ->andThen(fn(GroupLink $group_link) => $this->project_retriever->retrieveProject($group_link->project_id)
                ->andThen(
                    fn(\Project $project) => $this->checker->checkUserIsGitAdministrator(
                        $project,
                        $command->user
                    )
                )
                ->andThen(fn() => $this->updater->updateGroupLink($group_link, $command))
                ->andThen(fn() => $this->group_link_retriever->retrieveGroupLink($group_link->id)
                    ->mapErr(static fn() => Fault::fromMessage("Did not find the GitLab group link we've just updated"))));
    }
}
