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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

namespace Tuleap\FRS;

use TemplateRendererFactory;
use Project;
use ForgeConfig;

class PermissionController extends BaseFrsPresenter
{
    public function displayToolbar(Project $project)
    {
        $renderer          = TemplateRendererFactory::build()->getRenderer($this->getTemplateDir());

        $toolbar_presenter = new ToolbarPresenter($project);

        $toolbar_presenter->setPermissionIsActive();
        $toolbar_presenter->displaySectionNavigation();

        echo $renderer->renderToString('toolbar-presenter', $toolbar_presenter);
    }

    public function displayPermissions()
    {
        $renderer  = TemplateRendererFactory::build()->getRenderer($this->getTemplateDir());
        $presenter = new PermissionPresenter();

        echo $renderer->renderToString('permissions-presenter', $presenter);

    }

    private function getTemplateDir()
    {
        return ForgeConfig::get('codendi_dir') .'/src/templates/frs';
    }
}
