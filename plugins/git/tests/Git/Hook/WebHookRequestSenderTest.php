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

require_once dirname(__FILE__).'/../../bootstrap.php';

use Tuleap\Git\Git\Hook\WebHookRequestSender;
use TuleapTestCase;
use UserHelper;

class WebHookRequestSenderTest extends TuleapTestCase
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
        $dao         = mock('Tuleap\Git\Git\Hook\WebHookDao');
        $http_client = mock('Http_Client');
        $logger      = mock('Logger');
        $sender      = new WebHookRequestSender($dao, $http_client, $logger);

        $repository = mock('GitRepository');
        $user       = mock('PFUser');
        $oldrev     = 'oldrev';
        $newrev     = 'newrev';
        $refname    = 'refs/heads/master';

        $result = stub('DataAccessResult')->current()->returnsAt(0, array('url' => "url_01"));
        stub($result)->valid()->returnsAt(0, true);
        stub($result)->current()->returnsAt(1, array('url' => "url_02"));
        stub($result)->valid()->returnsAt(1, true);

        stub($dao)->searchWebhookUrlsForRepository()->returns($result);

        $http_client->expectCallCount('doRequest', 2);

        $sender->sendRequests($repository, $user, $oldrev, $newrev, $refname);
    }
}
