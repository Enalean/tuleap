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

namespace Tuleap\Project\Webhook;

class WebhookDao extends \DataAccessObject
{
    /**
     * @return \DataAccessResult|false
     */
    public function searchWebhooks()
    {
        $sql = 'SELECT * FROM project_webhook_url';

        return $this->retrieve($sql);
    }

    /**
     * @return bool
     */
    public function createWebhook($name, $url)
    {
        $name = $this->da->quoteSmart($name);
        $url  = $this->da->quoteSmart($url);

        $sql = "INSERT INTO project_webhook_url(name, url) VALUES ($name, $url)";

        return $this->update($sql);
    }

    /**
     * @return bool
     */
    public function editWebhook($id, $name, $url)
    {
        $id   = $this->da->escapeInt($id);
        $name = $this->da->quoteSmart($name);
        $url  = $this->da->quoteSmart($url);

        $sql = "UPDATE project_webhook_url SET name = $name, url = $url WHERE id = $id";

        return $this->update($sql);
    }
}
