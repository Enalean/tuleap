<?php
/**
 * Copyright (c) Enalean, 2017. All Rights Reserved.
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

namespace Tuleap\Project\Admin;

class WebhooksPresenter
{
    public $title;
    /**
     * @var bool
     */
    public $has_webhooks;
    /**
     * @var WebhookPresenter[]
     */
    public $webhooks;
    public $tab_title;
    public $pane_title;
    public $project_creation_description;
    public $name;
    public $url;
    public $last_push_date;
    public $last_push_status;
    public $no_webhooks;
    public $button_show_logs;
    public $close_modal;
    public $date_modal;
    public $status_modal;

    public function __construct($title, array $webhooks)
    {
        $this->title = $title;

        $this->webhooks     = $webhooks;
        $this->has_webhooks = !empty($webhooks);

        $this->tab_title                    = $GLOBALS['Language']->getText('admin_project_configuration', 'webhook_tab_title');
        $this->pane_title                   = $GLOBALS['Language']->getText('admin_project_configuration', 'webhook_pane_title');
        $this->project_creation_description = $GLOBALS['Language']->getText('admin_project_configuration', 'webhook_project_creation_description');
        $this->name                         = $GLOBALS['Language']->getText('admin_project_configuration', 'webhook_name');
        $this->url                          = $GLOBALS['Language']->getText('admin_project_configuration', 'webhook_url');
        $this->last_push_date               = $GLOBALS['Language']->getText('admin_project_configuration', 'webhook_last_push_date');
        $this->last_push_status             = $GLOBALS['Language']->getText('admin_project_configuration', 'webhook_last_push_status');
        $this->no_webhooks                  = $GLOBALS['Language']->getText('admin_project_configuration', 'no_webhooks');
        $this->button_show_logs             = $GLOBALS['Language']->getText('admin_project_configuration', 'show_logs');
        $this->close_modal                  = $GLOBALS['Language']->getText('admin_project_configuration', 'close_modal');
        $this->date_modal                   = $GLOBALS['Language']->getText('admin_project_configuration', 'date_modal');
        $this->status_modal                 = $GLOBALS['Language']->getText('admin_project_configuration', 'status_modal');
    }
}
