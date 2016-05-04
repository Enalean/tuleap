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

namespace Tuleap\BotMattermostGit\SenderServices;

require_once dirname(__FILE__).'/../../bootstrap.php';

use TuleapTestCase;

class NotificationMakerTest extends TuleapTestCase
{

    private $git_repository_url_manager;
    private $notification_maker;
    private $repository;
    private $user;

    public function setUp()
    {
        parent::setUp();
        $this->git_repository_url_manager = mock('Git_GitRepositoryUrlManager');
        $this->notification_maker         = new NotificationMaker($this->git_repository_url_manager);
        $this->repository                 = mock('GitRepository');
        $this->user                       = mock('PFUser');
    }

    public function itVerifiedThatMakeGitNotificationTextReturnsNotification()
    {
        $newrev  = 'https://tuleap.net/';
        $refname = 'master';
        stub($GLOBALS['Language'])->getText('plugin_botmattermost_git', 'push_notification_text')->returns('push_notification_text');
        stub($this->repository)->getName()->returns('MyRepository');
        stub($this->repository)->getDiffLink()->returns($newrev);
        stub($this->user)->getName()->returns('UserTest');
        $result = $this->notification_maker->makeGitNotificationText(
            $this->repository,
            $this->user,
            $newrev,
            $refname
        );
        $this->assertEqual($result, 'UserTest push_notification_text : [MyRepository]('.$newrev.') master');
    }
}
