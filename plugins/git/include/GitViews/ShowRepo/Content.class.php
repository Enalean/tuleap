<?php
/**
 * Copyright (c) Enalean, 2013-2018. All Rights Reserved.
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

use Tuleap\Git\History\GitPhpAccessLogger;

class GitViews_ShowRepo_Content
{

    /**
     * @var HTTPRequest
     */
    private $request;
    /**
     * @var GitRepository
     */
    protected $repository;
    /**
     * @var GitViews_GitPhpViewer
     */
    private $gitphp_viewer;

    /** @var Git_Mirror_MirrorDataMapper */
    private $mirror_data_mapper;
    /**
     * @var GitPhpAccessLogger
     */
    private $access_logger;

    public function __construct(
        GitRepository $repository,
        GitViews_GitPhpViewer $gitphp_viewer,
        HTTPRequest $request,
        Git_Mirror_MirrorDataMapper $mirror_data_mapper,
        GitPhpAccessLogger $access_logger
    ) {
        $this->repository         = $repository;
        $this->gitphp_viewer      = $gitphp_viewer;
        $this->request            = $request;
        $this->mirror_data_mapper = $mirror_data_mapper;
        $this->access_logger      = $access_logger;
    }

    public function display()
    {
        if ($this->repository->isCreated()) {
            $this->gitphp_viewer->displayContent($this->request);

            $this->access_logger->logAccess($this->repository, $this->request->getCurrentUser());
        } else {
            echo $this->getWaitingForRepositoryCreationInfo();
        }
    }

    private function getWaitingForRepositoryCreationInfo()
    {
        $html = '<div class="tlp-alert-info">';

        $html .= dgettext('tuleap-git', 'The repository is in queue for creation. Please check back here in a few minutes');

        $default_mirrors = $this->mirror_data_mapper->fetchAllRepositoryMirrors($this->repository);

        if ($default_mirrors) {
            $default_mirrors_names = array_map(
                static function (Git_Mirror_Mirror $mirror): string {
                    $purifier = Codendi_HTMLPurifier::instance();

                    return $purifier->purify($mirror->name);
                },
                $default_mirrors
            );

            $html .= '<br/>';
            $html .= sprintf(dgettext('tuleap-git', 'The repository will be automatically mirrored on: %1$s.'), implode(', ', $default_mirrors_names));
        }

        $html .= '</div>';
        return $html;
    }
}
