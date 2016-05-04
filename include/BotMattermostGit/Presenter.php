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

namespace Tuleap\BotMattermostGit;

use CSRFSynchronizerToken;
use GitRepository;
use GitViews_RepoManagement_Pane_Notification;
use Tuleap\BotMattermost\Bot\Bot;

class Presenter
{
    private $repository;
    private $bots;
    public  $csrf_input;

    public function __construct(CSRFSynchronizerToken $csrf, GitRepository $repository, array $bots)
    {
        $this->csrf_input = $csrf->fetchHTMLInput();
        $this->repository = $repository;
        $this->bots       = $bots;
    }

    public function project_id()
    {
        return $this->repository->getProjectId();
    }

    public function pane_identifier()
    {
        return GitViews_RepoManagement_Pane_Notification::ID;
    }

    public function repository_id()
    {
        return $this->repository->getId();
    }

    public function btn_save()
    {
        return $GLOBALS['Language']->getText('plugin_git', 'admin_save_submit');
    }

    public function title()
    {
        return $GLOBALS['Language']->getText('plugin_botmattermost_git', 'title');
    }

    public function bots()
    {
        return $this->bots;
    }

    public function botListIsEmpty()
    {
        return count($this->bots) === 0;
    }

    public function empty_bot_list()
    {
        return $GLOBALS['Language']->getText('plugin_botmattermost', 'configuration_empty_list');
    }

    public function table_col_name()
    {
        return $GLOBALS['Language']->getText('plugin_botmattermost', 'configuration_table_col_name');
    }

    public function table_col_webhook_url()
    {
        return $GLOBALS['Language']->getText('plugin_botmattermost', 'configuration_table_col_webhook_url');
    }

    public function table_title()
    {
        return $GLOBALS['Language']->getText('plugin_botmattermost', 'configuration_table_title');
    }

    public function description()
    {
        return $GLOBALS['Language']->getText('plugin_botmattermost_git', 'description');
    }
}
