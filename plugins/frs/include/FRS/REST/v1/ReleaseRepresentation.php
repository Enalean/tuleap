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
use Tuleap\Markdown\CommonMarkInterpreter;
use Tuleap\Project\REST\ProjectReference;
use Tuleap\REST\JsonCast;
use Tuleap\Tracker\Artifact\Changeset\Comment\PrivateComment\CachingTrackerPrivateCommentInformationRetriever;
use Tuleap\Tracker\Artifact\Changeset\Comment\PrivateComment\PermissionChecker;
use Tuleap\Tracker\Artifact\Changeset\Comment\PrivateComment\TrackerPrivateCommentInformationRetriever;
use Tuleap\Tracker\Artifact\Changeset\Comment\PrivateComment\TrackerPrivateCommentUGroupEnabledDao;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Type\TypeDao;
use Tuleap\Tracker\REST\Artifact\ArtifactRepresentation;
use Tuleap\Tracker\REST\Artifact\ArtifactRepresentationBuilder;
use Tuleap\Tracker\REST\Artifact\Changeset\ChangesetRepresentationBuilder;
use Tuleap\Tracker\REST\Artifact\Changeset\Comment\CommentRepresentationBuilder;
use Tuleap\Tracker\REST\Artifact\StatusValueRepresentation;
use Tuleap\Tracker\Semantic\Status\RetrieveSemanticStatus;
use Tuleap\User\Avatar\AvatarHashDao;
use Tuleap\User\Avatar\ComputeAvatarHash;
use Tuleap\User\Avatar\ProvideUserAvatarUrl;
use Tuleap\User\Avatar\UserAvatarUrlProvider;

/**
 * @psalm-immutable
 */
final class ReleaseRepresentation
{
    public const ROUTE = 'frs_release';

    public const STATUS_ACTIVE  = 'active';
    public const STATUS_DELETED = 'deleted';
    public const STATUS_HIDDEN  = 'hidden';

    public static $STATUS = [
        FRSRelease::STATUS_ACTIVE  => self::STATUS_ACTIVE,
        FRSRelease::STATUS_DELETED => self::STATUS_DELETED,
        FRSRelease::STATUS_HIDDEN  => self::STATUS_HIDDEN,
    ];

    /**
     * @var int id {@type int}
     */
    public $id;

    /**
     * @var string $uri {@type string}
     */
    public $uri;

    /**
     * @var string $name {@type string}
     */
    public $name;

    /**
     * @var array $files {@type array}
     */
    public $files = [];

    /**
     * @var array $links {@type array}
     */
    public $links = [];

    /**
     * @var string $changelog {@type string}
     */
    public $changelog;

    /**
     * @var string $release_note {@type string}
     */
    public $release_note;

    /**
     * @var array $resources {@type array}
     */
    public $resources;

    /**
     * @var ProjectReference
     */
    public $project;

    /**
     * @var ArtifactRepresentation | null
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
     * @var string $status {@type string}
     */
    public $status;

    /**
     * @var ReleasePermissionsForGroupsRepresentation | null
     */
    public $permissions_for_groups;

    public function __construct(
        FRSRelease $release,
        Retriever $link_retriever,
        PFUser $user,
        UploadedLinksRetriever $uploaded_links_retriever,
        ReleasePermissionsForGroupsBuilder $permissions_for_groups_builder,
        ProvideUserAvatarUrl $provide_user_avatar_url,
        RetrieveSemanticStatus $semantic_status_retriever,
    ) {
        $this->id           = JsonCast::toInt($release->getReleaseID());
        $this->uri          = self::ROUTE . '/' . urlencode((string) $release->getReleaseID());
        $this->changelog    = $release->getChanges();
        $this->release_note = $release->getNotes();
        $this->name         = $release->getName();
        $this->status       = self::$STATUS[$release->getStatusID()];
        $this->package      = self::getPackageRepresentation($release);

        $this->artifact  = self::getArtifactRepresentation($release, $link_retriever, $user, $semantic_status_retriever);
        $this->resources = [
            'artifacts' => [
                'uri' => $this->uri . '/artifacts',
            ],
        ];
        $this->project   = self::getProjectReference($release);

        $this->files = self::getFiles($release, $provide_user_avatar_url);

        $this->links = self::getLinks($uploaded_links_retriever, $release, $provide_user_avatar_url);

        $this->license_approval = self::getLicenseApprovalState($release);

        $this->permissions_for_groups = self::getPermissionsForGroups($permissions_for_groups_builder, $user, $release);
    }

