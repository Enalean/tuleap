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

namespace Tuleap\Project\Webhook\Log;

class StatusRetrieverTest extends \TuleapTestCase
{
    public function itRetrievesStatus()
    {
        $row_1              = array('webhook_id' => 1, 'created_on' => 1489595500, 'status' => 'Operation timed out after 5000 milliseconds with 0 bytes received');
        $row_2              = array('webhook_id' => 1, 'created_on' => 1489595525, 'status' => '200 OK');
        $data_access_result = \TestHelper::arrayToDar($row_1, $row_2);
        $dao                = mock('Tuleap\\Project\\Webhook\\Log\\WebhookLoggerDao');
        stub($dao)->searchLogsByWebhookId()->returns($data_access_result);
        $webhook = mock('Tuleap\\Project\\Webhook\\Webhook');

        $retriever = new StatusRetriever($dao);

        $status = $retriever->getMostRecentStatus($webhook);

        $this->assertCount($status, 2);
    }

    public function itFailsWhenStatusCanNotBeRetrieved()
    {
        $dao = mock('Tuleap\\Project\\Webhook\\Log\\WebhookLoggerDao');
        stub($dao)->searchLogsByWebhookId()->returns(false);
        $webhook = mock('Tuleap\\Project\\Webhook\\Webhook');

        $retriever = new StatusRetriever($dao);

        $this->expectException('Tuleap\\Project\\Webhook\\Log\\StatusDataAccessException');
        $retriever->getMostRecentStatus($webhook);
    }
}
