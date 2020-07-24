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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

namespace Tuleap\Git\Webhook;

use GitRepository;

class WebhookFactory
{
    /**
     * @var WebhookDao
     */
    private $dao;

    public function __construct(WebhookDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * @return array
     */
    public function getWebhooksForRepository(GitRepository $repository)
    {
        $repository_id = $repository->getId();

        $web_hooks = [];
        foreach ($this->dao->searchWebhooksForRepository($repository_id) as $web_hook_row) {
            $web_hooks[] = new Webhook($web_hook_row['id'], $web_hook_row['repository_id'], $web_hook_row['url']);
        }
        return $web_hooks;
    }
}
