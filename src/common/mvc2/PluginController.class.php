<?php
/**
 * Copyright (c) Enalean, 2012 - Present. All Rights Reserved.
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

/**
 * Base class for plugin controllers
 *
 * @see MVC2_Controller
 */
abstract class MVC2_PluginController extends MVC2_Controller
{

    protected $group_id;

    protected function getTemplatesDir()
    {
        return ForgeConfig::get('codendi_dir') . '/plugins/' . $this->base_name . '/templates';
    }

    /**
     * @psalm-return never-return
     */
    protected function redirect($query_parts): void
    {
        $redirect = http_build_query($query_parts);
        $layout = $GLOBALS['Response'];
        assert($layout instanceof \Tuleap\Layout\BaseLayout);
        $layout->redirect('/plugins/' . $this->base_name . '/?' . $redirect);
    }

    /**
     * @throws Exception
     */
    protected function checkUserIsAdmin(): void
    {
        $project = $this->request->getProject();
        $user    = $this->request->getCurrentUser();
        if (! $user->isAdmin($project->getID()) && ! $user->isSuperUser()) {
            $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('global', 'perm_denied'));
            $this->redirect(['group_id' => $this->group_id]);
            // the below is only run by tests (redirect should exit but is mocked)
            throw new Exception($GLOBALS['Language']->getText('global', 'perm_denied'));
        }
    }
}
