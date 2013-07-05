<?php
/**
 * Copyright (c) Enalean, 2013. All Rights Reserved.
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

/**
 * I build a collection of AgileDashboard_PaneIconLinkPresenter
 */
class AgileDashboard_PaneIconLinkPresenterCollectionFactory {

    /** @var Planning_MilestonePaneFactory */
    public $pane_factory;

    public function __construct(AgileDashboard_PaneInfoFactory $pane_factory) {
         $this->pane_factory = $pane_factory;
    }

    /**
     * @return AgileDashboard_PaneIconLinkPresenter[]
     */
    public function getIconLinkPresenterCollection(Planning_Milestone $milestone) {
        $pane_info_collection = $this->pane_factory->getListOfPaneInfo($milestone);
        return $this->getPresenterCollection($milestone, $pane_info_collection);
    }

    /**
     * @return AgileDashboard_PaneIconLinkPresenter[]
     */
    public function getIconLinkPresenterCollectionWithoutLegacyOne(Planning_Milestone $milestone) {
        $pane_info_collection = $this->pane_factory->getListOfPaneInfoWithoutLegacyOne($milestone);
        return $this->getPresenterCollection($milestone, $pane_info_collection);
    }

    private function getPresenterCollection(Planning_Milestone $milestone, array $pane_info_collection) {
        $presenter_collection = array();
        foreach ($pane_info_collection as $pane_info) {
            $presenter_collection[] = $pane_info->getIconTemplateParametersForMilestone($milestone);
        }
        return $presenter_collection;
    }
}
?>
