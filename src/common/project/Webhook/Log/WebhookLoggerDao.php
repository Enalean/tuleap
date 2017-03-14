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

class WebhookLoggerDao extends \DataAccessObject
{
    /**
     * @return bool
     */
    public function save($webhook_id, $created_on, $status)
    {
        $webhook_id = $this->da->escapeInt($webhook_id);
        $created_on = $this->da->escapeInt($created_on);
        $status     = $this->da->quoteSmart($status);

        $sql = "INSERT INTO project_webhook_log(webhook_id, created_on, status)
                VALUES ($webhook_id, $created_on, $status)";

        return $this->update($sql);
    }
}
