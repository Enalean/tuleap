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

class StatusLogger implements \Tuleap\Webhook\StatusLogger
{
    /**
     * @var WebhookLoggerDao
     */
    private $dao;

    public function __construct(WebhookLoggerDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * @param $status
     * @throws WebhookLoggerDataAccessException
     */
    public function log(\Tuleap\Webhook\Webhook $webhook, $status)
    {
        $has_been_saved = $this->dao->save($webhook->getId(), $_SERVER['REQUEST_TIME'], $status);

        if (! $has_been_saved) {
            throw new WebhookLoggerDataAccessException();
        }
    }
}
