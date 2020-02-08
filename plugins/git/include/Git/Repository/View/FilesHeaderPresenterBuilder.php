<?php
/**
 * Copyright (c) Enalean, 2018 - present. All Rights Reserved.
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

use Git_GitRepositoryUrlManager;
use GitRepository;
use HTTPRequest;
use Tuleap\Git\GitPHP\Commit;
use Tuleap\Git\GitPHP\Ref;
use Tuleap\Git\GitPHP\RepositoryAccessException;
use Tuleap\Git\Repository\GitPHPProjectRetriever;

class FilesHeaderPresenterBuilder
{
    /**
     * @var CommitForCurrentTreeRetriever
     */
    private $commit_retriever;
    /**
     * @var Git_GitRepositoryUrlManager
     */
    private $url_manager;
    /**
     * @var GitPHPProjectRetriever
     */
    private $gitphp_project_retriever;

    public const GITPHP_VIEWS_WITH_SELECTOR = ['shortlog', 'search', 'blob', 'blame', 'history', 'tree', false];

    public function __construct(
        GitPHPProjectRetriever $gitphp_project_retriever,
        CommitForCurrentTreeRetriever $commit_retriever,
        Git_GitRepositoryUrlManager $url_manager
    ) {
        $this->gitphp_project_retriever = $gitphp_project_retriever;
        $this->commit_retriever         = $commit_retriever;
        $this->url_manager              = $url_manager;
    }

    /**
     *
     * @return FilesHeaderPresenter
     */
    public function build(HTTPRequest $request, GitRepository $repository)
    {
        $repository_url = $this->url_manager->getRepositoryBaseUrl($repository);

        $cannot_be_displayed_presenter = new FilesHeaderPresenter(
            $repository,
            $repository_url,
            false,
            '',
            false,
            '',
            []
        );

        if (! $repository->isCreated()) {
            return $cannot_be_displayed_presenter;
        }

        $action = $request->get('a');
        if (! in_array($action, self::GITPHP_VIEWS_WITH_SELECTOR, true)) {
            return $cannot_be_displayed_presenter;
        }

        try {
            $gitphp_project = $this->gitphp_project_retriever->getFromRepository($repository);
        } catch (RepositoryAccessException $exception) {
            return $cannot_be_displayed_presenter;
        }
        $commit         = $this->commit_retriever->getCommitOfCurrentTree($request, $gitphp_project);
        if (! $commit) {
            if (empty($gitphp_project->GetRefs())) {
                return $cannot_be_displayed_presenter;
            }
        }

        list($head_name, $is_tag) = $commit ? $this->getHeadNameForCurrentCommit($request, $commit) : ['', false];
        $committer_epoch = $commit ? $commit->GetCommitterEpoch() : '';

        return new FilesHeaderPresenter(
            $repository,
            $repository_url,
            true,
            $head_name,
            $is_tag,
            $committer_epoch,
            $this->getURLParameters($request)
        );
    }

    /**
     *
     * @return array [string, bool]
     */
    private function getHeadNameForCurrentCommit(HTTPRequest $request, Commit $commit)
    {
        if (empty($commit->GetHeads()) && empty($commit->GetTags())) {
            return [$commit->GetHash(), false];
        }

        $matching_ref = $this->searchRequestedRef($request, $commit);
        if ($matching_ref) {
            return $matching_ref;
        }

        if (! empty($commit->GetHeads())) {
            return $this->firstBranch($commit);
        }

        return $this->firstTag($commit);
    }

    /**
     * @param Ref[] $refs
     *
     * @return string[]
     */
    private function flattenRefsWithName(array $refs)
    {
        $refs_names = array_map(
            function (Ref $ref) {
                return $ref->GetName();
            },
            $refs
        );

        return $refs_names;
    }

    /**
     *
     * @return array
     */
    private function firstBranch(Commit $commit)
    {
        return [$commit->GetHeads()[0]->GetName(), false];
    }

    /**
     *
     * @return array
     */
    private function firstTag(Commit $commit)
    {
        return [$commit->GetTags()[0]->GetName(), true];
    }

    /**
     *
     * @return array|null
     */
    private function searchRequestedRef(HTTPRequest $request, Commit $commit)
    {
        $requested_hashbase = preg_replace('%^refs/(?:tags|heads)/%', '', $request->get('hb'));

        $matching_ref = null;
        if (array_search($requested_hashbase, $this->flattenRefsWithName($commit->GetHeads())) !== false) {
            $matching_ref = [$requested_hashbase, false];
        }
        if (array_search($requested_hashbase, $this->flattenRefsWithName($commit->GetTags())) !== false) {
            $matching_ref = [$requested_hashbase, true];
        }

        return $matching_ref;
    }

    /**
     * @return array
     */
    private function getURLParameters(HTTPRequest $request)
    {
        $parameters = [];
        $parameters_to_keep = ['a', 'f', 's', 'st', 'm'];
        foreach ($parameters_to_keep as $key) {
            if ($request->exist($key)) {
                $parameters[$key] = $request->get($key);
            } elseif ($key === 'a') {
                $parameters[$key] = 'tree';
            }
        }

        return $parameters;
    }
}
