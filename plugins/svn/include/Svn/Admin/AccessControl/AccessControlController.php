<?php

/**
 * Copyright (c) Enalean, 2016. All rights reserved
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/
 */

namespace Tuleap\Svn\Admin\AccessControl;

use Tuleap\Svn\ServiceSvn;
use HTTPRequest;
use Tuleap\Svn\Repository\Repository;
use Tuleap\Svn\Repository\RepositoryManager;
use Tuleap\Svn\Admin\AccessControl\AccessFileHistory;
use Tuleap\Svn\Admin\AccessControl\AccessFileHistoryManager;
use SVNAccessFile;
use CSRFSynchronizerToken;
use SVN_AccessFile_Writer;

class AccessControlController {

    /** @var RepositoryManager */
    private $repository_manager;

    /** @var AccessFileHistoryManager */
    private $access_file_manager;

    public function __construct(RepositoryManager $repository_manager, AccessFileHistoryManager $access_file_manager) {
        $this->repository_manager  = $repository_manager;
        $this->access_file_manager = $access_file_manager;
    }

    private function getToken(Repository $repository) {
        return new CSRFSynchronizerToken($this->getUrl($repository));
    }

    private function getUrl(Repository $repository) {
        return SVN_BASE_URL.'/?'. http_build_query(array(
            'group_id' => $repository->getProject()->getId(),
            'repo_id'  => $repository->getId(),
            'action'   => 'access-control'
        ));
    }

    public function displayAuthFile(ServiceSvn $service, HTTPRequest $request) {
        $repository = $this->repository_manager->getById($request->get('repo_id'), $request->getProject());

        $versions = array();
        foreach ($this->access_file_manager->getByRepository($repository) as $historised_accessfile) {
            $versions[] = array(
                'file_id' => $historised_accessfile->getId(),
                'version' => $historised_accessfile->getVersionNumber(),
                'date'    => format_date("Y-m-d", $historised_accessfile->getVersionDate())
            );
        }

        $current_version        = $this->access_file_manager->getCurrentVersion($repository);
        $current_version_number = $current_version->getVersionNumber();
        $last_version_number    = $this->access_file_manager->getLastVersion($repository)->getVersionNumber();

        $title = $repository->getName() .' – '. $GLOBALS['Language']->getText('global', 'Administration');

        if ($request->exist('form_accessfile')) {
            $content = $request->get('form_accessfile');
        } else {
            $content = $current_version->getContent();
        }
        $accessfile = new SVN_AccessFile_Writer($repository->getSystemPath());
        $access_file_defaults = $accessfile->read_defaults(true);

        $service->renderInPage(
            $request,
            $title,
            'admin/edit_authfile',
            new AccessControlPresenter(
                    $this->getToken($repository),
                    $repository,
                    $title,
                    $access_file_defaults,
                    $content,
                    $versions,
                    $current_version_number,
                    $last_version_number
            )
        );
    }

    public function displayArchivedVersion(HTTPRequest $request) {
        $id         = $request->get('accessfile_history_id');
        $repository = $this->repository_manager->getById($request->get('repo_id'), $request->getProject());

        $result = $this->access_file_manager->getById($id, $repository);

        $GLOBALS['Response']->sendJSON(array('content' => $result['content']));
    }

    public function saveAuthFile(ServiceSvn $service, HTTPRequest $request){
        $repository = $this->repository_manager->getById($request->get('repo_id'), $request->getProject());
        $this->getToken($repository)->check();

        if ($request->exist('submit_other_version')) {
            $this->useAnOldVersion($service, $request, $repository, $request->get('version_selected'));
        } else {
            $this->createANewVersion($service, $request, $repository, $request->get('form_accessfile'));
        }
    }

    private function useAnOldVersion(ServiceSvn $service, HTTPRequest $request, Repository $repository, $version_id) {
        if ($this->access_file_manager->useAnOldVersion($repository, $version_id)) {
            $current_version = $this->access_file_manager->getCurrentVersion($repository);
            $this->saveAccessFile($service, $request, $repository, $current_version);
        } else {
            $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_svn_admin','update_fail'));
        }
        $GLOBALS['Response']->redirect($this->getUrl($repository));
    }

    private function createANewVersion(ServiceSvn $service, HTTPRequest $request, Repository $repository, $content) {
        $history = $this->createHistory($repository, $content);

        $this->saveAccessFile($service, $request, $repository, $history);
    }

    private function cleanContent(Repository $repository, $content) {
        $access_file = new SVNAccessFile();
        return trim(
            $access_file->parseGroupLinesByRepositories($repository->getSystemPath(), $content, true)
        );
    }

    private function createHistory(Repository $repository, $content) {
        $id             = 0;
        $version_number = $this->access_file_manager->getLastVersion($repository)->getVersionNumber();

        $file_history = new AccessFileHistory(
            $repository,
            $id,
            $version_number + 1,
            $this->cleanContent($repository, $content),
            $_SERVER['REQUEST_TIME']
        );
        $this->access_file_manager->create($file_history);

        return $file_history;
    }

    private function saveAccessFile(ServiceSvn $service, HTTPRequest $request, Repository $repository, AccessFileHistory $history) {
        $accessfile = new SVN_AccessFile_Writer($repository->getSystemPath());
        if ($accessfile->write_with_defaults($history->getContent())) {
            $GLOBALS['Response']->addFeedback('info', $GLOBALS['Language']->getText('plugin_svn_admin','update_success'));
            $GLOBALS['Response']->redirect($this->getUrl($repository));
        } else {
            if ($accessfile->isErrorFile()) {
                $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_svn_admin','file_error', $repository->getSystemPath()));
            } else if($accessfile->isErrorWrite()) {
                $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_svn_admin','write_error', $repository->getSystemPath()));
            }
            $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_svn_admin','update_fail'));
            $this->displayAuthFile($service, $request);
        }
    }
}
