<?php
/**
 * Copyright Enalean (c) 2020 - Present. All rights reserved.
 *
 * Tuleap and Enalean names and logos are registered trademarks owned by
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

namespace Tuleap\HudsonGit\REST\v1;

/**
 * @psalm-immutable
 */
class JenkinsServerRepresentationCollection
{
    /**
     * @var JenkinsServerRepresentation[]
     */
    public $git_jenkins_servers_representations;

    /**
     * @var int
     */
    public $total;

    /**
     * @param JenkinsServerRepresentation[] $git_jenkins_servers_representations
     */
    private function __construct(array $git_jenkins_servers_representations, int $total)
    {
        $this->git_jenkins_servers_representations = $git_jenkins_servers_representations;
        $this->total                               = $total;
    }

    public static function build(array $servers, int $total): self
    {
        $jenkins_server_representations = [];

        foreach ($servers as $server) {
            $jenkins_server_representations[] = JenkinsServerRepresentation::build($server);
        }

        return new self($jenkins_server_representations, $total);
    }
}
