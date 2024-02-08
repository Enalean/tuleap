<?php
/**
 * Copyright (c) Enalean, 2016 - Present. All Rights Reserved.
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

namespace Tuleap\SVN;

use Psr\Log\LoggerInterface;
use Project;
use SimpleXMLElement;
use Tuleap\Project\XML\Import\ImportConfig;
use Tuleap\SVN\AccessControl\AccessFileHistoryCreator;
use Tuleap\SVN\Admin\MailNotificationManager;
use Tuleap\SVN\Notifications\NotificationsEmailsBuilder;
use Tuleap\SVN\Repository\RepositoryCreator;
use Tuleap\SVN\Repository\RepositoryManager;
use Tuleap\SVN\Repository\RuleName;

class XMLImporter
{
    /** @var XMLRepositoryImporter[] */
    private $repositories_data;

    public function __construct(
        SimpleXMLElement $xml,
        $extraction_path,
        RepositoryCreator $repository_creator,
        \BackendSVN $backend_svn,
        AccessFileHistoryCreator $access_file_history_creator,
        RepositoryManager $repository_manager,
        \UserManager $user_manager,
        NotificationsEmailsBuilder $notifications_emails_builder,
        XMLUserChecker $xml_user_checker,
    ) {
        $this->repositories_data = [];

        if (empty($xml->svn)) {
            return;
        }

        foreach ($xml->svn->children() as $xml_repo) {
            if ($xml_repo->getName() != "repository") {
                continue;
            }
            $this->repositories_data[] = new XMLRepositoryImporter(
                $xml_repo,
                $extraction_path,
                $repository_creator,
                $backend_svn,
                $access_file_history_creator,
                $repository_manager,
                $user_manager,
                $notifications_emails_builder,
                $xml_user_checker
            );
        }
    }

    public function import(
        ImportConfig $configuration,
        LoggerInterface $logger,
        Project $project,
        AccessFileHistoryCreator $accessfile_history_creator,
        MailNotificationManager $mail_notification_manager,
        RuleName $rule_name,
        \PFUser $committer,
    ) {
        $logger->info("[svn] Importing " . count($this->repositories_data) . " SVN repositories");
        foreach ($this->repositories_data as $repo) {
            $repo->import(
                $configuration,
                $logger,
                $project,
                $accessfile_history_creator,
                $mail_notification_manager,
                $rule_name,
                $committer
            );
        }
        $logger->info("[svn] Subversion Import Finished");
    }
}
