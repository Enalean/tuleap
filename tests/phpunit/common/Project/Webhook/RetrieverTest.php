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

namespace Tuleap\Project\Webhook;

final class RetrieverTest extends \PHPUnit\Framework\TestCase
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    public function testItRetrievesWebhooks(): void
    {
        $row_1              = array('id' => 1, 'name' => 'W1', 'url' => 'https://example.com');
        $row_2              = array('id' => 2, 'name' => 'W2', 'url' => 'https://webhook2.example.com');
        $data_access_result = \TestHelper::arrayToDar($row_1, $row_2);
        $dao                = \Mockery::mock(\Tuleap\Project\Webhook\WebhookDao::class);
        $dao->shouldReceive('searchWebhooks')->andReturns($data_access_result);

        $retriever = new Retriever($dao);

        $webhooks = $retriever->getWebhooks();

        $this->assertCount(2, $webhooks);
    }

    public function testItFailsWhenWebhooksCanNotBeRetrieved(): void
    {
        $dao = \Mockery::mock(\Tuleap\Project\Webhook\WebhookDao::class);
        $dao->shouldReceive('searchWebhooks')->andReturns(false);

        $retriever = new Retriever($dao);

        $this->expectException(\Tuleap\Project\Webhook\WebhookDataAccessException::class);
        $retriever->getWebhooks();
    }
}
