<?php
/**
 * Copyright (c) Enalean, 2014-Present. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

namespace Tuleap\Kanban\REST\v1;

use Tuleap\Kanban\Kanban;
use Tuleap\Kanban\KanbanActionsChecker;
use Tuleap\Kanban\KanbanColumnFactory;
use Tuleap\Kanban\KanbanUserPreferences;
use Exception;
use PFUser;
use Tuleap\Kanban\KanbanUserCantAddArtifactException;

final class KanbanRepresentationBuilder
{
    public function __construct(
        private readonly KanbanUserPreferences $user_preferences,
        private readonly KanbanColumnFactory $kanban_column_factory,
        private readonly KanbanActionsChecker $kanban_actions_checker,
    ) {
    }

    public function build(Kanban $kanban, PFUser $user): KanbanRepresentation
    {
        try {
            $this->kanban_actions_checker->checkUserCanAddArtifact($user, $kanban);
            $user_can_add_artifact = true;
        } catch (KanbanUserCantAddArtifactException $exception) {
            $user_can_add_artifact = false;
        } catch (\Tuleap\Kanban\KanbanSemanticStatusNotDefinedException $e) {
            $user_can_add_artifact = false;
        } catch (\Tuleap\Kanban\KanbanTrackerNotDefinedException $e) {
            $user_can_add_artifact = false;
        }

        try {
            $this->kanban_actions_checker->checkUserCanAddInPlace($user, $kanban);
            $user_can_add_in_place = true;
        } catch (Exception $exception) {
            $user_can_add_in_place = false;
        }

        try {
            $this->kanban_actions_checker->checkUserCanAddColumns($user, $kanban);
            $user_can_add_columns = true;
        } catch (Exception $exception) {
            $user_can_add_columns = false;
        }

        try {
            $this->kanban_actions_checker->checkUserCanReorderColumns($user, $kanban);
            $user_can_reorder_columns = true;
        } catch (Exception $exception) {
            $user_can_reorder_columns = false;
        }

        return KanbanRepresentation::build(
            $kanban,
            $this->kanban_column_factory,
            $this->user_preferences,
            $this->kanban_actions_checker,
            $user_can_add_columns,
            $user_can_reorder_columns,
            $user_can_add_in_place,
            $user_can_add_artifact,
            $user
        );
    }
}
