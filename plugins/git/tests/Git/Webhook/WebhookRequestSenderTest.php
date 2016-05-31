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

namespace Tuleap\Git\Webhook;

require_once dirname(__FILE__).'/../../bootstrap.php';

use TuleapTestCase;
use UserHelper;

class WebhookRequestSenderTest extends TuleapTestCase
{
    public function setUp()
    {
        parent::setUp();
        UserHelper::clearInstance();
        $user_helper = mock('UserHelper');
        UserHelper::setInstance($user_helper);
    }

    public function tearDown()
    {
        UserHelper::clearInstance();
        parent::tearDown();
    }

    public function itSendsOneRequestPerDefinedHook()
    {
        $factory     = mock('Tuleap\Git\Webhook\WebhookFactory');
        $http_client = mock('Http_Client');
        $logger      = mock('Logger');
        $receiver    = mock('Tuleap\Git\Webhook\WebhookResponseReceiver');
        $sender      = new WebhookRequestSender($receiver, $factory, $http_client, $logger);

        $repository = mock('GitRepository');
        $user       = mock('PFUser');
        $oldrev     = 'oldrev';
        $newrev     = 'newrev';
        $refname    = 'refs/heads/master';

        $web_hook_01 = new Webhook(1, 1, 'url_01');
        $web_hook_02 = new Webhook(2, 1, 'url_02');

        stub($factory)->getWebhooksForRepository()->returns(array(
            $web_hook_01,
            $web_hook_02,
        ));

        $http_client->expectCallCount('doRequest', 2);

        $sender->sendRequests($repository, $user, $oldrev, $newrev, $refname);
    }
}
