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
use Tuleap\HudsonGit\Git\Administration\JenkinsServerAdder;
use Tuleap\HudsonGit\Git\Administration\JenkinsServerAlreadyDefinedException;
use Tuleap\HudsonGit\Git\Administration\JenkinsServerURLNotValidException;

class XMLImporter
{
    /**
     * @var JenkinsServerAdder
     */
    private $jenkins_server_adder;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(JenkinsServerAdder $jenkins_server_adder, LoggerInterface $logger)
    {
        $this->jenkins_server_adder = $jenkins_server_adder;
        $this->logger               = $logger;
    }

    public function import(Project $project, SimpleXMLElement $xml_git): void
    {
        if (! isset($xml_git->{"jenkins-servers-admin"})) {
            return;
        }

        $this->logger->info("Importing project jenkins servers.");
        foreach ($xml_git->{"jenkins-servers-admin"}->{"jenkins-server"} as $xml_jenkins_server) {
            $jenkins_server_url = (string) $xml_jenkins_server['url'];
            try {
                $this->logger->info("Importing project jenkins server: " . $jenkins_server_url);
                $this->jenkins_server_adder->addServerInProject(
                    $project,
                    $jenkins_server_url
                );
            } catch (JenkinsServerAlreadyDefinedException $exception) {
                $this->logger->error("Jenkins server " . $jenkins_server_url . " already exists in project.");
            } catch (JenkinsServerURLNotValidException $exception) {
                $this->logger->error("Jenkins server URL " . $jenkins_server_url . " is not an URL.");
            }
        }
    }
}
