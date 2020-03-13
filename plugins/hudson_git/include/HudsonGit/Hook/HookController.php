<?php
/**
 * Copyright (c) Enalean, 2016 - Present. All Rights Reserved.
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

namespace Tuleap\HudsonGit\Hook;

use GitRepository;
use GitRepositoryFactory;
use Tuleap\Git\GitViews\RepoManagement\Pane\Hooks;
use Codendi_Request;
use Feedback;
use CSRFSynchronizerToken;
use Valid_HTTPURI;

class HookController
{

    /**
     * @var CSRFSynchronizerToken
     */
    private $csrf;

    /**
     * @var Codendi_Request
     */
    private $request;

    /**
     * @var GitRepositoryFactory
     */
    private $git_repository_factory;

    /**
     * @var HookDao
     */
    private $dao;

    /**
     * @var Valid_HTTPURI
     */
    private $valid_HTTPURI;

    public function __construct(
        Codendi_Request $request,
        GitRepositoryFactory $git_repository_factory,
        HookDao $dao,
        CSRFSynchronizerToken $csrf,
        Valid_HTTPURI $valid_HTTPURI
    ) {
        $this->request                = $request;
        $this->git_repository_factory = $git_repository_factory;
        $this->dao                    = $dao;
        $this->csrf                   = $csrf;
        $this->valid_HTTPURI          = $valid_HTTPURI;
    }

    public function save()
    {
        $repository = $this->getRepository();
        $this->checkCSRFToken($repository);

        $jenkins_server = trim($this->request->getValidated('url', 'string', ''));

        if (! $this->valid_HTTPURI->validate($jenkins_server)) {
            $GLOBALS['Response']->addFeedback(
                Feedback::ERROR,
                dgettext("tuleap-hudson_git", "The Jenkins server URL provided is not well formed.")
            );

            $GLOBALS['Response']->redirect($this->getRedirectUrl($repository));
        }

        if ($this->dao->save($repository->getId(), $jenkins_server)) {
            $GLOBALS['Response']->addFeedback(Feedback::INFO, $GLOBALS['Language']->getText('plugin_hudson_git', 'update_success'));
        } else {
            $GLOBALS['Response']->addFeedback(Feedback::ERROR, $GLOBALS['Language']->getText('plugin_hudson_git', 'error_database'));
        }
        $GLOBALS['Response']->redirect($this->getRedirectUrl($repository));
    }

    public function remove()
    {
        $repository = $this->getRepository();
        $this->checkCSRFToken($repository);

        if ($this->dao->delete($repository->getId())) {
            $GLOBALS['Response']->addFeedback(Feedback::INFO, $GLOBALS['Language']->getText('plugin_hudson_git', 'delete_success'));
        } else {
            $GLOBALS['Response']->addFeedback(Feedback::ERROR, $GLOBALS['Language']->getText('plugin_hudson_git', 'error_database'));
        }
        $GLOBALS['Response']->redirect($this->getRedirectUrl($repository));
    }

    /** @return GitRepository */
    private function getRepository()
    {
        $repository_id = $this->request->getValidated('repo_id', 'uint', 0);
        $repository    = $this->git_repository_factory->getRepositoryById($repository_id);
        if (! $repository) {
            $GLOBALS['Response']->addFeedback(Feedback::ERROR, $GLOBALS['Language']->getText('plugin_hudson_git', 'error_repository_invalid'));
            $GLOBALS['Response']->redirect(GIT_BASE_URL . "/?group_id=" . $this->request->getProject()->getID());
        }

        return $repository;
    }

    private function checkCSRFToken(GitRepository $repository)
    {
        $this->csrf->check($this->getRedirectUrl($repository));
    }

    private function getRedirectUrl(GitRepository $repository)
    {
        return GIT_BASE_URL . '/?' . http_build_query(array(
            'action'      => 'repo_management',
            'group_id'    => $repository->getProjectId(),
            'repo_id'     => $repository->getId(),
            'pane'        => Hooks::ID
        ));
    }
}
