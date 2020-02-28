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

    public function __construct(JenkinsServerAdder $jenkins_server_adder)
    {
        $this->jenkins_server_adder = $jenkins_server_adder;
    }

    public function import(Project $project, SimpleXMLElement $xml_git): void
    {
        if (! isset($xml_git->{"jenkins-servers-admin"})) {
            return;
        }

        foreach ($xml_git->{"jenkins-servers-admin"}->{"jenkins-server"} as $xml_jenkins_server) {
            try {
                $this->jenkins_server_adder->addServerInProject(
                    $project,
                    (string) $xml_jenkins_server['url']
                );
            } catch (JenkinsServerAlreadyDefinedException | JenkinsServerURLNotValidException $exception) {
                //Do nothing but log
            }
        }
    }
}
