<?php
/**
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

namespace Tuleap\AgileDashboard\Workflow\PostAction\Update\Internal;

use Transition;
use Tuleap\AgileDashboard\Workflow\AddToTopBacklogPostActionDao;

class AddToTopBacklogValueRepository
{
    /**
     * @var AddToTopBacklogPostActionDao
     */
    private $add_to_top_backlog_post_action_dao;

    public function __construct(AddToTopBacklogPostActionDao $add_to_top_backlog_post_action_dao)
    {
        $this->add_to_top_backlog_post_action_dao = $add_to_top_backlog_post_action_dao;
    }

    public function create(Transition $transition): void
    {
        $this->add_to_top_backlog_post_action_dao->createPostActionForTransitionId(
            (int) $transition->getId()
        );
    }

    public function deleteAllByTransition(Transition $transition): void
    {
        $this->add_to_top_backlog_post_action_dao->deleteTransitionPostActions(
            (int) $transition->getId()
        );
    }
}
