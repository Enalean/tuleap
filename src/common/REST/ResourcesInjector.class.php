<?php
/**
 * Copyright (c) Enalean, 2013 - Present. All Rights Reserved.
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

namespace Tuleap\REST;

use Luracast\Restler\Restler;
use Project;
use Tuleap\BuildVersion\REST\v1\VersionResource;
use Tuleap\InviteBuddy\REST\v1\InvitationsResource;
use Tuleap\JWT\REST\JWTRepresentation;
use Tuleap\JWT\REST\v1\JWTResource;
use Tuleap\Label\REST\LabelRepresentation;
use Tuleap\PhpWiki\REST\v1\PhpWikiResource;
use Tuleap\Platform\Banner\REST\v1\BannerResource;
use Tuleap\Project\REST\ProjectRepresentation;
use Tuleap\Project\REST\ProjectResourceReference;
use Tuleap\Project\REST\UserGroupRepresentation;
use Tuleap\Project\REST\v1\ServiceRepresentation;
use Tuleap\Project\REST\v1\ServiceResource;
use Tuleap\Project\REST\v1\UserGroupResource;
use Tuleap\REST\v1\PhpWikiPageRepresentation;
use Tuleap\REST\v1\ProjectFieldRepresentation;
use Tuleap\REST\v1\ProjectFieldsResource;
use Tuleap\SystemEvent\REST\v1\SystemEventRepresentation;
use Tuleap\SystemEvent\REST\v1\SystemEventResource;
use Tuleap\Token\REST\TokenRepresentation;
use Tuleap\Token\REST\v1\TokenResource;
use Tuleap\User\AccessKey\REST\AccessKeyResource;
use Tuleap\User\REST\UserRepresentation;
use Tuleap\User\REST\v1\UserMembershipRepresentation;
use Tuleap\User\REST\v1\UserMembershipResource;
use Tuleap\User\REST\v1\UserResource;

/**
 * Inject core resources into restler
 */
class ResourcesInjector
{
    public function populate(Restler $restler): void
    {
        $restler->addAPIClass('\\Tuleap\\Project\\REST\\ProjectResource', ProjectRepresentation::ROUTE);
        $restler->addAPIClass(TokenResource::class, TokenRepresentation::ROUTE);
        $restler->addAPIClass(UserGroupResource::class, UserGroupRepresentation::ROUTE);
        $restler->addAPIClass(UserResource::class, UserRepresentation::ROUTE);
        $restler->addAPIClass(UserMembershipResource::class, UserMembershipRepresentation::ROUTE);
        $restler->addAPIClass(ProjectFieldsResource::class, ProjectFieldRepresentation::ROUTE);
        $restler->addAPIClass(PhpWikiResource::class, PhpWikiPageRepresentation::ROUTE);
        $restler->addAPIClass(JWTResource::class, JWTRepresentation::ROUTE);
        $restler->addAPIClass(SystemEventResource::class, SystemEventRepresentation::ROUTE);
        $restler->addAPIClass(AccessKeyResource::class, AccessKeyResource::ROUTE);
        $restler->addAPIClass(ServiceResource::class, ServiceRepresentation::ROUTE);
        $restler->addAPIClass(InvitationsResource::class, InvitationsResource::ROUTE);
        $restler->addAPIClass(BannerResource::class, BannerResource::ROUTE);
        $restler->addAPIClass(VersionResource::class, VersionResource::ROUTE);
    }

    public function declareProjectResources(array &$resources, Project $project)
    {
        $this->declareProjectServicesResource($resources, $project);
        $this->declareProjectUserGroupResource($resources, $project);
        $this->declarePhpWikiResource($resources, $project);
        $this->declareHeartbeatResource($resources, $project);
        $this->declareLabelsResource($resources, $project);
    }

    private function declareProjectServicesResource(array &$resources, Project $project): void
    {
        $resource_reference = new ProjectResourceReference();
        $resource_reference->build($project, ServiceRepresentation::ROUTE);

        $resources[] = $resource_reference;
    }

    private function declareHeartbeatResource(array &$resources, Project $project)
    {
        $resource_reference = new ProjectResourceReference();
        $resource_reference->build($project, 'heartbeats');

        $resources[] = $resource_reference;
    }

    private function declareProjectUserGroupResource(array &$resources, Project $project)
    {
        $resource_reference = new ProjectResourceReference();
        $resource_reference->build($project, UserGroupRepresentation::ROUTE);

        $resources[] = $resource_reference;
    }

    private function declarePhpWikiResource(array &$resources, Project $project)
    {
        $resource_reference = new ProjectResourceReference();
        $resource_reference->build($project, PhpWikiPageRepresentation::ROUTE);

        $resources[] = $resource_reference;
    }

    private function declareLabelsResource(array &$resources, Project $project)
    {
        $resource_reference = new ProjectResourceReference();
        $resource_reference->build($project, LabelRepresentation::ROUTE);

        $resources[] = $resource_reference;
    }
}
