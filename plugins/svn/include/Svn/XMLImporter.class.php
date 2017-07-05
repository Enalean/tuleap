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

namespace Tuleap\Svn;

use Backend;
use Logger;
use SimpleXMLElement;
use Project;
use SystemEventManager;
use Tuleap\Project\XML\Import\ImportConfig;
use Tuleap\Svn\Repository\RepositoryManager;
use Tuleap\Svn\Repository\RuleName;
use Tuleap\Svn\AccessControl\AccessFileHistoryCreator;
use Tuleap\Svn\Admin\MailNotificationManager;

class XMLImporter {

    /** @var array */
    private $repositories_data;

    public function __construct(
            Backend $backend,
            SimpleXMLElement $xml,
            $extraction_path)
    {
        $this->repositories_data = array();

        if(empty($xml->svn)) {
            return;
        }

        foreach($xml->svn->children() as $xml_repo) {
            if($xml_repo->getName() != "repository") {
                continue;
            }
            $this->repositories_data[] = new XMLRepositoryImporter($backend, $xml_repo, $extraction_path);
        }
    }

    public function import(
        ImportConfig $configuration,
        Logger $logger,
        Project $project,
        RepositoryManager $repository_manager,
        AccessFileHistoryCreator $accessfile_history_creator,
        MailNotificationManager $mail_notification_manager,
        RuleName $rule_name)
    {
        $logger->info("[svn] Importing " . count($this->repositories_data) . " SVN repositories");
        foreach($this->repositories_data as $repo) {
            $repo->import(
                $configuration,
                $logger,
                $project,
                $repository_manager,
                $accessfile_history_creator,
                $mail_notification_manager,
                $rule_name
            );
        }
        $logger->info("[svn] Subversion Import Finished");
    }

}

