<?php
/**
 * Copyright (c) Enalean, 2012 - 2016. All Rights Reserved.
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

namespace Tuleap\Git\GitViews\RepoManagement\Pane;

use TemplateRendererFactory;

class GeneralSettings extends Pane
{

    /**
     * @see GitViews_RepoManagement_Pane::getIdentifier()
     */
    public function getIdentifier()
    {
        return 'settings';
    }

    /**
     * @see GitViews_RepoManagement_Pane::getTitle()
     */
    public function getTitle()
    {
        return dgettext('tuleap-git', 'General settings');
    }

    /**
     * @see GitViews_RepoManagement_Pane::getContent()
     */
    public function getContent()
    {
        $renderer = TemplateRendererFactory::build()->getRenderer(dirname(GIT_BASE_DIR) . '/templates/settings');

        return $renderer->renderToString('general-settings', $this);
    }

    public function title()
    {
        return $this->getTitle();
    }

    public function project_id()
    {
        return $this->repository->getProjectId();
    }

    public function pane_identifier()
    {
        return $this->getIdentifier();
    }

    public function repository_id()
    {
        return $this->repository->getId();
    }

    public function repository_description_label()
    {
        return dgettext('tuleap-git', 'Description');
    }

    public function description()
    {
        return $this->repository->getDescription();
    }

    public function save_label()
    {
        return dgettext('tuleap-git', 'Save');
    }
}
