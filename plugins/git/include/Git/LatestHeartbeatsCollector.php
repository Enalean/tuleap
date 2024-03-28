<?php
/**
 * Copyright (c) Enalean, 2017 - Present. All Rights Reserved.
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

namespace Tuleap\Git;

use Git_GitRepositoryUrlManager;
use Git_LogDao;
use GitRepository;
use GitRepositoryFactory;
use Tuleap\Project\HeartbeatsEntry;
use Tuleap\Project\HeartbeatsEntryCollection;
use UserManager;

class LatestHeartbeatsCollector
{
    public function __construct(
        private GitRepositoryFactory $factory,
        private Git_LogDao $dao,
        private Git_GitRepositoryUrlManager $git_url_manager,
        private UserManager $user_manager,
    ) {
    }

    public function collect(HeartbeatsEntryCollection $collection)
    {
        $last_pushes = $this->dao->searchLatestPushesInProject(
            $collection->getProject()->getID(),
            $collection::NB_MAX_ENTRIES
        );
        foreach ($last_pushes as $push_row) {
            $repository = $this->factory->getRepositoryById($push_row['repository_id']);
            if (! $repository) {
                continue;
            }

            if (! $repository->userCanRead($collection->getUser())) {
                $collection->thereAreActivitiesUserCannotSee();
                continue;
            }

            $collection->add(
                new HeartbeatsEntry(
                    $push_row['push_date'],
                    $this->getHTMLMessage($repository, $push_row),
                    'fas fa-tlp-versioning-git',
                    $this->getUser((int) $push_row['user_id'])
                )
            );
        }
    }

    private function getUser(int $user_id): ?\PFUser
    {
        $pushed_by = $this->user_manager->getUserById($user_id);

        if ($pushed_by && $pushed_by->getId() && ! $pushed_by->isNone()) {
            return $pushed_by;
        }

        return null;
    }

    private function getHTMLMessage(GitRepository $repository, $push_row)
    {
        $nb_commits      = (int) $push_row['commits_number'];
        $repository_link = $repository->getHTMLLink($this->git_url_manager);

        if ($nb_commits === 0) {
            $message = sprintf(
                dgettext('tuleap-git', 'A push occured on %s'),
                $repository_link
            );
        } else {
            $message = sprintf(
                dngettext(
                    'tuleap-git',
                    '%s commit pushed on %s',
                    '%s commits pushed on %s',
                    $nb_commits
                ),
                $nb_commits,
                $repository_link
            );
        }

        return $message;
    }
}
