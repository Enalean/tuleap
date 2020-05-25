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

namespace Tuleap\Tracker\Creation\JiraImporter;

use DateTimeImmutable;
use PFUser;
use Project;

class PendingJiraImport
{
    /**
     * @var Project
     */
    private $project;
    /**
     * @var PFUser
     */
    private $user;
    /**
     * @var DateTimeImmutable
     */
    private $created_on;
    /**
     * @var string
     */
    private $jira_server;
    /**
     * @var string
     */
    private $jira_project_id;
    /**
     * @var string
     */
    private $jira_issue_type_name;
    /**
     * @var string
     */
    private $tracker_name;
    /**
     * @var string
     */
    private $tracker_shortname;
    /**
     * @var string
     */
    private $tracker_color;
    /**
     * @var string
     */
    private $jira_user;
    /**
     * @var int
     */
    private $id;
    /**
     * @var string
     */
    private $encrypted_jira_token;
    /**
     * @var string
     */
    private $tracker_description;

    public function __construct(
        int $id,
        Project $project,
        PFUser $user,
        DateTimeImmutable $created_on,
        string $jira_server,
        string $jira_user,
        string $encrypted_jira_token,
        string $jira_project_id,
        string $jira_issue_type_name,
        string $tracker_name,
        string $tracker_shortname,
        string $tracker_color,
        string $tracker_description
    ) {
        $this->id                   = $id;
        $this->project              = $project;
        $this->user                 = $user;
        $this->created_on           = $created_on;
        $this->jira_server          = $jira_server;
        $this->jira_user            = $jira_user;
        $this->jira_project_id      = $jira_project_id;
        $this->jira_issue_type_name = $jira_issue_type_name;
        $this->tracker_name         = $tracker_name;
        $this->tracker_shortname    = $tracker_shortname;
        $this->tracker_color        = $tracker_color;
        $this->encrypted_jira_token = $encrypted_jira_token;
        $this->tracker_description  = $tracker_description;
    }

    public function getProject(): Project
    {
        return $this->project;
    }

    public function getUser(): PFUser
    {
        return $this->user;
    }

    public function getCreatedOn(): DateTimeImmutable
    {
        return $this->created_on;
    }

    public function getJiraServer(): string
    {
        return $this->jira_server;
    }

    public function getJiraProjectId(): string
    {
        return $this->jira_project_id;
    }

    public function getJiraIssueTypeName(): string
    {
        return $this->jira_issue_type_name;
    }

    public function getTrackerName(): string
    {
        return $this->tracker_name;
    }

    public function getTrackerShortname(): string
    {
        return $this->tracker_shortname;
    }

    public function getTrackerColor(): string
    {
        return $this->tracker_color;
    }

    public function getJiraUser(): string
    {
        return $this->jira_user;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getEncryptedJiraToken(): string
    {
        return $this->encrypted_jira_token;
    }

    public function getTrackerDescription(): string
    {
        return $this->tracker_description;
    }
}
