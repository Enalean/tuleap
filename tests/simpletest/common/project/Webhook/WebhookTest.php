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

class WebhookTest extends \TuleapTestCase
{
    public function itSendsInformation()
    {
        $http_client = mock('Http_Client');
        $webhook     = new Webhook(1, 'https://example.com', $http_client);

        $project = mock('Project');
        stub($project)->getStartDate()->returns(1489414628);
        $admin = mock('PFUser');
        stub($project)->getAdmins()->returns(array($admin));

        $http_client->expectOnce('doRequest');
        $webhook->send($project, 1489414628);
    }
}
