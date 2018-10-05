<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

namespace GitPHP\Shortlog;

use Tuleap\Git\GitPHP\Commit;
use UserManager;

class ShortlogPresenterBuilder
{
    /**
     * @var UserManager
     */
    private $user_manager;

    public function __construct(UserManager $user_manager)
    {
        $this->user_manager = $user_manager;
    }

    /**
     * @param Commit[] $commits
     *
     * @return ShortlogPresenter
     */
    public function getShortlogPresenter(array $commits)
    {
        $emails = array_reduce(
            $commits,
            function (array $emails, Commit $commit) {
                $emails['authors'][] = $commit->getAuthorEmail();
                $emails['committers'][] = $commit->getCommitterEmail();

                return $emails;
            },
            [
                'authors' => [],
                'committers' => []
            ]
        );
        $authors_by_email    = $this->user_manager->getUserCollectionByEmails(array_unique($emails['authors']));
        $committers_by_email = $this->user_manager->getUserCollectionByEmails(array_unique($emails['committers']));

        return new ShortlogPresenter(
            array_map(
                function (Commit $commit) use ($authors_by_email, $committers_by_email) {
                    return new ShortlogCommitPresenter($commit, $authors_by_email, $committers_by_email);
                },
                $commits
            )
        );
    }
}
