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
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

namespace Tuleap\Tracker\Webhook;

use Tracker;
use Tuleap\Tracker\Webhook\Webhook;

class WebhookRetriever
{
    /**
     * @var WebhookDao
     */
    private $dao;

    public function __construct(WebhookDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * @return Webhook[]
     */
    public function getWebhooksForTracker(Tracker $tracker)
    {
        $webhooks = [];
        foreach ($this->dao->searchWebhooksForTracker($tracker->getId()) as $web_hook_row) {
            $webhooks[] = $this->instantiateFromRow($web_hook_row);
        }

        return $webhooks;
    }

    public function getLogsForWebhook(Webhook $webhook)
    {
        return $this->dao->searchLogsForWebhook($webhook->getId());
    }

    /**
     * @return Webhook
     */
    private function instantiateFromRow(array $row)
    {
        return new Webhook($row['id'], $row['tracker_id'], $row['url']);
    }
}
