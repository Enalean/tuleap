<?php
/**
 * Copyright (c) Enalean SAS 2015. All rights reserved
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

class Tracker_Artifact_PriorityHistoryManager {

    /**
     * @var Tracker_Artifact_PriorityHistoryDao
     */
    private $priority_history_dao;

    /**
     * @var UserManager
     */
    private $user_manager;


    public function __construct(Tracker_Artifact_PriorityHistoryDao $priority_history_dao, UserManager $user_manager) {
        $this->priority_history_dao = $priority_history_dao;
        $this->user_manager         = $user_manager;
    }

    public function logPriorityChange($artifact_higher_id, $artifact_lower_id) {
        $this->priority_history_dao->logPriorityChange(
            $artifact_higher_id,
            $artifact_lower_id,
            $this->user_manager->getCurrentUser()->getId(),
            time()
        );
    }

}