<?php
/**
 * Copyright (c) Enalean, 2017-2018. All Rights Reserved.
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

namespace Tuleap\Git\Repository\Settings;

use Feedback;
use GitRepoNotFoundException;
use GitRepository;
use HTTPRequest;
use Tuleap\Git\Repository\RepositoryFromRequestRetriever;

abstract class SettingsController
{
    /**
     * @var RepositoryFromRequestRetriever
     */
    private $repository_retriever;

    public function __construct(RepositoryFromRequestRetriever $repository_retriever)
    {
        $this->repository_retriever = $repository_retriever;
    }

    /**
     * @return GitRepository
     */
    protected function getRepositoryUserCanAdministrate(HTTPRequest $request)
    {
        try {
            return $this->repository_retriever->getRepositoryUserCanAdministrate($request);
        } catch (GitRepoNotFoundException $exception) {
            $GLOBALS['Response']->addFeedback(
                Feedback::ERROR,
                $GLOBALS['Language']->getText('plugin_git', 'actions_repo_not_found')
            );
            $GLOBALS['Response']->redirect('/plugins/git/?action=index&group_id=' . $request->getProject()->getID());
        } catch (UserCannotAdministrateRepositoryException $exception) {
            $GLOBALS['Response']->addFeedback(
                Feedback::ERROR,
                $GLOBALS['Language']->getText('plugin_git', 'controller_access_denied')
            );
            $GLOBALS['Response']->redirect('/plugins/git/' . urlencode($request->getProject()->getUnixNameLowerCase()));
        }
    }
}
