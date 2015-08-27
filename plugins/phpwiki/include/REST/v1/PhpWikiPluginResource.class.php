<?php
/**
 * Copyright (c) Enalean, 2015. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

namespace Tuleap\PhpWiki\REST\v1;

use Tuleap\REST\AuthenticatedResource;
use Tuleap\REST\Header;
use Luracast\Restler\RestException;
use Tuleap\PhpWiki\REST\v1\PhpWikiPluginPageFullRepresentation;
use Tuleap\REST\ProjectAuthorization;
use PHPWikiPage;
use PHPWiki;
use PHPWikiDao;
use UserManager;
use ProjectManager;
use URLVerification;
use PFUser;
use PHPWikiVersionDao;
use PHPWikiPageVersionFactory;

class PhpWikiPluginResource extends AuthenticatedResource {

    /** @var PHPWikiDao */
    private $wiki_dao;

    /** @var PHPWikiPageVersionFactory */
    private $wiki_version_factory;

    public function __construct() {
        $this->wiki_dao             = new PHPWikiDao();
        $this->wiki_version_factory = new PHPWikiPageVersionFactory(new PHPWikiVersionDao());
        $this->user_manager         = UserManager::instance();
        $this->project_manager      = ProjectManager::instance();
    }

    /**
     * @url OPTIONS {id}
     */
    public function options($id) {
        Header::allowOptionsGet();
    }

    /**
     * Get a PhpWiki page representation
     *
     * Get some information about a non empty PhpWiki page like urlencoded name, page id, versions ...
     *
     * @url GET {id}
     *
     * @access hybrid
     *
     * @param int $id Id of the wiki page
     *
     * @throws 403
     *
     * @return Tuleap\PhpWiki\REST\v1\PhpWikiPluginPageFullRepresentation
     */
    public function get($id) {
        $this->checkAccess();
        $this->checkPhpWikiPageExists($id);

        $wiki_page    = new PHPWikiPage($id);
        $current_user = $this->user_manager->getCurrentUser();

        $this->checkUserCanAccessProject($current_user, $wiki_page->getGid());
        $this->checkServiceIsActivated($wiki_page->getGid());

        $this->checkUserCanAccessPhpWikiService($current_user, $wiki_page->getGid());
        $this->checkUserCanSeeWikiPage($current_user, $wiki_page);

        $wiki_page_representation = new PhpWikiPluginPageFullRepresentation();
        $wiki_page_representation->build($wiki_page);

        return $wiki_page_representation;
    }

    /**
     * @url OPTIONS {id}/versions
     */
    public function optionsVersions($id) {
        Header::allowOptionsGet();
    }

    /**
     * Get a PhpWiki page version representation
     *
     * Get the content of a non empty PhpWiki page version.
     *
     * Actually it returns a collection filtered by the version_id.
     *
     * @url GET {id}/versions
     *
     * @access hybrid
     *
     * @param int $id         Id of the wiki page
     * @param int $version_id Id of the version to filter the collection. If version_id=0, we return the last version. {@from request}
     *
     * @throws 401
     * @throws 403
     * @throws 404
     *
     * @return array {@type Tuleap\PhpWiki\REST\v1\PhpWikiPluginPageVersionFullRepresentation}
     */
    public function getVersions($id, $version_id) {
        $this->checkAccess();
        $this->checkPhpWikiPageExists($id);

        $wiki_page    = new PHPWikiPage($id);
        $current_user = $this->user_manager->getCurrentUser();

        $this->checkUserCanAccessProject($current_user, $wiki_page->getGid());
        $this->checkServiceIsActivated($wiki_page->getGid());

        $this->checkUserCanAccessPhpWikiService($current_user, $wiki_page->getGid());
        $this->checkUserCanSeeWikiPage($current_user, $wiki_page);

        $page_version = $this->getVersion($wiki_page, $version_id);

        $wiki_page_representation = new PhpWikiPluginPageVersionFullRepresentation();
        $wiki_page_representation->build($wiki_page, $page_version);

        return array($wiki_page_representation);
    }

    /** @return PHPWikiPageVersion */
    private function getVersion(PHPWikiPage $wiki_page, $version_id) {
        if ($version_id === 0) {
            $version_id = $wiki_page->getCurrentVersion();
        }

        $wiki_page_version = $this->wiki_version_factory->getPageVersion($wiki_page->getId(), $version_id);

        if ($wiki_page_version === null) {
            throw new RestException(404);
        }

        return $wiki_page_version;
    }

    private function checkPhpWikiPageExists($page_id) {
        $exists = $this->wiki_dao->doesWikiPageExistInRESTContext($page_id);

        if (! $exists) {
            throw new RestException(404, 'The PhpWiki page does not exist');
        }
    }

    private function checkUserCanAccessPhpWikiService(PFUser $user, $project_id) {
        $wiki_service = new PHPWiki($project_id);

        if (! $wiki_service->isAutorized($user->getId())) {
            throw new RestException(403, 'You are not allowed to access to PhpWiki service');
        }
    }

    private function checkUserCanSeeWikiPage(PFUser $user, PHPWikiPage $Wiki_page) {
        if (! $Wiki_page->isAutorized($user->getId())) {
            throw new RestException(403, 'You are not allowed to see this PhpWiki page');
        }
    }

    private function checkServiceIsActivated($project_id) {
        $project = ProjectManager::instance()->getProject($project_id);
        if (! $project->usesService('plugin_phpwiki')) {
            throw new RestException(404, 'The PhpWiki plugin is not activated for this project');
        }
    }

    /**
     * @throws 403
     * @throws 404
     */
    private function checkUserCanAccessProject(PFUser $user, $project_id) {
        $project = $this->project_manager->getProject($project_id);

        ProjectAuthorization::userCanAccessProject($user, $project, new URLVerification());
    }
}