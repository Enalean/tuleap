<?php
/**
 * Copyright (c) Enalean, 2013 - present. All Rights Reserved.
 * Copyright (c) 2010 Christopher Han
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

use Tuleap\Git\GitPHP\Config;
use Tuleap\Git\GitPHP\Controller;
use Tuleap\Git\GitPHP\Controller_Message;
use Tuleap\Git\GitPHP\MessageException;
use Tuleap\Git\GitPHP\ProjectList;

class GitViews_GitPhpViewer
{
    public const GLOSSIFIED_GITPHP_ACTIONS = [
        'blob',
        'blobdiff',
        'blame',
        'history',
        'commit',
        'commitdiff',
        'shortlog',
        'search',
        'tree',
        false
    ];

    /**
     * @var GitRepository
     */
    private $repository;

    public function __construct(GitRepository $repository)
    {
        $this->repository   = $repository;
    }

    public function displayContent(HTTPRequest $request)
    {
        if ($this->canDisplayEnclosingDiv($request)) {
            echo '
                <section class="tlp-pane">
                    <div class="tlp-pane-container">
                        <div class="tlp-pane-header">
                            <h1 class="tlp-pane-title"><i class="tlp-pane-title-icon fa fa-files-o"></i> Files</h1>
                        </div>
                        <section class="tlp-pane-section">
                            <div id="gitphp" class="plugin_git_gitphp">';
        }

        $this->displayContentWithoutEnclosingDiv();

        if ($this->canDisplayEnclosingDiv($request)) {
            echo '          </div>
                        </section>
                    </div>
                </section>';
        }
    }

    public function displayContentWithoutEnclosingDiv()
    {
        set_time_limit(300);
        $this->displayGitPHP();
    }

    private function canDisplayEnclosingDiv(HTTPRequest $request)
    {
        return ! in_array($request->get('a'), self::GLOSSIFIED_GITPHP_ACTIONS, true);
    }

    private function displayGitPHP()
    {
        try {
            $this->setupGitPHPConfiguration();

            ProjectList::Instantiate($this->repository);

            $controller = Controller::GetController($_GET['a'] ?? '');
            if ($controller) {
                $controller->RenderHeaders();
                $controller->Render();
            }
        } catch (Exception $e) {
            $controller = new Controller_Message();
            $controller->SetParam('message', $e->getMessage());
            if ($e instanceof MessageException) {
                $controller->SetParam('error', $e->Error);
                $controller->SetParam('statuscode', $e->StatusCode);
            } else {
                $controller->SetParam('error', true);
            }
            $controller->RenderHeaders();
            $controller->Render();
        }
    }

    private function setupGitPHPConfiguration()
    {
        $config = Config::GetInstance();
        $config->SetValue('gittmp', '/tmp/');
        $config->SetValue('title', 'Tuleap');
        $config->SetValue('compressformat', \Tuleap\Git\GitPHP\Archive::COMPRESS_BZ2);
        $config->SetValue('compresslevel', 9);
        $config->SetValue('filemimetype', true);
        $config->SetValue('search', true);
        $config->SetValue('smarty_tmp', '/tmp/gitphp-tuleap/smarty');
    }
}
