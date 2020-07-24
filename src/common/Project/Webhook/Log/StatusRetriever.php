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

use Tuleap\Project\Webhook\Webhook;

class StatusRetriever
{
    /**
     * @var WebhookLoggerDao
     */
    private $dao;

    public function __construct(WebhookLoggerDao $dao)
    {
        $this->dao = $dao;
    }

    public function getMostRecentStatus(Webhook $webhook)
    {
        $data_access_result = $this->dao->searchLogsByWebhookId($webhook->getId());

        if ($data_access_result === false) {
            throw new StatusDataAccessException();
        }

        $status = [];
        foreach ($data_access_result as $row) {
            $status[] = $this->instantiateFromRow($row);
        }

        return $status;
    }

    /**
     * @return Status
     */
    private function instantiateFromRow(array $row)
    {
        return new Status($row['status'], $row['created_on']);
    }
}
