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

use Tuleap\Project\Webhook\Log\Status;
use Tuleap\Project\Webhook\Webhook;

class WebhookPresenter
{
    /**
     * @var Webhook
     */
    private $webhook;
    /**
     * @var Status[]
     */
    private $status;

    public $show_logs_title;
    public $update_title;
    public $delete_title;
    public $delete_warning;

    public function __construct(Webhook $webhook, array $status)
    {
        $this->webhook = $webhook;
        $this->status  = $status;

        $this->show_logs_title = $GLOBALS['Language']->getText('admin_project_configuration', 'show_logs_title_modal', [$webhook->getName()]);
        $this->update_title    = $GLOBALS['Language']->getText('admin_project_configuration', 'update_title_modal', [$webhook->getName()]);
        $this->delete_title    = $GLOBALS['Language']->getText('admin_project_configuration', 'delete_title_modal', [$webhook->getName()]);
        $this->delete_warning  = $GLOBALS['Language']->getText('admin_project_configuration', 'delete_webhook_warning_modal', [$webhook->getName()]);
    }

    public function name()
    {
        return $this->webhook->getName();
    }

    public function id()
    {
        return $this->webhook->getId();
    }

    public function url()
    {
        return $this->webhook->getUrl();
    }

    public function lastStatus()
    {
        if (empty($this->status)) {
            return null;
        }

        return $this->status[0];
    }

    public function status()
    {
        return $this->status;
    }
}
