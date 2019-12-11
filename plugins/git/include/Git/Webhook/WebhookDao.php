<?php
/**
 * Copyright (c) Enalean, 2016-2018. All Rights Reserved.
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

namespace Tuleap\Git\Webhook;

use Tuleap\DB\DataAccessObject;

class WebhookDao extends DataAccessObject
{
    public function searchWebhooksForRepository($repository_id)
    {
        $sql = 'SELECT *
                FROM plugin_git_webhook_url
                WHERE repository_id = ?';

        return $this->getDB()->run($sql, $repository_id);
    }

    public function deleteByRepositoryIdAndWebhookId($repository_id, $webhook_id)
    {
        $sql = 'DELETE plugin_git_webhook_url, plugin_git_webhook_log
                FROM plugin_git_webhook_url
                LEFT JOIN plugin_git_webhook_log ON (plugin_git_webhook_url.id = plugin_git_webhook_log.webhook_id)
                WHERE plugin_git_webhook_url.repository_id = ?
                  AND plugin_git_webhook_url.id = ?';

        try {
            $this->getDB()->run($sql, $repository_id, $webhook_id);
        } catch (\PDOException $ex) {
            return false;
        }
        return true;
    }

    public function create($repository_id, $webhook_url)
    {
        $sql = 'INSERT INTO plugin_git_webhook_url (repository_id, url)
                VALUES (?, ?)';

        try {
            $this->getDB()->run($sql, $repository_id, $webhook_url);
        } catch (\PDOException $ex) {
            return false;
        }
        return true;
    }

    public function edit($repository_id, $webhook_id, $webhook_url)
    {
        $sql = 'UPDATE plugin_git_webhook_url
                SET url = ?
                WHERE repository_id = ?
                  AND id = ?';

        try {
            $this->getDB()->run($sql, $webhook_url, $repository_id, $webhook_id);
        } catch (\PDOException $ex) {
            return false;
        }
        return true;
    }

    public function addLog($webhook_id, $status)
    {
        $sql = 'INSERT INTO plugin_git_webhook_log(created_on, webhook_id, status)
                VALUES (?, ?, ?)';

        $this->getDB()->run($sql, $_SERVER['REQUEST_TIME'], $webhook_id, $status);
    }

    public function getLogs($webhook_id)
    {
        $sql = 'SELECT *
                FROM plugin_git_webhook_log
                WHERE webhook_id = ?
                ORDER BY created_on DESC
                LIMIT 30';

        return $this->getDB()->run($sql, $webhook_id);
    }
}
