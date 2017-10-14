<?php
/**
 * Copyright (c) Enalean, 2016 - 2017. All Rights Reserved.
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
use Tuleap\FRS\Link\Dao;
use Tuleap\FRS\Link\Retriever;
use Tuleap\FRS\UploadedLinksDao;
use Tuleap\FRS\UploadedLinksRetriever;
use Tuleap\REST\AuthenticatedResource;
use Tuleap\REST\Header;
use UserManager;

class ReleaseResource extends AuthenticatedResource
{
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

    public function __construct()
    {
        $this->release_factory         = FRSReleaseFactory::instance();
        $this->retriever               = new Retriever(new Dao());
        $this->uploaded_link_retriever = new UploadedLinksRetriever(new UploadedLinksDao(), UserManager::instance());
        $this->package_factory         = FRSPackageFactory::instance();
        $this->user_manager            = UserManager::instance();
    }

    /**
     * Get FRS release
     *
     * @url GET {id}
     * @access hybrid
     *
     * @param int $id ID of the release
     *
     * @return \Tuleap\FRS\REST\v1\ReleaseRepresentation
     */
    public function getId($id)
    {
        $this->sendAllowOptions();

        $release = $this->release_factory->getFRSReleaseFromDb($id);

        if (! $release) {
            throw new RestException(404, "Release not found");
        }

        $release_representation = new ReleaseRepresentation();
        $user                   = $this->user_manager->getCurrentUser();
        $package                = $release->getPackage();

        if (! $this->release_factory->userCanRead($package->getGroupID(), $package->getPackageID(), $release->getReleaseID(), $user->getId())) {
            throw new RestException(403, "Access to release denied");
        }

        if ($package->isActive()) {
            $release_representation->build($release, $this->retriever, $user, $this->uploaded_link_retriever);
        } else if ($package->isHidden()
            && $this->release_factory->userCanAdmin($user, $package->getGroupID())
        ) {
            $release_representation->build($release, $this->retriever, $user, $this->uploaded_link_retriever);
        } else {
            throw new RestException(403, "Access to package denied");
        }

        return $release_representation;
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
     * @access hybrid
     *
     * @param ReleasePOSTRepresentation $body
     *
     * @return \Tuleap\FRS\REST\v1\ReleaseRepresentation
     * @status 201
     */
    public function post(ReleasePOSTRepresentation $body)
    {
        $this->sendAllowOptions();

        $user    = $this->user_manager->getCurrentUser();
        $package = $this->package_factory->getFRSPackageFromDb($body->package_id);

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
        $release_representation = new ReleaseRepresentation();
        $release_representation->build($release, $this->retriever, $user, $this->uploaded_link_retriever);

        return $release_representation;
    }

    /**
     * @url OPTION {id}
     */
    public function options()
    {
        $this->sendAllowOptions();
    }

    private function sendAllowOptions()
    {
        Header::allowOptionsGetPost();
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
}
