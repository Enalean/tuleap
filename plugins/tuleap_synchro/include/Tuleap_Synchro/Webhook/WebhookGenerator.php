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
 *
 */

namespace Tuleap\TuleapSynchro\Webhook;

use RandomNumberGenerator;
use Tuleap\TuleapSynchro\Dao\TuleapSynchroDao;

class WebhookGenerator
{
    /**
     * @var TuleapSynchroDao
     */
    private $tuleap_synchro_dao;

    /**
     * @var \RandomNumberGenerator
     */
    private $number_generator;

    /**
     *
     * @param int $size
     */
    public function __construct(TuleapSynchroDao $tuleap_synchro_dao, $size)
    {
        $this->tuleap_synchro_dao = $tuleap_synchro_dao;
        $this->number_generator   = new RandomNumberGenerator($size);
    }

    /**
     * @return Webhook
     */
    public function generateWebhook()
    {
        $existing_webhooks = $this->getWebhooks();

        do {
            $generated_webhook = new Webhook("import_" . $this->number_generator->getNumber());
        } while (in_array($generated_webhook, $existing_webhooks));

        return $generated_webhook;
    }

    /**
     * @return Webhook[]
     */
    private function getWebhooks()
    {
        $row_webhooks = $this->tuleap_synchro_dao->getAllEndpoints();

        return $this->getWebhooksFromRow($row_webhooks);
    }

    /**
     * @param $row_webhooks
     * @return Webhook[]
     */
    private function getWebhooksFromRow($row_webhooks)
    {
        $existing_webhooks = [];

        foreach ($row_webhooks as $row_webhook) {
            $existing_webhooks[] = new Webhook($row_webhook['webhook']);
        }

        return $existing_webhooks;
    }
}
