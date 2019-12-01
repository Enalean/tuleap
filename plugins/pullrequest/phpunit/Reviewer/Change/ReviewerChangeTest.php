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

namespace ReviewerChangeTest;

use PHPUnit\Framework\TestCase;
use Tuleap\PullRequest\Reviewer\Change\ReviewerChange;

final class ReviewerChangeTest extends TestCase
{
    public function testReviewerChangeCanBeConstructed(): void
    {
        $change_date      = new \DateTimeImmutable('@10');
        $change_user      = $this->buildUser();
        $added_reviewer   = [$this->buildUser(), $this->buildUser()];
        $removed_reviewer = [$this->buildUser()];

        $change = new ReviewerChange($change_date, $change_user, $added_reviewer, $removed_reviewer);

        $this->assertEquals($change_date, $change->changedAt());
        $this->assertEquals($change_date->getTimestamp(), $change->getPostDate());
        $this->assertSame($change_user, $change->changedBy());
        $this->assertSame($added_reviewer, $change->getAddedReviewers());
        $this->assertSame($removed_reviewer, $change->getRemovedReviewers());
    }

    private function buildUser(): \PFUser
    {
        return new \PFUser(['language_id' => 'en_US']);
    }
}
