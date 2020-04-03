<?php
/**
 * Copyright (c) Enalean, 2015-2018. All Rights Reserved.
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

use Tuleap\PHPWiki\WikiPage;
use Tuleap\REST\AuthenticatedResource;
use Tuleap\REST\Header;
use Luracast\Restler\RestException;
use Tuleap\REST\ProjectAuthorization;
use Tuleap\wiki\Events\GetItemsReferencingWikiPageCollectionEvent;
use Wiki;
use WikiDao;
use UserManager;
use ProjectManager;
use URLVerification;
use PFUser;
use WikiVersionDao;
use WikiPageVersionFactory;

class PhpWikiResource extends AuthenticatedResource
{

    /** @var WikiDao */
    private $wiki_dao;

    /** @var WikiVersionDao */
    private $wiki_version_dao;

    /** @var WikiPageVersionFactory */
    private $wiki_version_factory;

    /**
     * @var UserManager
     */
    private $user_manager;

    public function __construct()
    {
        $this->wiki_dao             = new WikiDao();
        $this->wiki_version_dao     = new WikiVersionDao();
        $this->wiki_version_factory = new WikiPageVersionFactory();
        $this->user_manager         = UserManager::instance();
        $this->project_manager      = ProjectManager::instance();
    }

    /**
     * @url OPTIONS {id}
     */
    public function options($id)
    {
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
     * @throws RestException 403
     *
     * @return \Tuleap\PhpWiki\REST\v1\PhpWikiPageFullRepresentation
     */
    public function get($id)
    {
        $this->checkAccess();
        $this->checkPhpWikiPageExists($id);

        $wiki_page    = new WikiPage($id);

        $this->checkUserCanAccessProject($wiki_page->getGid());

        $current_user = $this->user_manager->getCurrentUser();

        $this->checkUserCanAccessPhpWikiService($current_user, $wiki_page->getGid());
        $this->checkUserCanSeeWikiPage($current_user, $wiki_page);

        $wiki_page_representation = new PhpWikiPageFullRepresentation();
        $wiki_page_representation->build($wiki_page);

        return $wiki_page_representation;
    }

    /**
     * @url OPTIONS {id}/versions
     */
    public function optionsVersions($id)
    {
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
     * @throws RestException 401
     * @throws RestException 403
     * @throws RestException 404
     *
     * @return array {@type Tuleap\PhpWiki\REST\v1\PhpWikiPageVersionFullRepresentation}
     */
    public function getVersions($id, $version_id)
    {
        $this->checkAccess();
        $this->checkPhpWikiPageExists($id);

        $wiki_page    = new WikiPage($id);
        $current_user = $this->user_manager->getCurrentUser();

        $this->checkUserCanAccessProject($wiki_page->getGid());

        $this->checkUserCanAccessPhpWikiService($current_user, $wiki_page->getGid());
        $this->checkUserCanSeeWikiPage($current_user, $wiki_page);

        $page_version = $this->getVersion($wiki_page, $version_id);

        $wiki_page_representation = new PhpWikiPageVersionFullRepresentation();
        $wiki_page_representation->build($page_version, $wiki_page);

        return array($wiki_page_representation);
    }

    /**
     * Get the list of items referencing the given wiki page
     *
     * Returns the list of items referencing the given wiki page
     *
     * @url    GET {id}/items_referencing_wiki_page
     * @access hybrid
     *
     * @param int $id Id of the wiki page
     *
     * @status 200
     *
     * @return array {@type Tuleap\PhpWiki\Events\ItemsReferencingWikiPageRepresentation}
     *
     * @throws RestException 401
     * @throws RestException 403
     * @throws RestException 404
     */
    public function getItemsReferencingWikiPage(int $id): array
    {
        $this->checkAccess();
        $this->checkPhpWikiPageExists($id);

        $wiki_page    = new WikiPage($id);
        $current_user = $this->user_manager->getCurrentUser();

        $this->checkUserCanAccessProject($wiki_page->getGid());
        $this->checkUserCanAccessPhpWikiService($current_user, $wiki_page->getGid());
        $this->checkUserCanSeeWikiPage($current_user, $wiki_page);

        $event = new GetItemsReferencingWikiPageCollectionEvent($wiki_page, $current_user);

        \EventManager::instance()->dispatch($event);

        return $event->getItemsReferencingWikiPage();
    }

    /** @return WikiPageVersion */
    private function getVersion(WikiPage $wiki_page, $version_id)
    {
        if ($version_id === 0) {
            $version_id = $wiki_page->getLastVersionId();
        }

        $result = $this->wiki_version_dao->getSpecificVersionForGivenPage(
            $wiki_page->getId(),
            $version_id
        );

        if (! $result || $result->count() === 0) {
            throw new RestException(404);
        }

        return $this->wiki_version_factory->getInstanceFromRow($result->getRow());
    }

    private function checkPhpWikiPageExists($page_id)
    {
        $exists = $this->wiki_dao->doesWikiPageExistInRESTContext($page_id);

        if (! $exists) {
            throw new RestException(404, 'The PhpWiki page does not exist');
        }
    }

    private function checkUserCanAccessPhpWikiService(PFUser $user, $project_id)
    {
        $wiki_service = new Wiki($project_id);

        if (! $wiki_service->isAutorized($user->getId())) {
            throw new RestException(403, 'You are not allowed to access to PhpWiki service');
        }
    }

    private function checkUserCanSeeWikiPage(PFUser $user, WikiPage $Wiki_page)
    {
        if (! $Wiki_page->isAutorized($user->getId())) {
            throw new RestException(403, 'You are not allowed to see this PhpWiki page');
        }
    }

    /**
     * @throws RestException 403
     * @throws RestException 404
     */
    private function checkUserCanAccessProject($project_id)
    {
        $project = $this->project_manager->getProject($project_id);
        $user    = $this->user_manager->getCurrentUser();

        ProjectAuthorization::userCanAccessProject($user, $project, new URLVerification());
    }
}
