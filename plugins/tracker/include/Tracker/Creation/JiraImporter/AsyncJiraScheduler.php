<?php
/**
 * Copyright (c) Enalean, 2020 - present. All Rights Reserved.
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

namespace Tuleap\Tracker\Creation\JiraImporter;

use Project;
use Tuleap\Cryptography\ConcealedString;
use Tuleap\Cryptography\KeyFactory;
use Tuleap\Cryptography\Symmetric\SymmetricCrypto;

class AsyncJiraScheduler
{
    public const CONFIG_NAME = 'asynchronous_jira_creation';

    /**
     * @var KeyFactory
     */
    private $key_factory;
    /**
     * @var \Tuleap\Tracker\Creation\JiraImporter\PendingJiraImportDao
     */
    private $dao;

    public function __construct(KeyFactory $key_factory, PendingJiraImportDao $dao)
    {
        $this->key_factory = $key_factory;
        $this->dao         = $dao;
    }

    public function shouldCreationBeAsynchronous(): bool
    {
        return (bool) \ForgeConfig::get(self::CONFIG_NAME) === true;
    }

    public function scheduleCreation(
        Project $project,
        \PFUser $user,
        string $jira_server,
        string $jira_user_email,
        ConcealedString $jira_token,
        string $jira_project_id,
        string $jira_issue_type_name,
        string $tracker_name,
        string $tracker_shortname,
        string $tracker_color,
        string $tracker_description
    ): void {
        $encryption_key = $this->key_factory->getEncryptionKey();
        $this->dao->create(
            (int) $project->getID(),
            (int) $user->getId(),
            $jira_server,
            $jira_user_email,
            SymmetricCrypto::encrypt($jira_token, $encryption_key),
            $jira_project_id,
            $jira_issue_type_name,
            $tracker_name,
            $tracker_shortname,
            $tracker_color,
            $tracker_description
        );
    }
}
