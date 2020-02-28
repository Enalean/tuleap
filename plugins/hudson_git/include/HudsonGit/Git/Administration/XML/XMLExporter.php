<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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

namespace Tuleap\HudsonGit\Git\Administration\XML;

use Project;
use Psr\Log\LoggerInterface;
use SimpleXMLElement;
use Tuleap\HudsonGit\Git\Administration\JenkinsServerFactory;

class XMLExporter
{
    /**
     * @var JenkinsServerFactory
     */
    private $jenkins_server_factory;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(JenkinsServerFactory $jenkins_server_factory, LoggerInterface $logger)
    {
        $this->jenkins_server_factory = $jenkins_server_factory;
        $this->logger                 = $logger;
    }

    public function export(Project $project, SimpleXMLElement $xml_git): void
    {
        $project_jenkins_server = $this->jenkins_server_factory->getJenkinsServerOfProject($project);
        if (count($project_jenkins_server) === 0) {
            return;
        }

        $this->logger->info("Exporting all project jenkins servers.");
        $jenkins_servers_admin_node = $xml_git->addChild("jenkins-servers-admin");
        foreach ($project_jenkins_server as $jenkins_server) {
            $this->logger->info("Exporting project jenkins server: " . $jenkins_server->getServerURL());
            $jenkins_server_node = $jenkins_servers_admin_node->addChild("jenkins-server");
            $jenkins_server_node->addAttribute("url", $jenkins_server->getServerURL());
        }
    }
}
