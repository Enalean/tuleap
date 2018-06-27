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

use SimpleXMLElement;
use Tracker;

class WebhookFactory
{

    /**
     * @var WebhookDao
     */
    private $dao;

    public function __construct(WebhookDao $dao)
    {
        $this->dao = $dao;
    }

    public function duplicateWebhookFromSourceTracker(Tracker $source_tracker, Tracker $tracker)
    {
        $this->dao->duplicateWebhooks($source_tracker->getId(), $tracker->getId());
    }

    public function saveWebhooks(array $webhooks, $tracker_id)
    {
        foreach ($webhooks as $webhook) {
            $this->dao->save($tracker_id, $webhook->getUrl());
        }
    }

    /**
     * @return Webhook[]
     */
    public function getWebhooksFromXML(SimpleXMLElement $webhooks_xml)
    {
        $webhooks = [];
        foreach ($webhooks_xml->webhook as $xml_webhook) {
            $webhooks[] = $this->getInstance((string) $xml_webhook['url']);
        }

        return $webhooks;
    }

    /**
     * @return Webhook
     */
    private function getInstance($url)
    {
        return new Webhook(0, 0, $url);
    }
}
