<?php
/**
 * Copyright (c) Enalean, 2012. All Rights Reserved.
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

class Planning_ShortAccessMilestonePresenter extends Planning_MilestoneLinkPresenter {

    /** @var bool */
    private $is_latest = false;

    /** @var User */
    private $user;

    /** @var Planning_ShortAccess */
    private $short_access;

    public function __construct(Planning_ShortAccess $short_access, Planning_Milestone $milestone, User $user) {
        parent::__construct($milestone);
        $this->short_access = $short_access;
        $this->user         = $user;
    }

    public function additionalPanes() {
        $additional_panes = array();
        EventManager::instance()->processEvent(
            AGILEDASHBOARD_EVENT_ADDITIONAL_PANES_ON_MILESTONE,
            array(
                'milestone' => $this->milestone,
                'panes'     => &$additional_panes,
                'user'      => $this->user,
            )
        );
        return $additional_panes;
    }


    public function setIsLatest($is_latest) {
        $this->is_latest = $is_latest;
    }

    public function isLatest() {
        return $this->is_latest;
    }

    public function is_active() {
        return $this->isLatest() && $this->short_access->isLatest();
    }
}
?>
