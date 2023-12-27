<?php
/**
 * Copyright (c) Enalean, 2017-Present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\Project\Webhook\Log;

final class StatusRetrieverTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testItRetrievesStatus(): void
    {
        $row_1              = ['webhook_id' => 1, 'created_on' => 1489595500, 'status' => 'Operation timed out after 5000 milliseconds with 0 bytes received'];
        $row_2              = ['webhook_id' => 1, 'created_on' => 1489595525, 'status' => '200 OK'];
        $data_access_result = \TestHelper::arrayToDar($row_1, $row_2);
        $dao                = $this->createMock(\Tuleap\Project\Webhook\Log\WebhookLoggerDao::class);
        $dao->method('searchLogsByWebhookId')->willReturn($data_access_result);
        $webhook = $this->createMock(\Tuleap\Project\Webhook\Webhook::class);
        $webhook->method('getId');

        $retriever = new StatusRetriever($dao);

        $status = $retriever->getMostRecentStatus($webhook);

        self::assertCount(2, $status);
    }

    public function testItFailsWhenStatusCanNotBeRetrieved(): void
    {
        $dao = $this->createMock(\Tuleap\Project\Webhook\Log\WebhookLoggerDao::class);
        $dao->method('searchLogsByWebhookId')->willReturn(false);
        $webhook = $this->createMock(\Tuleap\Project\Webhook\Webhook::class);
        $webhook->method('getId');

        $retriever = new StatusRetriever($dao);

        self::expectException(\Tuleap\Project\Webhook\Log\StatusDataAccessException::class);
        $retriever->getMostRecentStatus($webhook);
    }
}
