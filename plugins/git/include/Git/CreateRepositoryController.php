<?php
/**
 * Copyright (c) Enalean, 2017. All Rights Reserved.
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

namespace Tuleap\Git;

use Exception;
use Git_GitRepositoryUrlManager;
use HTTPRequest;
use Tuleap\Git\Repository\RepositoryCreator;
use Valid_String;

class CreateRepositoryController extends RouterLink
{
    /**
     * @var Git_GitRepositoryUrlManager
     */
    private $url_manager;
    /**
     * @var RepositoryCreator
     */
    private $repository_creator;

    public function __construct(
        Git_GitRepositoryUrlManager $url_manager,
        RepositoryCreator $repository_creator
    ) {
        parent::__construct();

        $this->url_manager        = $url_manager;
        $this->repository_creator = $repository_creator;
    }

    public function process(HTTPRequest $request)
    {
        switch ($request->get('action')) {
            case 'add':
                $this->createRepositoryFromRequest($request);
                break;
            default:
                parent::process($request);
                break;
        }
    }

    private function createRepositoryFromRequest(HTTPRequest $request)
    {
        $repository_name = $this->getNameFromRequest($request);

        $creator    = $request->getCurrentUser();
        $project    = $request->getProject();
        $project_id = $project->getID();

        try {
            $repository = $this->repository_creator->create($project, $creator, $repository_name);
            $redirect_url = $this->url_manager->getRepositoryBaseUrl($repository);
        } catch (Exception $exception) {
            $GLOBALS['Response']->addFeedback('error', $exception->getMessage());
            $redirect_url = '/plugins/git/?action=index&group_id=' . $project_id;
        }

        $GLOBALS['Response']->redirect($redirect_url);
    }

    /**
     * @return null|string
     */
    private function getNameFromRequest(HTTPRequest $request)
    {
        $valid = new Valid_String('repo_name');
        $valid->required();
        $repository_name = null;
        if ($request->valid($valid)) {
            $repository_name = trim($request->get('repo_name'));
        }
        return $repository_name;
    }
}
