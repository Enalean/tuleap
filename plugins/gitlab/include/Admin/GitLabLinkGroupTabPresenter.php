<?php
/**
 * Copyright (c) Enalean, 2022 - present. All Rights Reserved.
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

namespace Tuleap\Gitlab\Admin;

use Project;
use Tuleap\Git\GitPresenters\AdminExternalPanePresenter;

final class GitLabLinkGroupTabPresenter
{
    public const string PANE_NAME = 'gitlab';

    public static function withInactiveState(Project $project): AdminExternalPanePresenter
    {
        return self::buildWithState($project, false);
    }

    public static function withActiveState(Project $project): AdminExternalPanePresenter
    {
        return self::buildWithState($project, true);
    }

    private static function buildWithState(Project $project, bool $is_active): AdminExternalPanePresenter
    {
        return new AdminExternalPanePresenter(
            dgettext('tuleap-gitlab', 'GitLab Group Link'),
            GIT_BASE_URL . '/' . urlencode($project->getUnixName()) . '/administration/gitlab/',
            $is_active
        );
    }
}
