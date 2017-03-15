<?php
/**
 * Copyright (c) Enalean, 2016 - 2017. All Rights Reserved.
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
        $webhook_factory = mock('Tuleap\\Git\\Webhook\\WebhookFactory');
        $webhook_emitter = mock('Tuleap\\Webhook\\Emitter');
        $logger          = mock('Logger');

        $sender = new WebhookRequestSender($webhook_emitter, $webhook_factory, $logger);

        $repository = mock('GitRepository');
        $user       = mock('PFUser');
        $oldrev     = 'oldrev';
        $newrev     = 'newrev';
        $refname    = 'refs/heads/master';

        $web_hook_01 = new Webhook(1, 1, 'url_01');
        $web_hook_02 = new Webhook(2, 1, 'url_02');

        stub($webhook_factory)->getWebhooksForRepository()->returns(array(
            $web_hook_01,
            $web_hook_02,
        ));

        $webhook_emitter->expectCallCount('emit', 2);

        $sender->sendRequests($repository, $user, $oldrev, $newrev, $refname);
    }
}
