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

namespace Tuleap\PullRequest\Reviewer;

use ParagonIE\EasyDB\EasyStatement;
use PHPUnit\Framework\TestCase;
use Tuleap\DB\DBFactory;

final class ReviewerDAOTest extends TestCase
{
    /**
     * @var int[]
     */
    private static $reviewers_id = [];

    public static function setUpBeforeClass(): void
    {
        $db = DBFactory::getMainTuleapDBConnection()->getDB();
        self::$reviewers_id[] = (int) $db->insertReturnId(
            'user',
            [
                'user_name' => 'pr_reviewer_1',
                'email' => 'pr_reviewer_1@example.com',
            ]
        );
        self::$reviewers_id[] = (int) $db->insertReturnId(
            'user',
            [
                'user_name' => 'pr_reviewer_2',
                'email' => 'pr_reviewer_2@example.com',
            ]
        );
    }

    public static function tearDownAfterClass(): void
    {
        $db                       = DBFactory::getMainTuleapDBConnection()->getDB();

        $user_to_delete_condition = EasyStatement::open()->in('user_id IN (?*)', self::$reviewers_id);
        $db->safeQuery("DELETE FROM user WHERE $user_to_delete_condition", $user_to_delete_condition->values());
    }

    public function testCanSearchReviewersOnANotExistingPullRequest(): void
    {
        $dao = new ReviewerDAO();

        $user_rows = $dao->searchReviewers(9999999);

        $this->assertEmpty($user_rows);
    }

    public function testCanSetReviewersOnAPullRequest(): void
    {
        $dao = new ReviewerDAO();

        $user_doing_change_id = 1;

        $dao->setReviewers(
            1,
            $user_doing_change_id,
            1,
            ...self::$reviewers_id
        );

        $new_reviewer_rows = $dao->searchReviewers(1);

        $reviewer_ids = [];
        foreach ($new_reviewer_rows as $new_reviewer_row) {
            $reviewer_ids[] = $new_reviewer_row['user_id'];
        }
        $this->assertEqualsCanonicalizing(self::$reviewers_id, $reviewer_ids);

        $dao->setReviewers(1, $user_doing_change_id, 3);
        $this->assertEmpty($dao->searchReviewers(1));
    }
}
