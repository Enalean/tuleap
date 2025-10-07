<?php
/**
 * Copyright (c) Enalean, 2013 - Present. All Rights Reserved.
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

namespace Tuleap\AgileDashboard\Milestone\Pane\Details;

use Override;
use TemplateRendererFactory;
use Tuleap\AgileDashboard\Milestone\Pane\AgileDashboardPane;

/**
 * I display the content of a milestone in a pane
 *
 * The content of a "release" is all "stories" (open and closed) that belongs to
 * the release (aka their "epic" parent are planned into the release)
 */
class DetailsPane extends AgileDashboardPane
{
    public function __construct(
        private readonly DetailsPaneInfo $info,
        private readonly DetailsPresenter $presenter,
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

    private function getPaneContent(): string
    {
        $renderer = TemplateRendererFactory::build()->getRenderer(AGILEDASHBOARD_TEMPLATE_DIR);

        return $renderer->renderToString($this->presenter->getTemplateName(), $this->presenter);
    }
}
