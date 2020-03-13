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

namespace Tuleap\Git\Webhook;

use GitRepository;
use CSRFSynchronizerToken;

class GenericWebhookPresenter implements WebhookPresenter
{
    public $use_default_edit_modal;
    public $repository_id;
    public $hooklogs;
    public $csrf_token;
    public $webhook_url;
    public $modal_logs_time_label;
    public $modal_logs_info_label;
    public $logs_for;
    public $empty_logs;
    public $remove_webhook_desc;
    public $remove_webhook_confirm;
    public $remove_form_action;
    public $id;

    /**
     * @var string
     */
    public $purified_last_push_info;

    public function __construct(
        GitRepository $repository,
        $id,
        $webhook_url,
        array $hooklogs,
        CSRFSynchronizerToken $csrf,
        $use_default_edit_modal
    ) {
        $this->id                      = $id;
        $this->webhook_url             = $webhook_url;
        $this->purified_last_push_info = '';
        $this->hooklogs                = $hooklogs;
        $this->csrf_token              = $csrf->getToken();
        $this->use_default_edit_modal  = $use_default_edit_modal;

        $this->repository_id = $repository->getId();

        $this->modal_logs_time_label = dgettext('tuleap-git', 'Time');
        $this->modal_logs_info_label = dgettext('tuleap-git', 'Return type');

        $this->remove_webhook_desc    = dgettext('tuleap-git', 'You are about to remove the webhook. Please confirm your action.');
        $this->remove_webhook_confirm = dgettext('tuleap-git', 'Confirm deletion');
        $this->remove_form_action     = GIT_BASE_URL . '/?group_id=' . (int) $repository->getProjectId();

        $this->logs_for   = sprintf(dgettext('tuleap-git', 'Logs for %1$s'), $webhook_url);
        $this->empty_logs = dgettext('tuleap-git', 'No logs yet');

        if (count($hooklogs) > 0) {
            $this->purified_last_push_info = $hooklogs[0]->purified_information;
        }
    }

    public function isSimple(): bool
    {
        return false;
    }
}
