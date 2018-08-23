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

namespace Tuleap\TuleapSynchro\Endpoint;

use Tuleap\TuleapSynchro\Dao\TuleapSynchroDao;
use Tuleap\TuleapSynchro\Webhook\WebhookGenerator;

class EndpointUpdater
{
    /**
     * @var TuleapSynchroDao
     */
    private $tuleap_synchro_dao;

    /**
     * @var WebhookGenerator
     */
    private $generate_webhook;

    public function __construct(TuleapSynchroDao $tuleap_synchro_dao, WebhookGenerator $webhook_generator)
    {
        $this->tuleap_synchro_dao = $tuleap_synchro_dao;
        $this->generate_webhook   = $webhook_generator;
    }

    public function addEndpoint(array $data)
    {
        $webhook = $this->generate_webhook->generateWebhook();

        $this->tuleap_synchro_dao->addEndpoint(
            $data['tuleap_synchro-username_source'],
            $data['tuleap_synchro-password_source'],
            $data['tuleap_synchro-project_source'],
            $data['tuleap_synchro-tracker_source'],
            $data['tuleap_synchro-username_target'],
            $data['tuleap_synchro-project_target'],
            $data['tuleap_synchro-base_uri'],
            $webhook->getWebhook()
        );
    }

    public function deleteEndpoint(array $data)
    {
        $this->tuleap_synchro_dao->deleteEndpoint($data['tuleap_synchro-delete_webhook']);
    }
}
