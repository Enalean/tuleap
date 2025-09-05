<?php
/**
 * Copyright (c) Enalean, 2012 - Present. All Rights Reserved.
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

namespace Tuleap\Git\GitViews\RepoManagement\Pane;

use Codendi_Request;
use GitRepository;
use TemplateRendererFactory;
use Tuleap\Git\DefaultBranch\RepositoryBranchSelectorOptionPresenter;
use Tuleap\Git\Repository\Settings\ArtifactClosure\VerifyArtifactClosureIsAllowed;

class GeneralSettings extends Pane
{
    /**
     * @var RepositoryBranchSelectorOptionPresenter[]
     * @psalm-readonly
     */
    public array $available_branches;
    /**
     * @psalm-readonly
     */
    public bool $have_branches;
    /**
     * @psalm-readonly
     */
    public bool $allow_artifact_closure;

    public function __construct(GitRepository $repository, Codendi_Request $request, VerifyArtifactClosureIsAllowed $closure_verifier)
    {
        parent::__construct($repository, $request);

        $this->available_branches     = self::getAvailableBranchPresenters($repository);
        $this->have_branches          = count($this->available_branches) > 0;
        $this->allow_artifact_closure = $closure_verifier->isArtifactClosureAllowed((int) $repository->getId());
    }

    /**
     * @return RepositoryBranchSelectorOptionPresenter[]
     */
    private static function getAvailableBranchPresenters(GitRepository $repository): array
    {
        if (! $repository->isInitialized()) {
            return [];
        }
        $git_exec       = \Git_Exec::buildFromRepository($repository);
        $all_branches   = $git_exec->getAllBranchesSortedByCreationDate();
        $default_branch = $git_exec->getDefaultBranch();

        $presenters = [];

        foreach ($all_branches as $branch) {
            $presenters[] = new RepositoryBranchSelectorOptionPresenter($branch, $default_branch);
        }

        return $presenters;
    }

    /**
     * @see GitViews_RepoManagement_Pane::getIdentifier()
     */
    #[\Override]
    public function getIdentifier()
    {
        return 'settings';
    }

    /**
     * @see GitViews_RepoManagement_Pane::getTitle()
     */
    #[\Override]
    public function getTitle()
    {
        return dgettext('tuleap-git', 'General settings');
    }

    /**
     * @see GitViews_RepoManagement_Pane::getContent()
     */
    #[\Override]
    public function getContent()
    {
        $renderer = TemplateRendererFactory::build()->getRenderer(dirname(GIT_BASE_DIR) . '/templates/settings');

        return $renderer->renderToString('general-settings', $this);
    }

    public function title()
    {
        return $this->getTitle();
    }

    public function project_id()
    {
        return $this->repository->getProjectId();
    }

    public function pane_identifier()
    {
        return $this->getIdentifier();
    }

    public function repository_id()
    {
        return $this->repository->getId();
    }

    public function repository_description_label()
    {
        return dgettext('tuleap-git', 'Description');
    }

    public function description()
    {
        return $this->repository->getDescription();
    }

    public function save_label()
    {
        return dgettext('tuleap-git', 'Save');
    }
}
