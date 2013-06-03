<?php
/**
 * Copyright Enalean (c) 2013. All rights reserved.
 *
 * Tuleap and Enalean names and logos are registrated trademarks owned by
 * Enalean SAS. All other trademarks or names are properties of their respective
 * owners.
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

class Git_Hook_PostReceive {
    const FAKE_EMPTY_COMMIT = '0000000000000000000000000000000000000000';

    /** @var Git_Exec */
    private $exec_repo;

    /** @var GitRepositoryFactory  */
    private $repository_factory;

    /** @var UserManager */
    private $user_manager;

    /** @var Git_Hook_ExtractCrossReferences */
    private $extract_cross_ref;

    /** @var Git_Ci_Launcher */
    private $ci_launcher;

    public function __construct(
            Git_Exec $exec_repo,
            GitRepositoryFactory $repository_factory,
            UserManager $user_manager,
            Git_Hook_ExtractCrossReferences $extract_cross_ref,
            Git_Ci_Launcher $ci_launcher) {
        $this->exec_repo          = $exec_repo;
        $this->repository_factory = $repository_factory;
        $this->user_manager       = $user_manager;
        $this->extract_cross_ref  = $extract_cross_ref;
        $this->ci_launcher        = $ci_launcher;
    }

    public function execute($repository_path, $user_name, $oldrev, $newrev, $refname) {
        $repository = $this->repository_factory->getFromFullPath($repository_path);
        if ($repository !== null) {
            $this->ci_launcher->executeForRepository($repository);

            $user = $this->user_manager->getUserByUserName($user_name);
            if ($user === null) {
                $user = new PFUser(array('user_id' => 0));
            }
            foreach ($this->getRevisionsList($oldrev, $newrev, $refname) as $commit) {
                $this->extract_cross_ref->execute($repository, $user, $commit, $refname);
            }
        }
    }

    private function getRevisionsList($oldrev, $newrev, $refname) {
        if ($oldrev == self::FAKE_EMPTY_COMMIT) {
            return $this->exec_repo->revListSinceStart($refname, $newrev);
        } elseif ($newrev == self::FAKE_EMPTY_COMMIT) {
            return array();
        } else {
            return $this->exec_repo->revList($oldrev, $newrev);
        }
    }
}

?>
