<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

namespace Tuleap\PullRequest\Reviewer\Change;

use ParagonIE\EasyDB\EasyStatement;
use PHPUnit\Framework\TestCase;
use Tuleap\DB\DBFactory;
use Tuleap\PullRequest\Reviewer\ReviewerDAO;

final class ReviewerChangeDAOTest extends TestCase
{
    /** @var int */
    private static $pr_reviewer_1_id;
    /** @var int */
    private static $pr_reviewer_2_id;
    /** @var int */
    private static $pr_reviewer_3_id;

    public static function setUpBeforeClass(): void
    {
        self::$pr_reviewer_1_id = self::createUser('pr_reviewer_1');
        self::$pr_reviewer_2_id = self::createUser('pr_reviewer_2');
        self::$pr_reviewer_3_id = self::createUser('pr_reviewer_3');
    }

    private static function createUser(string $username): int
    {
        return (int) DBFactory::getMainTuleapDBConnection()->getDB()->insertReturnId(
            'user',
            [
                'user_name' => 'pr_reviewer_1',
                'email' => 'pr_reviewer_1@example.com',
            ]
        );
    }

    protected function tearDown(): void
    {
        $db = DBFactory::getMainTuleapDBConnection()->getDB();
        $db->run('DELETE FROM plugin_pullrequest_reviewer_change');
        $db->run('DELETE FROM plugin_pullrequest_reviewer_change_user');
    }

    public static function tearDownAfterClass(): void
    {
        $user_to_delete_condition = EasyStatement::open()->in(
            'user_id IN (?*)',
            [self::$pr_reviewer_1_id, self::$pr_reviewer_2_id, self::$pr_reviewer_3_id]
        );
        DBFactory::getMainTuleapDBConnection()->getDB()->safeQuery(
            "DELETE FROM user WHERE $user_to_delete_condition",
            $user_to_delete_condition->values()
        );
    }

    public function testSearchesReviewerChangeFromID(): void
    {
        $reviewer_dao = new ReviewerDAO();
        $change_id    = $reviewer_dao->setReviewers(40, self::$pr_reviewer_1_id, 10, ...[self::$pr_reviewer_2_id]);

        $this->assertNotNull($change_id);

        $reviewer_change_dao    = new ReviewerChangeDAO();
        $raw_change_information = $reviewer_change_dao->searchByChangeID($change_id);

        $expected_result = [
            [
                'pull_request_id'  => 40,
                'change_date'      => 10,
                'change_user_id'   => self::$pr_reviewer_1_id,
                'reviewer_user_id' => self::$pr_reviewer_2_id,
                'is_removal'       => 0,
            ]
        ];

        $this->assertEquals($expected_result, $raw_change_information);
    }

    public function testSearchesReviewerChangesOfAPullRequest(): void
    {
        $reviewer_dao = new ReviewerDAO();
        $reviewer_dao->setReviewers(41, self::$pr_reviewer_1_id, 10, ...[self::$pr_reviewer_2_id, self::$pr_reviewer_3_id]);
        $reviewer_dao->setReviewers(41, self::$pr_reviewer_2_id, 20, ...[self::$pr_reviewer_3_id]);

        $reviewer_change_dao = new ReviewerChangeDAO();
        $changes = $reviewer_change_dao->searchByPullRequestID(41);

        $expected_result = [
            [
                [
                    'change_date'      => 10,
                    'change_user_id'   => self::$pr_reviewer_1_id,
                    'reviewer_user_id' => self::$pr_reviewer_2_id,
                    'is_removal'       => 0,
                ],
                [
                    'change_date'      => 10,
                    'change_user_id'   => self::$pr_reviewer_1_id,
                    'reviewer_user_id' => self::$pr_reviewer_3_id,
                    'is_removal'       => 0,
                ],
            ],
            [
                [
                    'change_date'      => 20,
                    'change_user_id'   => self::$pr_reviewer_2_id,
                    'reviewer_user_id' => self::$pr_reviewer_2_id,
                    'is_removal'       => 1,
                ],
            ],
        ];

        $this->assertEquals($expected_result, array_values($changes));
    }
}
