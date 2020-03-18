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

class WebhookModalPresenter
{
    public $title;
    public $btn_cancel;
    public $csrf_token;
    public $project_id;
    public $repository_id;
    public $label;
    public $save;
    public $action;

    public function __construct(GitRepository $repository)
    {
        $this->project_id     = $repository->getProjectId();
        $this->repository_id  = $repository->getId();

        $this->title      = dgettext('tuleap-git', 'Add generic webhook');
        $this->desc       = dgettext('tuleap-git', 'Add a target which will be called everytime a git push will be done. The URL will be called using HTTP POST method.');
        $this->label      = dgettext('tuleap-git', 'Target URL');
        $this->save       = dgettext('tuleap-git', 'Add generic webhook');
        $this->btn_cancel = $GLOBALS['Language']->getText('global', 'btn_cancel');
    }
}
