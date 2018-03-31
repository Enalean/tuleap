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

namespace Tuleap\Enalean\LicenseManager;

use Tuleap\Enalean\LicenseManager\Webhook\UserCounterPayload;
use Tuleap\Instrument\Collect;
use Tuleap\Webhook\Emitter;

class StatusActivityEmitter
{
    /**
     * @var Emitter
     */
    private $webhook_emitter;

    public function __construct(Emitter $webhook_emitter)
    {
        $this->webhook_emitter = $webhook_emitter;
    }

    public function emit(UserCounterPayload $payload, $webhook_url)
    {
        $this->emitToInstrumentationTools($payload);
        $this->emitWebhook($payload, $webhook_url);
    }

    private function emitToInstrumentationTools(UserCounterPayload $payload)
    {
        foreach ($payload->getPayload()['users'] as $status => $number) {
            Collect::gauge("license.users.$status", $number);
        }
        Collect::gauge('license.max_users', $payload->getPayload()['max_users']);
    }

    private function emitWebhook(UserCounterPayload $payload, $webhook_url)
    {
        if (! $webhook_url) {
            return;
        }

        $webhook = new UserCounterWebhook($webhook_url);

        $this->webhook_emitter->emit($webhook, $payload);
    }
}
