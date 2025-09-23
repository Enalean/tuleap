<?php
/**
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

declare(strict_types=1);

namespace Tuleap\Gitlab\REST\v1;

use Project;
use Tuleap\Project\REST\MinimalProjectRepresentation;
use Tuleap\REST\JsonCast;

/**
 * @psalm-immutable
 */
class GitlabRepositoryRepresentation
{
    public const string ROUTE = 'gitlab_repositories';

    /**
     * @var int
     */
    public $id;

    /**
     * @var int
     */
    public $gitlab_repository_id;

    /**
     * @var string
     */
    public $name;

    /**
     * @var string
     */
    public $description;

    /**
     * @var string
     */
    public $gitlab_repository_url;

    /**
     * @var string
     */
    public $last_push_date;

    /**
     * @var MinimalProjectRepresentation
     */
    public $project;

    /**
     * @var bool
     */
    public $allow_artifact_closure;
    /**
     * @var bool
     */
    public $is_webhook_configured;

    public string $create_branch_prefix;

    public function __construct(
        int $id,
        int $gitlab_repository_id,
        string $name,
        string $description,
        string $gitlab_repository_url,
        int $last_push_date_timestamp,
        Project $project,
        bool $allow_artifact_closure,
        bool $is_webhook_configured,
        string $create_branch_prefix,
    ) {
        $this->id                     = JsonCast::toInt($id);
        $this->gitlab_repository_id   = JsonCast::toInt($gitlab_repository_id);
        $this->name                   = $name;
        $this->description            = $description;
        $this->gitlab_repository_url  = $gitlab_repository_url;
        $this->last_push_date         = JsonCast::toDate($last_push_date_timestamp);
        $this->project                = new MinimalProjectRepresentation($project);
        $this->allow_artifact_closure = JsonCast::toBoolean($allow_artifact_closure);
        $this->is_webhook_configured  = JsonCast::toBoolean($is_webhook_configured);
        $this->create_branch_prefix   = $create_branch_prefix;
    }
}
