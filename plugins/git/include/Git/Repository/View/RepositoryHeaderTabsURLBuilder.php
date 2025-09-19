<?php
/**
 * Copyright (c) Enalean, 2023 - Present. All Rights Reserved.
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

namespace Tuleap\Git\Repository\View;

use Codendi_Request;
use Git_GitRepositoryUrlManager;
use GitRepository;

final class RepositoryHeaderTabsURLBuilder
{
    private const string BRANCH_NAME_QUERY_PARAMETER = 'hb';
    private const string COMMIT_NAME_QUERY_PARAMETER = 'h';

    public function __construct(private readonly Git_GitRepositoryUrlManager $url_manager)
    {
    }

    public function buildFilesTabURL(GitRepository $repository, Codendi_Request $request): string
    {
        $query_parameters = [];
        $selected_branch  = $request->get(self::BRANCH_NAME_QUERY_PARAMETER);
        $selected_commit  = $request->get(self::COMMIT_NAME_QUERY_PARAMETER);
        if ($selected_branch !== false) {
            $query_parameters = [
                'a'  => 'tree',
                'hb' => $selected_branch,
            ];
        } elseif ($selected_commit !== false) {
            $query_parameters = [
                'a'  => 'tree',
                'hb' => $selected_commit,
            ];
        }

        $url = $this->url_manager->getRepositoryBaseUrl($repository);
        if (! empty($query_parameters)) {
            $url .= '?' . http_build_query($query_parameters);
        }

        return $url;
    }

    public function buildCommitsTabURL(GitRepository $repository, Codendi_Request $request): string
    {
        $query_parameters = [
            'a' => 'shortlog',
        ];

        $selected_branch = $request->get(self::BRANCH_NAME_QUERY_PARAMETER);
        $selected_commit = $request->get(self::COMMIT_NAME_QUERY_PARAMETER);
        if ($selected_branch !== false) {
            $query_parameters = [
                'a' => 'shortlog',
                'hb' => $selected_branch,
            ];
        } elseif ($selected_commit !== false) {
            $query_parameters = [
                'a' => 'commit',
                'h' => $selected_commit,
            ];
        }

        return $this->url_manager->getRepositoryBaseUrl($repository) . '?' . http_build_query($query_parameters);
    }
}
