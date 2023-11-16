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

namespace Tuleap\User\Admin;

use Project;
use ProjectManager;
use Tuleap\Date\DateHelper;
use Tuleap\Project\Admin\DescriptionFields\ProjectDescriptionFieldBuilder;
use Tuleap\Project\ProjectAccessPresenter;
use Tuleap\Trove\TroveCatCollectionRetriever;
use UserManager;

class PendingProjectBuilder
{
    /**
     * @var ProjectManager
     */
    private $project_manager;
    /**
     * @var UserManager
     */
    private $user_manager;
    /**
     * @var ProjectDescriptionFieldBuilder
     */
    private $field_builder;
    /**
     * @var TroveCatCollectionRetriever
     */
    private $trove_cat_collection_retriever;

    public function __construct(
        ProjectManager $project_manager,
        UserManager $user_manager,
        ProjectDescriptionFieldBuilder $field_builder,
        TroveCatCollectionRetriever $trove_cat_collection_retriever,
    ) {
        $this->project_manager                = $project_manager;
        $this->user_manager                   = $user_manager;
        $this->field_builder                  = $field_builder;
        $this->trove_cat_collection_retriever = $trove_cat_collection_retriever;
    }

    /**
     * @return array
     */
    public function build()
    {
        $project_list = [];
        $projects_id  = [];

        foreach ($this->project_manager->getAllPendingProjects() as $project) {
            $admin = $this->getProjectAdminWhichIsFirstProjectMember($project);
            if (! $admin) {
                $admin = $this->user_manager->getUserAnonymous();
            }
            $custom_fields = $this->field_builder->build($project);
            $trovecats     = $this->trove_cat_collection_retriever->getCollection($project->getID());

            $project_list[] = [
                'project_id'          => $project->getID(),
                'project_public_name' => $project->getPublicName(),
                'project_unix_name'   => $project->getUnixNameMixedCase(),
                'project_is_public'   => $project->isPublic(),
                'project_get_access'  => $project->getAccess(),
                'project_description' => $project->getDescription(),
                'admin_is_anonymous'  => $admin->isAnonymous(),
                'user_id'             => $admin->getId(),
                'user_name'           => $admin->getRealName(),
                'user_has_avatar'     => $admin->hasAvatar(),
                'user_avatar'         => $admin->getAvatarUrl(),
                'date_creation'       => DateHelper::formatForLanguage($GLOBALS['Language'], $project->getStartDate()),
                'project_fields'      => $custom_fields,
                'has_custom_fields'   => $this->hasCustomFields($custom_fields),
                'access_presenter'    => new ProjectAccessPresenter($project->getAccess()),
                'trovecats'           => $trovecats,
            ];

            $projects_id[] = $project->getID();
        }

        return [
            'project_list' => $project_list,
            'projects_id' => implode(',', $projects_id),
        ];
    }

    private function hasCustomFields(array $custom_fields)
    {
        foreach ($custom_fields as $field) {
            if ($field['is_required']) {
                return true;
            }
        }

        return false;
    }

    private function getProjectAdminWhichIsFirstProjectMember(Project $project)
    {
        $admin      = "";
        $members_id = $project->getMembersId();
        if (count($members_id) > 0) {
            $admin_id = $members_id[0];
            $admin    = $this->user_manager->getUserById($admin_id);
        }

        return $admin;
    }
}
