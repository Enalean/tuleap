<?php
/**
 * Copyright (c) Enalean, 2016. All Rights Reserved.
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

namespace Tuleap\HudsonGit\Hook;

use Logger;
use GitRepository;
use Jenkins_Client;
use Exception;

class HookTriggerController {

    /**
     * @var HookDao
     */
    private $dao;

    /**
     * @var Jenkins_Client
     */
    private $jenkins_client;

    /**
     * @var Logger
     */
    private $logger;

    public function __construct(HookDao $dao, Jenkins_Client $jenkins_client, Logger $logger) {
        $this->dao            = $dao;
        $this->jenkins_client = $jenkins_client;
        $this->logger         = $logger;
    }

    public function trigger(GitRepository $repository) {
        $dar = $this->dao->getById($repository->getId());
        foreach ($dar as $row) {
            try {
                $transports = $repository->getAccessURL();
                foreach ($transports as $protocol => $url) {
                    $response = $this->jenkins_client->pushGitNotifications($row['jenkins_server_url'], $url);
                    $this->logger->debug('repository #'.$repository->getId().' : '.$response);
                }
            } catch (Exception $exception) {
                $this->logger->error('repository #'.$repository->getId().' : '.$exception->getMessage());
            }
        }
    }
}
