<?php
/*
 * Copyright Enalean (c) 2011, 2012, 2013. All rights reserved.
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
 * Manage launch of continuous integration jobs on jenkins for git repositories
 * on push
 */
class Git_Ci_Launcher
{

    /** @var Jenkins_Client */
    private $jenkins_client;

    /** @var Git_Ci_Dao */
    private $dao;

    /** @var \Psr\Log\LoggerInterface */
    private $logger;

    public function __construct(Jenkins_Client $jenkins_client, Git_Ci_Dao $dao, \Psr\Log\LoggerInterface $logger)
    {
        $this->jenkins_client     = $jenkins_client;
        $this->dao                = $dao;
        $this->logger             = $logger;
    }

    /**
     * Trigger jobs corresponding to the Git repository
     *
     * @param GitRepository $repository_location Name of the git repository
     */
    public function executeForRepository(GitRepository $repository)
    {
        if ($repository->getProject()->usesService('hudson')) {
            $this->launchForRepository($repository);
        }
    }

    private function launchForRepository(GitRepository $repository)
    {
        $res = $this->dao->retrieveTriggersPathByRepository($repository->getId());
        if ($res && !$res->isError() && $res->rowCount() > 0) {
            foreach ($res as $row) {
                try {
                    $this->jenkins_client->setToken($row['token'])->launchJobBuild($row['job_url']);
                } catch (Exception $exception) {
                    $this->logger->error(self::class . '[' . $repository->getId() . '] ' . $exception->getMessage());
                }
            }
        }
    }
}
