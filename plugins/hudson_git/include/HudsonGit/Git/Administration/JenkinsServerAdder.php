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

namespace Tuleap\HudsonGit\Git\Administration;

use Project;
use Tuleap\Cryptography\ConcealedString;
use Tuleap\Cryptography\SymmetricLegacy2025\EncryptionKey;
use Tuleap\Cryptography\SymmetricLegacy2025\SymmetricCrypto;
use Valid_HTTPURI;

class JenkinsServerAdder
{
    /**
     * @var JenkinsServerDao
     */
    private $jenkins_server_dao;

    /**
     * @var Valid_HTTPURI
     */
    private $valid_HTTPURI;

    public function __construct(JenkinsServerDao $jenkins_server_dao, Valid_HTTPURI $valid_HTTPURI, private EncryptionKey $encryption_key)
    {
        $this->jenkins_server_dao = $jenkins_server_dao;
        $this->valid_HTTPURI      = $valid_HTTPURI;
    }

    /**
     * @throws JenkinsServerURLNotValidException
     * @throws JenkinsServerAlreadyDefinedException
     */
    public function addServerInProject(Project $project, string $jenkins_server_url, ?ConcealedString $token): void
    {
        $jenkins_server_url = trim($jenkins_server_url);

        if (! $this->valid_HTTPURI->validate($jenkins_server_url)) {
            throw new JenkinsServerURLNotValidException();
        }

        $project_id = (int) $project->getID();
        if (
            $this->jenkins_server_dao->isJenkinsServerAlreadyDefinedInProject(
                $project_id,
                $jenkins_server_url
            )
        ) {
            throw new JenkinsServerAlreadyDefinedException();
        }

        $encrypted_token = null;
        if ($token !== null) {
            $encrypted_token = SymmetricCrypto::encrypt($token, $this->encryption_key);
        }

        $this->jenkins_server_dao->addJenkinsServer(
            $project_id,
            $jenkins_server_url,
            $encrypted_token
        );
    }
}
