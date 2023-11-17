<?php
/**
 * Copyright (c) Enalean 2022 - Present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\PullRequest\Comment;

use Tuleap\DB\DBFactory;

final class ThreadCommentDaoTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private const PULL_REQUEST_1_ID = 1;
    private const FIRST_COMMENT_ID  = 1;
    private const SECOND_COMMENT_ID = 2;

    public static function setUpBeforeClass(): void
    {
        $db = DBFactory::getMainTuleapDBConnection()->getDB();
        $db->insert(
            'plugin_pullrequest_git_reference',
            [
                'pr_id' => self::PULL_REQUEST_1_ID,
                'reference_id' => '1',
                'repository_dest_id' => '1',
                'status' => '0',
            ]
        );

        $db->insert(
            'plugin_pullrequest_comments',
            [
                'id' => 1,
                'pull_request_id' => self::PULL_REQUEST_1_ID,
                'user_id' => '101',
                'post_date' => time(),
                'content' => 'My global comment',
                'parent_id' => '0',
            ]
        );

        $db->insert(
            'plugin_pullrequest_inline_comments',
            [
                'id' => 2,
                'pull_request_id' => self::PULL_REQUEST_1_ID,
                'user_id' => '101',
                'post_date' => time(),
                'content' => 'My inline comment',
                'parent_id' => '0',
            ]
        );
    }

    public static function tearDownAfterClass(): void
    {
        $db = DBFactory::getMainTuleapDBConnection()->getDB();

        $db->safeQuery("DELETE FROM plugin_pullrequest_git_reference WHERE pr_id=1");

        $db->safeQuery("DELETE FROM plugin_pullrequest_comments WHERE pull_request_id=1");
        $db->safeQuery("DELETE FROM plugin_pullrequest_inline_comments WHERE pull_request_id=1");
    }

    public function testItRetrievesThreads(): void
    {
        $dao               = new ThreadCommentDao();
        $number_of_threads = $dao->countAllThreadsOfPullRequest(self::PULL_REQUEST_1_ID);
        self::assertSame(0, $number_of_threads);

        $db = DBFactory::getMainTuleapDBConnection()->getDB();

        $db->insert(
            'plugin_pullrequest_comments',
            [
                'id' => 3,
                'pull_request_id' => self::PULL_REQUEST_1_ID,
                'user_id' => '101',
                'post_date' => time(),
                'content' => 'My global comment',
                'parent_id' => self::FIRST_COMMENT_ID,
            ]
        );

        $db->insert(
            'plugin_pullrequest_inline_comments',
            [
                'id' => 4,
                'pull_request_id' => self::PULL_REQUEST_1_ID,
                'user_id' => '101',
                'post_date' => time(),
                'content' => 'My inline comment',
                'parent_id' => self::SECOND_COMMENT_ID,
            ]
        );
        $db->update('plugin_pullrequest_comments', ['color' => "graffity-yellow"], ["id" => self::FIRST_COMMENT_ID]);
        $db->update('plugin_pullrequest_inline_comments', ['color' => "flamingo-pink"], ["id" => self::SECOND_COMMENT_ID]);

        $dao               = new ThreadCommentDao();
        $number_of_threads = $dao->countAllThreadsOfPullRequest(self::PULL_REQUEST_1_ID);
        self::assertSame(2, $number_of_threads);
    }
}
