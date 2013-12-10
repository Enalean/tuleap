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

/**
 * Central access point for things that needs to happen when post-receive is
 * executed
 */
class Git_Hook_PostReceive {
    /** @var Git_Hook_LogAnalyzer */
    private $log_analyzer;

    /** @var GitRepositoryFactory  */
    private $repository_factory;

    /** @var UserManager */
    private $user_manager;

    /** @var Git_Ci_Launcher */
    private $ci_launcher;

    /** @var Git_Hook_ParseLog */
    private $parse_log;
    
    public function __construct(
            Git_Hook_LogAnalyzer $log_analyzer,
            GitRepositoryFactory $repository_factory,
            UserManager $user_manager,
            Git_Ci_Launcher $ci_launcher,
            Git_Hook_ParseLog $parse_log) {
        $this->log_analyzer       = $log_analyzer;
        $this->repository_factory = $repository_factory;
        $this->user_manager       = $user_manager;
        $this->ci_launcher        = $ci_launcher;
        $this->parse_log          = $parse_log;
    }

    public function execute($repository_path, $user_name, $oldrev, $newrev, $refname) {
        $repository = $this->repository_factory->getFromFullPath($repository_path);
        if ($repository !== null) {
            $user = $this->user_manager->getUserByUserName($user_name);
            if ($user === null) {
                $user = new PFUser(array('user_id' => 0));
            }
            $this->executeForRepositoryAndUser($repository, $user, $oldrev, $newrev, $refname);
        }
    }

    private function executeForRepositoryAndUser(GitRepository $repository, PFUser $user, $oldrev, $newrev, $refname) {
        $this->ci_launcher->executeForRepository($repository);

        $push_details = $this->log_analyzer->getPushDetails($repository, $user, $oldrev, $newrev, $refname);
        $this->parse_log->execute($push_details);
    }
}
