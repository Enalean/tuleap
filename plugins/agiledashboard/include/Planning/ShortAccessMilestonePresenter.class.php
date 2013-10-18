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

    /**
     * @var Planning_MilestoneFactory
     */
    private $milestone_factory;

    /** @var bool */
    private $is_latest = false;

    /** @var PFUser */
    private $user;

    /** @var Planning_ShortAccess */
    private $short_access;

    /** @var string */
    private $theme_path;

    /** @var AgileDashboard_PaneIconLinkPresenter[] */
    private $pane_icon_list;

    public function __construct(
        Planning_ShortAccess $short_access,
        Planning_Milestone $milestone,
        array $pane_icon_list,
        Planning_MilestoneFactory $milestone_factory,
        PFUser $user,
        $theme_path
    ) {
        parent::__construct($milestone);
        $this->milestone_factory = $milestone_factory;
        $this->pane_icon_list    = $pane_icon_list;
        $this->short_access      = $short_access;
        $this->user              = $user;
        $this->theme_path        = $theme_path;
    }

    /**
     * @return AgileDashboard_PaneIconLinkPresenter[]
     */
    public function getQuickLinkIconList() {
        return $this->pane_icon_list;
    }

    public function getContent() {
        $pane = null;
        EventManager::instance()->processEvent(
            AGILEDASHBOARD_EVENT_INDEX_PAGE,
            array(
                'milestone'   => $this->milestone,
                'user'        => $this->user,
                'pane'        => &$pane,
                'milestone_factory' => $this->milestone_factory,
            )
        );
        if ($pane) {
            return $pane->getMinimalContent();
        }
        return '';
    }

    public function getBacklogTrackersIds() {
        return $this->milestone->getTrackerId();
    }

    public function setIsLatest() {
        $this->is_latest = true;
    }

    public function isLatest() {
        return $this->is_latest;
    }

    public function is_active() {
        return $this->isLatest() && $this->short_access->isLatest() && count($this->pane_icon_list);
    }
}
?>
