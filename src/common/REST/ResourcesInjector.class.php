<?php
/**
 * Copyright (c) Enalean, 2013 - 2018. All Rights Reserved.
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
use Tuleap\JWT\REST\JWTRepresentation;
use Tuleap\Label\REST\LabelRepresentation;
use \Tuleap\Project\REST\ProjectRepresentation;
use \Tuleap\Token\REST\TokenRepresentation;
use \Tuleap\Project\REST\UserGroupRepresentation;
use Tuleap\User\AccessKey\REST\AccessKeyResource;
use \Tuleap\User\REST\UserRepresentation;
use \Tuleap\REST\v1\PhpWikiPageRepresentation;
use \Tuleap\User\REST\v1\UserMembershipRepresentation;
use Tuleap\SystemEvent\REST\v1\SystemEventRepresentation;
use \Tuleap\Project\REST\ProjectResourceReference;
use \Project;

/**
 * Inject core resources into restler
 */
class ResourcesInjector {

    public function populate(Restler $restler)
    {
        $restler->addAPIClass('\\Tuleap\\Project\\REST\\ProjectResource',   ProjectRepresentation::ROUTE);
        $restler->addAPIClass('\\Tuleap\\Token\\REST\\TokenResource',       TokenRepresentation::ROUTE);
        $restler->addAPIClass('\\Tuleap\\Project\\REST\\UserGroupResource', UserGroupRepresentation::ROUTE);
        $restler->addAPIClass('\\Tuleap\\User\\REST\\UserResource',         UserRepresentation::ROUTE);
        $restler->addAPIClass('\\Tuleap\\User\\REST\\v1\\UserMembershipResource', UserMembershipRepresentation::ROUTE);
        $restler->addAPIClass('\\Tuleap\\PhpWiki\\REST\\v1\\PhpWikiResource',  PhpWikiPageRepresentation::ROUTE);
        $restler->addAPIClass('\\Tuleap\\JWT\\REST\\v1\\JWTResource',  JWTRepresentation::ROUTE);
        $restler->addAPIClass('\\Tuleap\\SystemEvent\\REST\\v1\\SystemEventResource',  SystemEventRepresentation::ROUTE);
        $restler->addAPIClass(AccessKeyResource::class,  AccessKeyResource::ROUTE);
    }

    public function declareProjectResources(array &$resources, Project $project)
    {
        $this->declareProjectUserGroupResource($resources, $project);
        $this->declarePhpWikiResource($resources, $project);
        $this->declareHeartbeatResource($resources, $project);
        $this->declareLabelsResource($resources, $project);
    }

    private function declareHeartbeatResource(array &$resources, Project $project) {
        $resource_reference = new ProjectResourceReference();
        $resource_reference->build($project, 'heartbeats');

        $resources[] = $resource_reference;
    }

    private function declareProjectUserGroupResource(array &$resources, Project $project) {
        $resource_reference = new ProjectResourceReference();
        $resource_reference->build($project, UserGroupRepresentation::ROUTE);

        $resources[] = $resource_reference;
    }

    private function declarePhpWikiResource(array &$resources, Project $project) {
        $resource_reference = new ProjectResourceReference();
        $resource_reference->build($project, PhpWikiPageRepresentation::ROUTE);

        $resources[] = $resource_reference;
    }

    private function declareLabelsResource(array &$resources, Project $project) {
        $resource_reference = new ProjectResourceReference();
        $resource_reference->build($project, LabelRepresentation::ROUTE);

        $resources[] = $resource_reference;
    }
}
