<?php
/**
 * Copyright (c) Enalean, 2016 - 2018. All Rights Reserved.
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

namespace Tuleap\FRS\REST\v1;

use FRSPackageFactory;
use FRSRelease;
use FRSReleaseFactory;
use Luracast\Restler\RestException;
use PermissionsManager;
use PFUser;
use Tuleap\FRS\FRSPermissionManager;
use Tuleap\FRS\Link\Dao;
use Tuleap\FRS\Link\Retriever;
use Tuleap\FRS\UploadedLinksDao;
use Tuleap\FRS\UploadedLinksRetriever;
use Tuleap\REST\AuthenticatedResource;
use Tuleap\REST\Header;
use Tuleap\REST\ProjectStatusVerificator;
use UGroupManager;
use UserManager;

class ReleaseResource extends AuthenticatedResource
{
    public const MAX_LIMIT      = 50;
    public const DEFAULT_LIMIT  = 10;
    public const DEFAULT_OFFSET = 0;

    /**
     * @var FRSPackageFactory
     */
    private $package_factory;
    /**
     * @var UserManager
     */
    private $user_manager;
    /**
     * @var UploadedLinksRetriever
     */
    private $uploaded_link_retriever;
    /**
     * @var FRSReleaseFactory
     */
    private $release_factory;
    /**
     * @var Retriever
     */
    private $retriever;
    /**
     * @var ReleasePermissionsForGroupsBuilder
     */
    private $permissions_for_groups_builder;

    public function __construct()
    {
        $this->release_factory                = FRSReleaseFactory::instance();
        $this->retriever                      = new Retriever(new Dao());
        $this->uploaded_link_retriever        = new UploadedLinksRetriever(new UploadedLinksDao(), UserManager::instance());
        $this->package_factory                = FRSPackageFactory::instance();
        $this->user_manager                   = UserManager::instance();
        $this->permissions_for_groups_builder = new ReleasePermissionsForGroupsBuilder(
            FRSPermissionManager::build(),
            PermissionsManager::instance(),
            new UGroupManager()
        );
    }

    /**
     * Get release
     *
     * @url GET {id}
     * @access hybrid
     *
     * @param int $id ID of the release
     *
     * @return \Tuleap\FRS\REST\v1\ReleaseRepresentation
     *
     * @throws RestException 403
     */
    public function getId($id)
    {
        $this->sendAllowOptionsForRelease();

        $release = $this->getRelease($id);
        $user    = $this->user_manager->getCurrentUser();

        ProjectStatusVerificator::build()->checkProjectStatusAllowsOnlySiteAdminToAccessIt(
            $user,
            $release->getProject()
        );

        $this->checkUserCanReadRelease($release, $user);

        $release_representation = new ReleaseRepresentation();
        $release_representation->build($release, $this->retriever, $user, $this->uploaded_link_retriever, $this->permissions_for_groups_builder);

        return $release_representation;
    }

    /**
     * Get files
     *
     * Get files belonging to a release
     *
     * @url GET {id}/files
     * @access hybrid
     *
     * @param int $id ID of the release
     * @param int $limit  Number of files displayed per page {@from path}{@min 1}{@max 50}
     * @param int $offset Position of the first file to display {@from path}{@min 0}
     *
     * @return \Tuleap\FRS\REST\v1\CollectionOfFileRepresentation
     *
     * @throws RestException 403
     */
    public function getFiles($id, $limit = self::DEFAULT_LIMIT, $offset = self::DEFAULT_OFFSET)
    {
        $release = $this->getRelease($id);
        $user    = $this->user_manager->getCurrentUser();

        ProjectStatusVerificator::build()->checkProjectStatusAllowsOnlySiteAdminToAccessIt(
            $user,
            $release->getProject()
        );

        $this->checkUserCanReadRelease($release, $user);

        $files_in_release = $release->getFiles();
        $representations  = array();
        foreach (array_slice($files_in_release, $offset, $limit) as $file) {
            $file_representation = new FileRepresentation();
            $file_representation->build($file);
            $representations[] = $file_representation;
        }

        $this->sendAllowOptionsForFiles();
        Header::sendPaginationHeaders($limit, $offset, count($files_in_release), self::MAX_LIMIT);

        $collection = new CollectionOfFileRepresentation();
        $collection->build($representations);

        return $collection;
    }

    /**
     * Create release
     *
     * Create a release in a given active package
     *
     * <p>Example of payload:</p>
     * <pre>
     * { "package_id": 42, "name": "Cajun Chicken Pasta 2.0" }
     * </pre>
     *
     * <br>
     * <p>You can also add release notes and/or changelog (optional, default is empty string):</p>
     * <pre>
     * { "package_id": 42, "name": "Cajun Chicken Pasta 2.0", "release_note": "Important informations…" }
     * </pre>
     *
     * <br>
     * <p>By default the release is active. You can set the status to hidden instead
     * (allowed values for status are "active" or "hidden"):</p>
     * <pre>
     * { "package_id": 42, "name": "Cajun Chicken Pasta 2.0", "status": "hidden" }
     * </pre>
     *
     * @url POST
     *
     *
     * @return \Tuleap\FRS\REST\v1\ReleaseRepresentation
     * @status 201
     *
     * @throws RestException 403
     */
    protected function post(ReleasePOSTRepresentation $body)
    {
        $this->sendAllowOptions();

        $user    = $this->user_manager->getCurrentUser();
        $package = $this->package_factory->getFRSPackageFromDb($body->package_id);

        ProjectStatusVerificator::build()->checkProjectStatusAllowsAllUsersToAccessIt(
            \ProjectManager::instance()->getProject($package->getGroupID())
        );

        if (! $package) {
            throw new RestException(400, "Package not found");
        }

        if (! $package->isActive()) {
            throw new RestException(403, "Package is not active");
        }

        if (! $this->package_factory->userCanUpdate($package->getGroupID(), $package->getPackageID(), $user->getId())) {
            throw new RestException(403, "Write access to package denied");
        }

        if ($this->release_factory->isReleaseNameExist($body->name, $body->package_id)) {
            throw new RestException(409, "Release name '{$body->name}' already exists in this package");
        }

        $release_array = array(
            'package_id' => $body->package_id,
            'name'       => $body->name,
            'notes'      => $body->release_note,
            'changes'    => $body->changelog,
            'status_id'  => $this->getStatusIdFromLiteralStatus($body->status)
        );

        $id = $this->release_factory->create($release_array);
        if (! $id) {
            throw new RestException(500, "An error occurred while creating the release");
        }

        $release = $this->release_factory->getFRSReleaseFromDb($id);
        if (! $release) {
            throw new RestException(500, "Unable to retrieve the release from the DB. Please contact site administrators");
        }
        $release_representation = new ReleaseRepresentation();
        $release_representation->build($release, $this->retriever, $user, $this->uploaded_link_retriever, $this->permissions_for_groups_builder);

        return $release_representation;
    }

    /**
     * Update release
     *
     * Update the metadata of a release. Only name, release_note, changelog and/or status can be changed for now.
     *
     * <p>Example to change the name:</p>
     * <pre>
     * { "name": "Cajun Chicken Pasta 2.1" }
     * </pre>
     *
     * @url PATCH {id}
     *
     * @param int $id
     *
     * @throws RestException 403
     */
    protected function patchId($id, ReleasePATCHRepresentation $body)
    {
        $this->sendAllowOptionsForRelease();

        $user    = $this->user_manager->getCurrentUser();
        $release = $this->getRelease($id);

        ProjectStatusVerificator::build()->checkProjectStatusAllowsAllUsersToAccessIt(
            $release->getProject()
        );

        if (! $this->release_factory->userCanUpdate($release->getGroupID(), $release->getReleaseID(), $user->getId())) {
            throw new RestException(403, "Write access to release denied");
        }

        $release_array = $this->getArrayForUpdateRelease($release, $body);

        if (count($release_array) > 1) {
            $is_success = $this->release_factory->update($release_array);
            if (! $is_success) {
                throw new RestException(500, "An error occurred while updating the release");
            }
        }
    }

    /**
     * @url OPTIONS
     */
    public function options()
    {
        $this->sendAllowOptions();
    }

    private function sendAllowOptions()
    {
        Header::allowOptionsPost();
    }

    /**
     * @url OPTIONS {id}
     */
    public function optionsId($id)
    {
        $this->sendAllowOptionsForRelease();
    }

    /**
     * @url OPTIONS {id}/frs_files
     */
    public function optionsFiles($id)
    {
        $this->sendAllowOptionsForFiles();
    }

    private function sendAllowOptionsForRelease()
    {
        Header::allowOptionsGetPatch();
    }

    private function sendAllowOptionsForFiles()
    {
        Header::allowOptionsGet();
    }

    /**
     * @return int
     */
    private function getStatusIdFromLiteralStatus($status)
    {
        if (! $status) {
            return FRSRelease::STATUS_ACTIVE;
        }

        return array_search($status, ReleaseRepresentation::$STATUS);
    }

    /**
     * @param $id
     * @return FRSRelease
     */
    private function getRelease($id)
    {
        $release = $this->release_factory->getFRSReleaseFromDb($id);

        if (! $release) {
            throw new RestException(404);
        }

        return $release;
    }

    /**
     * @param $release
     * @return array
     */
    private function getArrayForUpdateRelease($release, ReleasePATCHRepresentation $body)
    {
        $release_id = (int) $release->getReleaseID();
        $package_id = (int) $release->getPackageID();

        $release_array = array(
            'release_id' => $release_id
        );

        if ($body->name) {
            $with_same_name_release_id = (int) $this->release_factory->getReleaseIdByName($body->name, $package_id);
            if ($with_same_name_release_id && $with_same_name_release_id !== $release_id) {
                throw new RestException(409, "Release name '{$body->name}' already exists in this package");
            }

            $release_array['name'] = $body->name;
        }

        if ($body->release_note) {
            $release_array['notes'] = $body->release_note;
        }

        if ($body->changelog) {
            $release_array['changes'] = $body->changelog;
        }

        if ($body->status) {
            $release_array['status_id'] = $this->getStatusIdFromLiteralStatus($body->status);
        }

        return $release_array;
    }

    private function checkUserCanReadRelease(FRSRelease $release, PFUser $user)
    {
        $package = $release->getPackage();

        if ($package->isHidden() && ! $this->release_factory->userCanAdmin($user, $package->getGroupID())) {
            throw new RestException(403, "Access to package denied");
        }

        if (! $this->release_factory->userCanRead(
            $package->getGroupID(),
            $package->getPackageID(),
            $release->getReleaseID(),
            $user->getId()
        )) {
            throw new RestException(403, "Access to release denied");
        }

        return $user;
    }
}
