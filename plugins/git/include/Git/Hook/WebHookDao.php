<?php
/**
 * Copyright (c) Enalean, 2016. All Rights Reserved.
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

namespace Tuleap\Git\Git\Hook;

use DataAccessObject;

class WebHookDao extends DataAccessObject
{
    public function searchWebHooksForRepository($repository_id)
    {
        $repository_id = $this->da->escapeInt($repository_id);

        $sql = "SELECT *
                FROM plugin_git_webhook_url
                WHERE repository_id = $repository_id";

        return $this->retrieve($sql);
    }

    public function deleteByRepositoryIdAndWebhookId($repository_id, $webhook_id)
    {
        $repository_id = $this->da->escapeInt($repository_id);
        $webhook_id    = $this->da->escapeInt($webhook_id);

        $sql = "DELETE FROM plugin_git_webhook_url
                WHERE repository_id = $repository_id
                  AND id = $webhook_id";

        return $this->update($sql);
    }

    public function create($repository_id, $webhook_url)
    {
        $repository_id = $this->da->escapeInt($repository_id);
        $webhook_url   = $this->da->quoteSmart($webhook_url);

        $sql = "INSERT INTO plugin_git_webhook_url (repository_id, url)
                VALUES ($repository_id, $webhook_url)";

        return $this->updateAndGetLastId($sql);
    }
}
