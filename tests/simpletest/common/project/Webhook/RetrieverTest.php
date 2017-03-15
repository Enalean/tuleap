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

namespace Tuleap\project\Webhook;

class RetrieverTest extends \TuleapTestCase
{
    public function itRetrievesWebhooks()
    {
        $row_1              = array('id' => 1, 'url' => 'https://example.com');
        $row_2              = array('id' => 2, 'url' => 'https://webhook2.example.com');
        $data_access_result = \TestHelper::arrayToDar($row_1, $row_2);
        $dao                = mock('Tuleap\\Project\\Webhook\\WebhookDao');
        stub($dao)->searchWebhooks()->returns($data_access_result);
        $status_logger = mock('Tuleap\\Project\\Webhook\\Log\\StatusLogger');

        $retriever = new Retriever($dao, $status_logger);

        $webhooks = $retriever->getWebhooks();

        $this->assertCount($webhooks, 2);
    }

    public function itFailsWhenWebhooksCanNotBeRetrieved()
    {
        $dao = mock('Tuleap\\Project\\Webhook\\WebhookDao');
        stub($dao)->searchWebhooks()->returns(false);
        $status_logger = mock('Tuleap\\Project\\Webhook\\Log\\StatusLogger');

        $retriever = new Retriever($dao, $status_logger);

        $this->expectException('Tuleap\\Project\\Webhook\\WebhookDataAccessException');
        $retriever->getWebhooks();
    }
}
