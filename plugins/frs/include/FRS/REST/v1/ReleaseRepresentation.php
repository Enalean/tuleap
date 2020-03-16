<?php
/**
 * Copyright (c) Enalean, 2016 - Present. All Rights Reserved.
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

use ForgeConfig;
use FRSRelease;
use PFUser;
use Tracker_ArtifactFactory;
use Tracker_FormElementFactory;
use Tuleap\FRS\Link\Retriever;
use Tuleap\FRS\UploadedLinksRetriever;
use Tuleap\Project\REST\ProjectReference;
use Tuleap\REST\JsonCast;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Nature\NatureDao;
use Tuleap\Tracker\REST\Artifact\ArtifactRepresentationBuilder;

class ReleaseRepresentation
{
    public const ROUTE = 'frs_release';

    public const STATUS_ACTIVE  = 'active';
    public const STATUS_DELETED = 'deleted';
    public const STATUS_HIDDEN  = 'hidden';

    public static $STATUS = array(
        FRSRelease::STATUS_ACTIVE  => self::STATUS_ACTIVE,
        FRSRelease::STATUS_DELETED => self::STATUS_DELETED,
        FRSRelease::STATUS_HIDDEN  => self::STATUS_HIDDEN
    );

    /**
     * @var id {@type int}
     */
    public $id;

    /**
     * @var $uri {@type string}
     */
    public $uri;

    /**
     * @var $name {@type string}
     */
    public $name;

    /**
     * @var $files {@type array}
     */
    public $files = array();

    /**
     * @var $links {@type array}
     */
    public $links = array();

    /**
     * @var $changelog {@type string}
     */
    public $changelog;

    /**
     * @var $release_note {@type string}
     */
    public $release_note;

    /**
     * @var $resources {@type array}
     */
    public $resources;

    /**
     * @var Tuleap\REST\ResourceReference
     */
    public $project;

    /**
     * @var Tuleap\Tracker\REST\Artifact\ArtifactRepresentation
     */
    public $artifact;

    /**
     * @var bool
     */
    public $license_approval;

    /**
     * @var PackageMinimalRepresentation
     */
    public $package;

    /**
     * @var $status {@type string}
     */
    public $status;

    /**
     * @var ReleasePermissionsForGroupsRepresentation | null
     */
    public $permissions_for_groups;

    public function build(FRSRelease $release, Retriever $link_retriever, PFUser $user, UploadedLinksRetriever $uploaded_links_retriever, ReleasePermissionsForGroupsBuilder $permissions_for_groups_builder)
    {
        $this->id           = JsonCast::toInt($release->getReleaseID());
        $this->uri          = self::ROUTE . "/" . urlencode((string) $release->getReleaseID());
        $this->changelog    = $release->getChanges();
        $this->release_note = $release->getNotes();
        $this->name         = $release->getName();
        $this->status       = self::$STATUS[$release->getStatusID()];
        $this->package      = new PackageMinimalRepresentation();
        $this->package->build($release->getPackage());

        $this->artifact  = $this->getArtifactRepresentation($release, $link_retriever, $user);
        $this->resources = array(
            "artifacts" => array(
                "uri" => $this->uri . "/artifacts"
            )
        );
        $this->project = new ProjectReference();
        $this->project->build($release->getProject());

        foreach ($release->getFiles() as $file) {
            $file_representation = new FileRepresentation();
            $file_representation->build($file);
            $this->files[] = $file_representation;
        }

        foreach ($uploaded_links_retriever->getLinksForRelease($release) as $link) {
            $link_representation = new UploadedLinkRepresentation();
            $link_representation->build($link);
            $this->links[] = $link_representation;
        }

        $this->license_approval = $this->getLicenseApprovalState($release);

        $this->permissions_for_groups = $permissions_for_groups_builder->getRepresentation($user, $release);
    }

    private function getLicenseApprovalState(FRSRelease $release)
    {
        if (ForgeConfig::get('sys_frs_license_mandatory')) {
            return JsonCast::toBoolean(true);
        }

        $package = $release->getPackage();

        return JsonCast::toBoolean($package->getApproveLicense());
    }

    private function getArtifactRepresentation(FRSRelease $release, Retriever $link_retriever, PFUser $user)
    {
        $artifact_id = $link_retriever->getLinkedArtifactId($release->getReleaseID());

        if (! $artifact_id) {
            return null;
        }

        $tracker_artifact_builder = new ArtifactRepresentationBuilder(
            Tracker_FormElementFactory::instance(),
            Tracker_ArtifactFactory::instance(),
            new NatureDao()
        );

        $tracker_factory = Tracker_ArtifactFactory::instance();
        $artifact        = $tracker_factory->getArtifactByIdUserCanView($user, $artifact_id);

        if (! $artifact) {
            return null;
        }

        return $tracker_artifact_builder->getArtifactRepresentation($user, $artifact);
    }
}
