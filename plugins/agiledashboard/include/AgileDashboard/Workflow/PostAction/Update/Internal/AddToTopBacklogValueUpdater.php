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
use Tuleap\AgileDashboard\Workflow\PostAction\Update\AddToTopBacklogValue;
use Tuleap\Tracker\Workflow\PostAction\Update\Internal\PostActionUpdater;
use Tuleap\Tracker\Workflow\PostAction\Update\PostActionCollection;

class AddToTopBacklogValueUpdater implements PostActionUpdater
{
    /**
     * @var AddToTopBacklogValueRepository
     */
    private $add_to_top_backlog_value_repository;

    public function __construct(AddToTopBacklogValueRepository $add_to_top_backlog_value_repository)
    {
        $this->add_to_top_backlog_value_repository = $add_to_top_backlog_value_repository;
    }

    public function updateByTransition(PostActionCollection $actions, Transition $transition): void
    {
        $add_to_top_backlog_post_action_value = [];
        foreach ($actions->getExternalPostActionsValue() as $external_post_action_value) {
            if ($external_post_action_value instanceof AddToTopBacklogValue) {
                $add_to_top_backlog_post_action_value[] = $external_post_action_value;
            }
        }

        $this->add_to_top_backlog_value_repository->deleteAllByTransition($transition);

        if (count($add_to_top_backlog_post_action_value) === 0) {
            return;
        }

        $this->add_to_top_backlog_value_repository->create($transition);
    }
}
