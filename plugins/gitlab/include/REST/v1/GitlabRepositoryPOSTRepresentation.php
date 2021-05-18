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

/**
 * @psalm-immutable
 */
class GitlabRepositoryPOSTRepresentation
{
    /**
     * @var int
     */
    public $project_id;

    /**
     * @var string {@pattern /^https:\/\//i}
     */
    public $gitlab_server_url;

    /**
     * @var string
     */
    public $gitlab_bot_api_token;

    /**
     * @var int
     */
    public $gitlab_repository_id;

    /**
     * @var bool {@required false}
     */
    public $allow_artifact_closure;
}
