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
 * I display the content of a milestone in a pane
 *
 * The content of a "release" is all "stories" (open and closed) that belongs to
 * the release (aka their "epic" parent are planned into the release)
 */
class AgileDashboard_Milestone_Pane_Content_ContentPane extends AgileDashboard_Pane {

    /** @var Tracker_Artifact_Burndown_PaneInfo */
    private $info;

    /** @var AgileDashboard_Milestone_Pane_ContentPresenter */
    private $presenter;

    public function __construct(AgileDashboard_Milestone_Pane_Content_ContentPaneInfo $info, AgileDashboard_Milestone_Pane_Content_ContentPresenter $presenter) {
        $this->info      = $info;
        $this->presenter = $presenter;
    }

    public function getIdentifier() {
        return $this->info->getIdentifier();
    }

    public function getUriForMilestone(Planning_Milestone $milestone) {
        return $this->info->getUriForMilestone($milestone);
    }

    /**
     * @see AgileDashboard_Pane::getFullContent()
     */
    public function getFullContent() {
        return $this->getPaneContent();
    }

    /**
     * @see AgileDashboard_Pane::getMinimalContent()
     */
    public function getMinimalContent() {
        return '';
    }

    private function getPaneContent() {
        $renderer  = TemplateRendererFactory::build()->getRenderer(AGILEDASHBOARD_TEMPLATE_DIR);
        return $renderer->renderToString('pane-content', $this->presenter);
    }
}

?>
