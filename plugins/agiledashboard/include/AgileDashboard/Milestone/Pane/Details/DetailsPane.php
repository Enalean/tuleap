<?php
/**
 * Copyright (c) Enalean, 2013 - 2018. All Rights Reserved.
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

use AgileDashboard_Pane;
use TemplateRendererFactory;

/**
 * I display the content of a milestone in a pane
 *
 * The content of a "release" is all "stories" (open and closed) that belongs to
 * the release (aka their "epic" parent are planned into the release)
 */
class DetailsPane extends AgileDashboard_Pane
{
    /** @var DetailsPaneInfo */
    private $info;

    /** @var DetailsPresenter */
    private $presenter;

    public function __construct(
        DetailsPaneInfo $info,
        DetailsPresenter $presenter
    ) {
        $this->info      = $info;
        $this->presenter = $presenter;
    }

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

    private function getPaneContent()
    {
        $renderer = TemplateRendererFactory::build()->getRenderer(AGILEDASHBOARD_TEMPLATE_DIR);

        return $renderer->renderToString($this->presenter->getTemplateName(), $this->presenter);
    }
}
