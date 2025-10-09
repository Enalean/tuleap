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

namespace Tuleap\AgileDashboard\Milestone\Pane\Planning;

use Override;
use TemplateRendererFactory;
use Tuleap\AgileDashboard\Milestone\Pane\AgileDashboardPane;

/**
 * I facilitate the association of Backlog Elements with sub-milestones
 *
 */
class PlanningV2Pane extends AgileDashboardPane
{
    public function __construct(
        private readonly PlanningV2PaneInfo $info,
        private readonly PlanningV2Presenter $presenter,
    ) {
    }

    #[Override]
    public function getIdentifier(): string
    {
        return $this->info->getIdentifier();
    }

    /**
     * @see AgileDashboardPane::getFullContent()
     */
    #[Override]
    public function getFullContent(): string
    {
        return $this->getPaneContent();
    }

    /**
     * @see AgileDashboardPane::getMinimalContent()
     */
    #[Override]
    public function getMinimalContent(): string
    {
        return '';
    }

    #[Override]
    public function getBodyClass(): array
    {
        return ['has-sidebar-with-pinned-header'];
    }

    #[Override]
    public function shouldIncludeFatCombined(): bool
    {
        return true;
    }

    /**
     * @see templates/pane-planning-v2.mustache
     */
    private function getPaneContent(): string
    {
        $renderer = TemplateRendererFactory::build()->getRenderer(AGILEDASHBOARD_TEMPLATE_DIR);
        return $renderer->renderToString('pane-planning-v2', $this->presenter);
    }
}