    private static function getLicenseApprovalState(FRSRelease $release): bool
    {
        if (ForgeConfig::get('sys_frs_license_mandatory')) {
            return JsonCast::toBoolean(true);
        }

        $package = $release->getPackage();

        return JsonCast::toBoolean($package->getApproveLicense());
    }

    private static function getArtifactRepresentation(FRSRelease $release, Retriever $link_retriever, PFUser $user, RetrieveSemanticStatus $semantic_status_retriever): ?ArtifactRepresentation
    {
        $artifact_id = $link_retriever->getLinkedArtifactId($release->getReleaseID());

        if (! $artifact_id) {
            return null;
        }

        $form_element_factory     = Tracker_FormElementFactory::instance();
        $tracker_artifact_builder = new ArtifactRepresentationBuilder(
            $form_element_factory,
            Tracker_ArtifactFactory::instance(),
            new TypeDao(),
            new ChangesetRepresentationBuilder(
                \UserManager::instance(),
                $form_element_factory,
                new CommentRepresentationBuilder(
                    CommonMarkInterpreter::build(\Codendi_HTMLPurifier::instance())
                ),
                new PermissionChecker(new CachingTrackerPrivateCommentInformationRetriever(new TrackerPrivateCommentInformationRetriever(new TrackerPrivateCommentUGroupEnabledDao()))),
                new UserAvatarUrlProvider(new AvatarHashDao(), new ComputeAvatarHash()),
            ),
            new UserAvatarUrlProvider(new AvatarHashDao(), new ComputeAvatarHash()),
        );

        $tracker_factory = Tracker_ArtifactFactory::instance();
        $artifact        = $tracker_factory->getArtifactByIdUserCanView($user, $artifact_id);

        if (! $artifact) {
            return null;
        }

        return $tracker_artifact_builder->getArtifactRepresentation($user, $artifact, StatusValueRepresentation::buildFromArtifact($artifact, $user, $semantic_status_retriever));
    }

    private static function getPackageRepresentation(FRSRelease $release): PackageMinimalRepresentation
    {
        return new PackageMinimalRepresentation($release->getPackage());
    }

    private static function getProjectReference(FRSRelease $release): ProjectReference
    {
        return new ProjectReference($release->getProject());
    }

    /**
     * @return FileRepresentation[]
     */
    private static function getFiles(FRSRelease $release, ProvideUserAvatarUrl $provide_user_avatar_url): array
    {
        $files = [];
        foreach ($release->getFiles() as $file) {
            $file_representation = new FileRepresentation($file, $provide_user_avatar_url);
            $files[]             = $file_representation;
        }

        return $files;
    }

    /**
     * @return UploadedLinkRepresentation[]
     */
    private static function getLinks(UploadedLinksRetriever $uploaded_links_retriever, FRSRelease $release, ProvideUserAvatarUrl $provide_user_avatar_url): array
    {
        $links = [];
        foreach ($uploaded_links_retriever->getLinksForRelease($release) as $link) {
            $link_representation = new UploadedLinkRepresentation($link, $provide_user_avatar_url);
            $links[]             = $link_representation;
        }

        return $links;
    }

    private static function getPermissionsForGroups(
        ReleasePermissionsForGroupsBuilder $permissions_for_groups_builder,
        PFUser $user,
        FRSRelease $release,
    ): ?ReleasePermissionsForGroupsRepresentation {
        return $permissions_for_groups_builder->getRepresentation($user, $release);
    }
}
