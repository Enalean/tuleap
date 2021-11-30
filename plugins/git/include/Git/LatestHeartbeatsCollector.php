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
use UserHelper;
use UserManager;

class LatestHeartbeatsCollector
{
    public function __construct(
        private GitRepositoryFactory $factory,
        private Git_LogDao $dao,
        private Git_GitRepositoryUrlManager $git_url_manager,
        private UserManager $user_manager,
        private UserHelper $user_helper,
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
                    "fas fa-tlp-versioning-git"
                )
            );
        }
    }

    private function getHTMLMessage(GitRepository $repository, $push_row)
    {
        $nb_commits      = (int) $push_row['commits_number'];
        $pushed_by       = $this->user_manager->getUserById($push_row['user_id']);
        $repository_link = $repository->getHTMLLink($this->git_url_manager);

        if ($pushed_by && $pushed_by->getId() && ! $pushed_by->isNone()) {
            $user_link = $this->user_helper->getLinkOnUser($pushed_by);

            if ($nb_commits === 0) {
                $message = sprintf(
                    dgettext('tuleap-git', '%s pushed on %s'),
                    $user_link,
                    $repository_link
                );
            } else {
                $message = sprintf(
                    dngettext(
                        'tuleap-git',
                        '%s pushed %s commit on %s',
                        '%s pushed %s commits on %s',
                        $nb_commits
                    ),
                    $user_link,
                    $nb_commits,
                    $repository_link
                );
            }
        } else {
            if ($nb_commits === 0) {
                $message = sprintf(
                    dgettext('tuleap-git', 'A push occured on %s'),
                    $repository_link
                );
            } else {
                $message = sprintf(
                    dngettext(
                        'tuleap-git',
                        '%s commit was pushed on %s',
                        '%s commits were pushed on %s',
                        $nb_commits
                    ),
                    $nb_commits,
                    $repository_link
                );
            }
        }

        return $message;
    }
}
