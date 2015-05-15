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

namespace Tuleap\Wiki\REST\v1;

use Tuleap\REST\AuthenticatedResource;
use Tuleap\REST\Header;
use Luracast\Restler\RestException;
use Tuleap\Wiki\REST\v1\PhpWikiPageFullRepresentation;
use WikiPage;
use Wiki;
use WikiDao;
use UserManager;
use PFUser;

class PhpWikiResource extends AuthenticatedResource {

    /** @var WikiDao */
    private $wiki_dao;

    public function __construct() {
        $this->wiki_dao = new WikiDao();
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
     * @return Tuleap\Wiki\REST\v1\PhpWikiPageFullRepresentation
     */
    public function get($id) {
        $this->checkAccess();
        $this->checkPhpWikiPageExists($id);

        $wiki_page    = new WikiPage($id);
        $current_user = UserManager::instance()->getCurrentUser();

        $this->checkUserCanAccessPhpWikiService($current_user, $wiki_page->getGid());
        $this->checkUserCanSeeWikiPage($current_user, $wiki_page);

        $wiki_page_representation = new PhpWikiPageFullRepresentation();
        $wiki_page_representation->build($wiki_page);

        return $wiki_page_representation;
    }

    private function checkPhpWikiPageExists($page_id) {
        $exists = $this->wiki_dao->doesWikiPageExistInRESTContext($page_id);

        if (! $exists) {
            throw new RestException(404, 'The PhpWiki page does not exist');
        }
    }

    private function checkUserCanAccessPhpWikiService(PFUser $user, $project_id) {
        $wiki_service = new Wiki($project_id);

        if (! $wiki_service->isAutorized($user->getId())) {
            throw new RestException(403, 'You are not allowed to access to PhpWiki service');
        }
    }

    private function checkUserCanSeeWikiPage(PFUser $user, WikiPage $Wiki_page) {
        if (! $Wiki_page->isAutorized($user->getId())) {
            throw new RestException(403, 'You are not allowed to see this PhpWiki page');
        }
    }
}