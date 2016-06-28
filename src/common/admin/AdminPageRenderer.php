<?php
/**
 * Copyright (c) Enalean, 2016. All Rights Reserved.
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

namespace Tuleap\Admin;

use ForgeConfig;
use TemplateRendererFactory;
use Tuleap\Layout\SidebarPresenter;

class AdminPageRenderer
{
    public function header($title)
    {
        $GLOBALS['HTML']->header(
            array(
                'title'        => $title,
                'main_classes' => array('tlp-framed'),
                'sidebar'      => new SidebarPresenter('siteadmin-sidebar', $this->renderSideBar())
            )
        );
    }

    public function renderAPresenter($title, $template_path, $template_name, $presenter)
    {
        $this->header($title);
        $this->getRenderer($template_path)->renderToPage($template_name, $presenter);
        $GLOBALS['HTML']->footer(array());
    }

    private function renderSideBar()
    {
        $sidebar_presenter = array(/* later */);

        $renderer = $this->getRenderer(ForgeConfig::get('codendi_dir') . '/src/templates/admin/');

        return $renderer->renderToString('sidebar', $sidebar_presenter);
    }

    private function getRenderer($template_path)
    {
        return TemplateRendererFactory::build()->getRenderer($template_path);
    }
}
