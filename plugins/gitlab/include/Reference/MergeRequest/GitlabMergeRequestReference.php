<?php
/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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
 * along with Tuleap. If not, see http://www.gnu.org/licenses/.
 */

declare(strict_types=1);

namespace Tuleap\Gitlab\Reference\MergeRequest;

use Project;
use Tuleap\Gitlab\Repository\GitlabRepositoryIntegration;

class GitlabMergeRequestReference extends \Reference
{
    public const string REFERENCE_NAME = 'gitlab_mr';
    public const string NATURE_NAME    = 'plugin_gitlab_mr';

    public function __construct(
        GitlabRepositoryIntegration $repository_integration,
        Project $project,
        int $id,
    ) {
        parent::__construct(
            0,
            self::REFERENCE_NAME,
            dgettext('tuleap-gitlab', 'GitLab merge request'),
            $repository_integration->getGitlabRepositoryUrl() . '/-/merge_requests/' . $id,
            'S',
            'plugin_gitlab',
            'plugin_gitlab',
            true,
            (int) $project->getID()
        );
    }
}
