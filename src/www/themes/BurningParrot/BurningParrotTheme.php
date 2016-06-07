<?php
/*
 * Copyright (c) Enalean, 2016. All Rights Reserved.
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, If not, see <http://www.gnu.org/licenses/>
 */

namespace Tuleap\Theme\BurningParrot;

use Tuleap\Layout\BaseLayout;
use Widget_Static;
use TemplateRendererFactory;

class BurningParrotTheme extends BaseLayout
{
    /** @var \MustacheRenderer */
    private $renderer;

    public function __construct()
    {
        $this->renderer = TemplateRendererFactory::build()->getRenderer($this->getTemplateDir());
    }

    public function isLabFeature()
    {
        return true;
    }

    public function header(array $params)
    {
    }

    public function footer(array $params)
    {
    }

    public function displayStaticWidget(Widget_Static $widget)
    {
        $this->renderer->renderToPage('widget', $widget);
    }

    private function getTemplateDir()
    {
        return __DIR__ . '/templates/';
    }
}
