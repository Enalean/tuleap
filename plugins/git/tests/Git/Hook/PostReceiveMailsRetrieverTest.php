<?php
/**
 * Copyright Enalean (c) 2017. All rights reserved.
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

namespace Tuleap\Git\Hook;

require_once dirname(__FILE__).'/../../bootstrap.php';

use TuleapTestCase;

class PostReceiveMailsRetrieverTest extends TuleapTestCase
{
    /** @var \GitRepository */
    private $repository;

    /** @var PostReceiveMailsRetriever */
    private $retriever;

    public function setUp()
    {
        parent::setUp();

        $this->repository   = aGitRepository()
            ->withId(101)
            ->withNotifiedEmails(array('jdoe@example.com', 'smith@example.com'))
            ->build();

        $notified_users_dao = mock('Tuleap\Git\Notifications\UsersToNotifyDao');
        stub($notified_users_dao)
            ->searchUsersEmailByRepositoryId(101)
            ->returnsDar(
                array('email' => 'andrew@example.com'),
                array('email' => 'smith@example.com')
            );

        $this->retriever = new PostReceiveMailsRetriever($notified_users_dao);
    }

    public function itReturnsMailsForRepository()
    {
        $emails = $this->retriever->getNotifiedMails($this->repository);

        $this->assertTrue(in_array('jdoe@example.com', $emails));
        $this->assertTrue(in_array('smith@example.com', $emails));
    }

    public function itReturnsMailsOfUsersForRepository()
    {
        $emails = $this->retriever->getNotifiedMails($this->repository);

        $this->assertTrue(in_array('andrew@example.com', $emails));
    }

    public function itRemovesDuplicates()
    {
        $emails = $this->retriever->getNotifiedMails($this->repository);

        $this->assertEqual($emails, array_unique($emails));
    }
}
