<?php
/**
 * Copyright (c) Enalean, 2016 - Present. All Rights Reserved.
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
use Tuleap\BuildVersion\FlavorFinderFromFilePresence;
use Tuleap\BuildVersion\VersionPresenter;
use Tuleap\Layout\CssAsset;
use Tuleap\Layout\JavascriptAsset;
use Tuleap\Layout\SidebarPresenter;

class AdminPageRenderer
{
    public function header($title, $is_framed = true)
    {
        $body_class = [];

        $GLOBALS['HTML']->header(
            array(
                'in_siteadmin' => true,
                'title'        => $title,
                'body_class'   => $body_class,
                'main_classes' => $is_framed ? array('tlp-framed') : array(),
                'sidebar'      => new SidebarPresenter(
                    'siteadmin-sidebar',
                    $this->renderSideBar(),
                    VersionPresenter::fromFlavorFinder(new FlavorFinderFromFilePresence())
                )
            )
        );
    }

    public function footer()
    {
        $GLOBALS['HTML']->footer(array());
    }

    public function renderAPresenter($title, $template_path, $template_name, $presenter)
    {
        $this->header($title);
        $this->renderToPage($template_path, $template_name, $presenter);
        $this->footer();
    }

    public function renderANoFramedPresenter($title, $template_path, $template_name, $presenter)
    {
        $this->header($title, false);
        $this->renderToPage($template_path, $template_name, $presenter);
        $this->footer();
    }

    public function renderToPage($template_path, $template_name, $presenter)
    {
        $this->getRenderer($template_path)->renderToPage($template_name, $presenter);
    }


    private function renderSideBar()
    {
        $admin_sidebar_presenter = $this->getAdminSidebarPresenter();

        $renderer = $this->getRenderer(ForgeConfig::get('codendi_dir') . '/src/templates/admin/');

        return $renderer->renderToString('sidebar', $admin_sidebar_presenter);
    }

    private function getRenderer($template_path)
    {
        return TemplateRendererFactory::build()->getRenderer($template_path);
    }

    private function getAdminSidebarPresenter()
    {
        $builder   = new AdminSidebarPresenterBuilder();
        $presenter = $builder->build();

        return $presenter;
    }

    public function addJavascriptAsset(JavascriptAsset $asset): void
    {
        $GLOBALS['HTML']->addJavascriptAsset($asset);
    }

    public function addCssAsset(CssAsset $asset): void
    {
        $GLOBALS['HTML']->addCssAsset($asset);
    }
}
