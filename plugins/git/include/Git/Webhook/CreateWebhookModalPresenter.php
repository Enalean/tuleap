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

namespace Tuleap\Git\Webhook;

use GitRepository;

class CreateWebhookModalPresenter
{
    public $title;
    public $btn_cancel;
    public $csrf_token;
    public $project_id;
    public $repository_id;
    public $label;
    public $save;

    public function __construct(GitRepository $repository)
    {
        $this->project_id     = $repository->getProjectId();
        $this->repository_id  = $repository->getId();

        $this->title      = $GLOBALS['Language']->getText('plugin_git', 'settings_hooks_create');
        $this->desc       = $GLOBALS['Language']->getText('plugin_git', 'settings_hooks_create_desc');
        $this->label      = $GLOBALS['Language']->getText('plugin_git', 'settings_hooks_create_label');
        $this->save       = $GLOBALS['Language']->getText('plugin_git', 'settings_hooks_create');
        $this->btn_cancel = $GLOBALS['Language']->getText('global', 'btn_cancel');
    }
}
