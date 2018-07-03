<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

namespace Tuleap\Tracker\Webhook\Actions;

use Tuleap\Tracker\Webhook\Webhook;

class WebhookPresenter
{
    /**
     * @var int
     */
    public $webhook_id;

    /**
     * @var string
     */
    public $webhook_url;

    /**
     * @var WebhookLogPresenter|null
     */
    public $last_log;

    /**
     * @var WebhookLogPresenter[]
     */
    public $all_log;

    public function __construct(Webhook $webhook, array $logs)
    {
        $this->webhook_id  = $webhook->getId();
        $this->webhook_url = $webhook->getUrl();
        $this->last_log    = $this->getLastLog($logs);
        $this->all_log     = $logs;
    }

    private function getLastLog(array $logs)
    {
        $last_log = null;
        if (count($logs) > 0) {
            $last_log = $logs[0];
        }

        return $last_log;
    }
}
