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


class GitViews_RepoManagement_Pane_Hooks extends GitViews_RepoManagement_Pane {

    const ID = 'hooks';

    /**
     * @see GitViews_RepoManagement_Pane::getIdentifier()
     */
    public function getIdentifier() {
        return self::ID;
    }

    /**
     * @see GitViews_RepoManagement_Pane::getTitle()
     */
    public function getTitle() {
        return $GLOBALS['Language']->getText('plugin_git', 'settings_hooks_title');
    }

    /**
     * @see GitViews_RepoManagement_Pane::getContent()
     */
    public function getContent() {
        $renderer = TemplateRendererFactory::build()->getRenderer(dirname(GIT_BASE_DIR).'/templates/settings');

        return $renderer->renderToString('hooks', $this);
    }

    public function title() {
        return $this->getTitle();
    }

    public function plugin_hooks() {
        $output = '';
        EventManager::instance()->processEvent(GIT_ADDITIONAL_HOOKS, array(
            'request'    => $this->request,
            'repository' => $this->repository,
            'output'     => &$output,
        ));
        return $output;
    }
}
