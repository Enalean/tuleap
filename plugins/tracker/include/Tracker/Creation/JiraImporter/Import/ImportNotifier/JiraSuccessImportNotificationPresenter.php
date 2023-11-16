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

namespace Tuleap\Tracker\Creation\JiraImporter\Import\ImportNotifier;

use Tracker;
use Tuleap\Date\DateHelper;
use Tuleap\Tracker\Creation\JiraImporter\Import\User\JiraUserOnTuleapCache;
use Tuleap\Tracker\Creation\JiraImporter\PendingJiraImport;

class JiraSuccessImportNotificationPresenter
{
    /**
     * @var string
     */
    public $link;

    /**
     * @var string
     */
    public $tracker_name;

    /**
     * @var string
     */
    public $title;

    /**
     * @var string
     */
    public $localized_created_on;

    /**
     * @var array
     */
    public $identified_users;

    /**
     * @var array
     */
    public $email_not_matching;

    /**
     * @var array
     */
    public $unknown_users;

    /**
     * @var bool
     */
    public $has_identified_users;

    /**
     * @var bool
     */
    public $has_email_not_matching;

    /**
     * @var bool
     */
    public $has_unknown_users;

    /**
     * @var bool
     */
    public $has_not_identified_users;

    public function __construct(
        PendingJiraImport $pending_jira_import,
        string $link,
        Tracker $tracker,
        string $subject,
        JiraUserOnTuleapCache $jira_user_on_tuleap_cache,
    ) {
        $this->link                 = $link;
        $this->tracker_name         = $tracker->getName();
        $this->title                = $subject;
        $this->localized_created_on = DateHelper::formatForLanguage(
            $pending_jira_import->getUser()->getLanguage(),
            $pending_jira_import->getCreatedOn()->getTimestamp()
        );

        $jira_tuleap_users_mapping      = $jira_user_on_tuleap_cache->getJiraTuleapUsersMapping();
        $this->identified_users         = $jira_tuleap_users_mapping->getIdentifiedUsers();
        $this->email_not_matching       = $jira_tuleap_users_mapping->getUserEmailsNotMatching();
        $this->unknown_users            = $jira_tuleap_users_mapping->getUnknownUsers();
        $this->has_identified_users     = count($this->identified_users) > 0;
        $this->has_email_not_matching   = count($this->email_not_matching) > 0;
        $this->has_unknown_users        = count($this->unknown_users) > 0;
        $this->has_not_identified_users = $this->has_email_not_matching || $this->has_unknown_users;
    }
}
