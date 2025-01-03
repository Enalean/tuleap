<?php
/**
 * Copyright (c) Enalean, 2014 - Present. All Rights Reserved.
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

use Tuleap\AgileDashboard\Milestone\Pane\Planning\PlanningV2Presenter;
use Tuleap\AgileDashboard\Milestone\Pane\Planning\PlanningV2PaneInfo;

/**
 * I facilitate the association of Backlog Elements with sub-milestones
 *
 */
class AgileDashboard_Milestone_Pane_Planning_PlanningV2Pane extends AgileDashboard_Pane
{
    /** @var PlanningV2PaneInfo */
    private $info;

    /** @var PlanningV2Presenter */
    private $presenter;

    public function __construct(
        PlanningV2PaneInfo $info,
        PlanningV2Presenter $presenter,
    ) {
        $this->info      = $info;
        $this->presenter = $presenter;
    }

    /**
     * @return string
     */
    public function getIdentifier()
    {
        return $this->info->getIdentifier();
    }

    /**
     * @see AgileDashboard_Pane::getFullContent()
     */
    public function getFullContent()
    {
        return $this->getPaneContent();
    }

    /**
     * @see AgileDashboard_Pane::getMinimalContent()
     */
    public function getMinimalContent()
    {
        return '';
    }

    public function getBodyClass(): array
    {
        return ['has-sidebar-with-pinned-header'];
    }

    public function shouldIncludeFatCombined(): bool
    {
        return true;
    }

    /**
     * @see templates/pane-planning-v2.mustache
     */
    private function getPaneContent()
    {
        $renderer = TemplateRendererFactory::build()->getRenderer(AGILEDASHBOARD_TEMPLATE_DIR);
        return $renderer->renderToString('pane-planning-v2', $this->presenter);
    }
}
