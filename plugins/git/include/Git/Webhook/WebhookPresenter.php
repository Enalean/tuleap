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
use CSRFSynchronizerToken;

class WebhookPresenter
{
    public $repository_id;
    public $hooklogs;
    public $csrf_token;
    public $url;
    public $last_push_info;
    public $modal_logs_time_label;
    public $modal_logs_info_label;
    public $logs_for;
    public $empty_logs;
    public $remove_webhook_desc;
    public $remove_webhook_confirm;
    public $remove_form_action;

    public function __construct(GitRepository $repository, $id, $url, array $hooklogs, CSRFSynchronizerToken $csrf)
    {
        $this->id                   = $id;
        $this->url                  = $url;
        $this->last_push_info       = '';
        $this->hooklogs             = $hooklogs;
        $this->modal_logs_classname = 'modal-logs';
        $this->csrf_token           = $csrf->getToken();

        $this->repository_id = $repository->getId();

        $this->modal_logs_time_label = $GLOBALS['Language']->getText('plugin_git', 'settings_hooks_time_label');
        $this->modal_logs_info_label = $GLOBALS['Language']->getText('plugin_git', 'settings_hooks_info_label');

        $this->remove_webhook_desc    = $GLOBALS['Language']->getText('plugin_git', 'settings_hooks_remove_webhook_desc');
        $this->remove_webhook_confirm = $GLOBALS['Language']->getText('plugin_git', 'settings_hooks_remove_webhook_confirm');
        $this->remove_form_action     = '';

        $this->logs_for   = $GLOBALS['Language']->getText('plugin_git', 'settings_hooks_logs_for', $url);
        $this->empty_logs = $GLOBALS['Language']->getText('plugin_git', 'settings_hooks_empty_logs');
    }
}
