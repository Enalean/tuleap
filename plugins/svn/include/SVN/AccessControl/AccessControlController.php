<?php
/**
 * Copyright (c) Enalean, 2016 - Present. All rights reserved
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

namespace Tuleap\SVN\AccessControl;

use SVN_AccessFile_Writer;
use Tuleap\SVN\ServiceSvn;
use HTTPRequest;
use Tuleap\SVN\Repository\Repository;
use Tuleap\SVN\Repository\RepositoryManager;
use CSRFSynchronizerToken;

class AccessControlController
{
    /** @var AccessFileHistoryCreator */
    private $access_file_creator;

    /** @var RepositoryManager */
    private $repository_manager;

    /** @var AccessFileHistoryFactory */
    private $access_file_factory;

    public function __construct(
        RepositoryManager $repository_manager,
        AccessFileHistoryFactory $access_file_factory,
        AccessFileHistoryCreator $access_file_creator,
    ) {
        $this->repository_manager  = $repository_manager;
        $this->access_file_factory = $access_file_factory;
        $this->access_file_creator = $access_file_creator;
    }

    private function getToken(Repository $repository)
    {
        return new CSRFSynchronizerToken($this->getUrl($repository));
    }

    private function getUrl(Repository $repository)
    {
        return SVN_BASE_URL . '/?' . http_build_query([
            'group_id' => $repository->getProject()->getId(),
            'repo_id' => $repository->getId(),
            'action' => 'access-control',
        ]);
    }

    public function displayAuthFile(ServiceSvn $service, HTTPRequest $request)
    {
        $repository = $this->repository_manager->getByIdAndProject($request->get('repo_id'), $request->getProject());

        $versions = [];
        foreach ($this->access_file_factory->getByRepository($repository) as $historised_accessfile) {
            $versions[] = [
                'file_id' => $historised_accessfile->getId(),
                'version' => $historised_accessfile->getVersionNumber(),
                'date' => format_date("Y-m-d", $historised_accessfile->getVersionDate()),
            ];
        }

        $current_version_number = $this->access_file_factory->getCurrentVersion($repository)->getVersionNumber();
        $last_version_number    = $this->access_file_factory->getLastVersion($repository)->getVersionNumber();

        $title = $GLOBALS['Language']->getText('global', 'Administration');

        $accessfile_reader       = new AccessFileReader();
        $svn_access_file_content = $accessfile_reader->getAccessFileContent($repository);
        if ($request->exist('form_accessfile')) {
            $svn_access_file_content = SvnAccessFileContent::fromSubmittedContent($svn_access_file_content, $request->get('form_accessfile'));
        }

        $duplicate_path_detector = new DuplicateSectionDetector();
        $faults                  = $duplicate_path_detector->inspect($svn_access_file_content);
        foreach ($faults as $fault) {
            $GLOBALS['Response']->addFeedback(\Feedback::WARN, (string) $fault);
        }

        $GLOBALS['HTML']->includeJavascriptSnippet(
            file_get_contents($GLOBALS['Language']->getContent('script_locale', null, 'svn', '.js'))
        );

        $service->renderInPageRepositoryAdministration(
            $request,
            $title,
            'admin/edit_authfile',
            new AccessControlPresenter(
                $this->getToken($repository),
                $repository,
                $title,
                $svn_access_file_content,
                $versions,
                $current_version_number,
                $last_version_number
            ),
            '',
            $repository,
        );
    }

    public function displayArchivedVersion(HTTPRequest $request)
    {
        $id         = $request->get('accessfile_history_id');
        $repository = $this->repository_manager->getByIdAndProject($request->get('repo_id'), $request->getProject());

        $access_file = $this->access_file_factory->getById($id, $repository);

        $GLOBALS['Response']->sendJSON(['content' => $access_file->getContent()]);
    }

    public function saveAuthFile(ServiceSvn $service, HTTPRequest $request)
    {
        $repository = $this->repository_manager->getByIdAndProject($request->get('repo_id'), $request->getProject());
        $this->getToken($repository)->check();

        try {
            if ($request->exist('submit_other_version')) {
                $this->access_file_creator->useAVersionWithHistory($repository, $request->get('version_selected'));
            } else {
                $faults = $this->access_file_creator->create(
                    $repository,
                    $request->get('form_accessfile'),
                    $_SERVER['REQUEST_TIME'],
                    new SVN_AccessFile_Writer($repository->getSystemPath()),
                );
                foreach ($faults as $fault) {
                    $GLOBALS['Response']->addFeedback(\Feedback::WARN, (string) $fault);
                }
            }
            $GLOBALS['Response']->redirect($this->getUrl($repository));
        } catch (CannotCreateAccessFileHistoryException $exception) {
            $GLOBALS['Response']->addFeedback('error', dgettext('tuleap-svn', 'Error during new revision file creation'));
            $GLOBALS['Response']->addFeedback('error', $exception->getMessage());
            $this->displayAuthFile($service, $request);
        }
    }
}
